<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Master\Material;
use App\Models\TrdTire1\Inventories\IvtBal;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Enums\Constant;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdTire1\Inventories\IvtLog;
use App\Models\TrdTire1\Master\MatlUom;
// Pastikan BillingDtl sudah di-import jika digunakan di sini
use App\Models\TrdTire1\Transaction\{OrderDtl, BillingDtl};

class DelivDtl extends BaseModel
{
    use SoftDeletes;

    protected $table = 'deliv_dtls';

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_code',
        'tr_seq',
        'reffdtl_id',
        'reffhdrtr_type',
        'reffhdrtr_code',
        'reffdtltr_seq',
        'matl_id',
        'matl_code',
        'matl_uom',
        'matl_descr',
        'wh_code',
        'qty',
        'wh_id',
        'status_code',
        'ivt_id',
        'batch_code'
    ];

    protected static function boot()
    {
        parent::boot();

        // Event saving: sebelum data disimpan, jalankan semua operasi dalam satu transaksi
        static::saving(function ($delivDtl) {
            // Pastikan batch_code sudah terisi sesuai logic sebelumnya
            if (empty($delivDtl->batch_code)) {
                $delivDtl->batch_code = date('ymd');
            }

            $oldMatlId  = $delivDtl->getOriginal('matl_id');
            $oldMatlUom = $delivDtl->getOriginal('matl_uom');
            $oldQty     = (float) $delivDtl->getOriginal('qty', 0);

            $newMatlId  = $delivDtl->matl_id;
            $newMatlUom = $delivDtl->matl_uom;
            $newQty     = (float) $delivDtl->qty;

            $combinationChanged = $oldMatlId != $newMatlId || $oldMatlUom != $newMatlUom;

            // Jika kombinasi material atau uom berubah, lakukan adjustment untuk record lama
            if ($combinationChanged && $oldQty != 0) {
                // Ambil record IvtBal untuk kombinasi lama dengan batch_code
                $oldIvtBal = IvtBal::where([
                    'matl_id'    => $oldMatlId,
                    'matl_uom'   => $oldMatlUom,
                    'wh_id'      => $delivDtl->wh_id,
                    'batch_code' => $delivDtl->batch_code,
                ])->first();

                if ($oldIvtBal) {
                    $delta = $delivDtl->tr_type === 'PD' ? -$oldQty : $oldQty;
                    $oldIvtBal->increment('qty_oh', $delta);

                    IvtLog::removeIvtLogIfExists($delivDtl->trhdr_id, $delivDtl->tr_type, $delivDtl->tr_seq);
                }

                $oldMatlUomRec = MatlUom::where([
                    'matl_id'  => $oldMatlId,
                    'matl_uom' => $oldMatlUom,
                ])->first();

                if ($oldMatlUomRec) {
                    if ($delivDtl->tr_type == 'PD') {
                    } elseif ($delivDtl->tr_type == 'SD') {
                        $oldMatlUomRec->decrement('qty_fgi', $oldQty);
                    }
                    MatlUom::recalcMatlUomQtyOh($oldMatlId, $oldMatlUom);
                    MatlUom::recalcMatlUomQtyFgr($oldMatlId, $oldMatlUom);
                }
            }

            $delta = $combinationChanged ? $newQty : $newQty - $oldQty;

            if ($delta != 0) {
                $ivtBal = IvtBal::firstOrCreate(
                    [
                        'matl_id'    => $newMatlId,
                        'matl_uom'   => $newMatlUom,
                        'wh_id'      => $delivDtl->wh_id,
                        'wh_code'    => $delivDtl->wh_code,
                        'batch_code' => $delivDtl->batch_code,
                    ],
                    [
                        'matl_code'  => $delivDtl->matl_code,
                        'matl_descr' => $delivDtl->matl_descr,
                        'qty_oh'     => 0,
                    ]
                );

                // Adjustment disesuaikan dengan tipe transaksi
                $adjustment = $delivDtl->tr_type == 'PD' ? $delta : -$delta;
                $ivtBal->increment('qty_oh', $adjustment);

                // Kurangi qty_fgr jika transaksi adalah PD
                if ($delivDtl->tr_type === 'PD') {
                    $matlUomRec = MatlUom::where([
                        'matl_id'  => $newMatlId,
                        'matl_uom' => $newMatlUom,
                    ])->first();

                    if ($matlUomRec) {
                        $matlUomRec->decrement('qty_fgr', $delta);
                    }
                }

                // Kurangi qty_fgi jika transaksi adalah SD
                if ($delta != 0 && $delivDtl->tr_type === 'SD') {
                    $matlUomRec = MatlUom::where([
                        'matl_id'  => $delivDtl->matl_id,
                        'matl_uom' => $delivDtl->matl_uom,
                    ])->first();

                    if ($matlUomRec) {
                        $matlUomRec->decrement('qty_fgi', $delta); // Ensure this is applied only once
                    }
                }

                // Simpan id IvtBal ke DelivDtl
                $delivDtl->ivt_id = $ivtBal->id;
                // Simpan data secara quiet untuk menghindari pemicu event kembali
                $delivDtl->saveQuietly();

                // Recalculate stok pada MatlUom untuk kombinasi baru
                MatlUom::recalcMatlUomQtyOh($newMatlId, $newMatlUom);

                // Update atau buat log transaksi
                IvtLog::updateOrCreate(
                    [
                        'trhdr_id' => $delivDtl->trhdr_id,
                        'tr_type'  => $delivDtl->tr_type,
                        'tr_seq'   => $delivDtl->tr_seq,
                    ],
                    [
                        'tr_id'      => $delivDtl->tr_id,
                        'trdtl_id'   => $delivDtl->id,
                        'ivt_id'     => $delivDtl->ivt_id,
                        'matl_id'    => $newMatlId,
                        'matl_code'  => $delivDtl->matl_code,
                        'matl_uom'   => $newMatlUom,
                        'wh_id'      => $delivDtl->wh_id,
                        'wh_code'    => $delivDtl->wh_code,
                        'batch_code' => $delivDtl->batch_code ?? '',
                        'tr_date'    => date('Y-m-d'),
                        'qty'        => $newQty,
                        'price'      => $delivDtl->OrderDtl->price ?? 0,
                        'amt'        => $delivDtl->qty * ($delivDtl->OrderDtl->price ?? 0),
                        'tr_desc'    => $delivDtl->matl_descr,
                    ]
                );
            }

            // Jika relasi OrderDtl telah ter-load, update nilai qty_reff
            if ($delivDtl->relationLoaded('OrderDtl') && $delivDtl->OrderDtl && $delta != 0) {
                $delivDtl->OrderDtl->increment('qty_reff', $delta);
            }

            // Khusus untuk tipe PD, pastikan field qty_fgr direkalkulasi ulang
            if ($delivDtl->tr_type === 'PD') {
                $matlUomRec = MatlUom::where([
                    'matl_id'  => $delivDtl->matl_id,
                    'matl_uom' => $delivDtl->matl_uom,
                ])->first();

                if ($matlUomRec) {
                    // Calculate the difference between the new and old quantities
                    $oldQty = (float) $delivDtl->getOriginal('qty', 0);
                    $newQty = (float) $delivDtl->qty;
                    $deltaQty = $newQty - $oldQty;

                    // Adjust qty_fgr based on the delta
                    if ($deltaQty > 0) {
                        $matlUomRec->decrement('qty_fgr', $deltaQty);
                    } elseif ($deltaQty < 0) {
                        $matlUomRec->increment('qty_fgr', abs($deltaQty));
                    }
                }
            }
        });

        // Event saved: setelah data tersimpan, update IvtLog dan BillingDtl di dalam transaksi
        static::saved(function ($delivDtl) {
            $header = $delivDtl->DelivHdr;
            $orderDtl = $delivDtl->OrderDtl;
            $billingHdr = BillingHdr::where('tr_code', $delivDtl->tr_code)
                ->where('tr_type', $delivDtl->tr_type == 'SD' ? 'ARB' : 'APB')
                ->first();

            IvtLog::updateOrCreate(
                [
                    'trhdr_id' => $header ? $header->id : $delivDtl->trhdr_id,
                    'tr_type'  => $header ? $header->tr_type : $delivDtl->tr_type,
                    'tr_seq'   => $delivDtl->tr_seq,
                ],
                [
                    'tr_code'    => $header ? $header->tr_code : $delivDtl->tr_code,
                    'trdtl_id'   => $delivDtl->id,
                    'ivt_id'     => $delivDtl->ivt_id,
                    'matl_id'    => $delivDtl->matl_id,
                    'matl_code'  => $delivDtl->matl_code,
                    'matl_uom'   => $delivDtl->matl_uom,
                    'wh_id'      => $delivDtl->wh_id,
                    'wh_code'    => $delivDtl->wh_code,
                    'batch_code' => $delivDtl->batch_code,
                    'tr_date'    => $header ? $header->tr_date : null,
                    'qty'        => $delivDtl->qty,
                    'price'      => $orderDtl ? $orderDtl->price * (1 - ($orderDtl->disc_pct / 100)) : 0,
                    'amt'        => $delivDtl->qty * ($orderDtl ? $orderDtl->price * (1 - ($orderDtl->disc_pct / 100)) : 0),
                    'tr_desc'    => $delivDtl->matl_descr,
                ]
            );

            BillingDtl::updateOrCreate(
                [
                    'trhdr_id' => $billingHdr->id,
                    'tr_seq'   => $delivDtl->tr_seq,
                    'tr_type'  => $delivDtl->tr_type == 'SD' ? 'ARB' : 'APB',
                ],
                [
                    'trhdr_id'   => $billingHdr->id,
                    'tr_type'    => $delivDtl->tr_type == 'SD' ? 'ARB' : 'APB',
                    'tr_code'    => $delivDtl->tr_code,
                    'tr_seq'     => $delivDtl->tr_seq,
                    'matl_id'    => $delivDtl->matl_id,
                    'matl_code'  => $delivDtl->matl_code,
                    'matl_uom'   => $delivDtl->matl_uom,
                    'descr'      => $delivDtl->matl_descr,
                    'qty'        => $delivDtl->qty,
                    'price'      => $orderDtl ? $orderDtl->amt : 0,
                    'amt'        => $delivDtl->qty * ($orderDtl ? $orderDtl->amt : 0),
                    'dlvdtl_id'  => $delivDtl->id,
                    'dlvdtlr_seq'=> $header ? $header->tr_seq : $delivDtl->tr_seq,
                    'dlvhdr_type'=> $header ? $header->tr_type : $delivDtl->tr_type,
                    'dlvhdr_id'  => $header ? $header->id : $delivDtl->trhdr_id,
                ]
            );
        });

        static::deleting(function ($delivDtl) {
            $warehouse = ConfigConst::where('str1', $delivDtl->wh_code)->first();
            if ($warehouse) {
                $delivDtl->wh_id = $warehouse->id;
            }

            $existingBal = IvtBal::where('matl_id', $delivDtl->matl_id)
                ->where('wh_id', $delivDtl->wh_id)
                ->where('batch_code', $delivDtl->batch_code)
                ->lockForUpdate()
                ->first();

            $qtyChange = (float)$delivDtl->qty;
            if ($delivDtl->tr_type === 'PD') {
                $qtyChange = -$qtyChange;
            } elseif ($delivDtl->tr_type === 'SD') {
                // Tidak ada perubahan qty untuk tipe SD
            }

            if ($existingBal) {
                $newQty = $existingBal->qty_oh + $qtyChange;
                $existingBal->update(['qty_oh' => $newQty]);
            }

            // Hapus data pada ivtLog
            IvtLog::where([
                'trhdr_id' => $delivDtl->trhdr_id,
                'tr_type'  => $delivDtl->tr_type,
                'tr_seq'   => $delivDtl->tr_seq,
            ])->delete();

            // Kembalikan nilai qty_reff pada OrderDtl jika relasi ada
            if ($delivDtl->relationLoaded('OrderDtl') && $delivDtl->OrderDtl) {
                $delivDtl->OrderDtl->decrement('qty_reff', $delivDtl->qty);
            }

            // Tambahkan logika untuk memastikan relasi OrderDtl dimuat jika belum
            if (!$delivDtl->relationLoaded('OrderDtl')) {
                $orderDtl = $delivDtl->OrderDtl;
                if ($orderDtl) {
                    $orderDtl->decrement('qty_reff', $delivDtl->qty);
                }
            }

            // Kembalikan nilai qty_oh dan qty_fgr pada MatlUom jika relasi ada
            $matlUomRec = MatlUom::where([
                'matl_id'  => $delivDtl->matl_id,
                'matl_uom' => $delivDtl->matl_uom,
            ])->first();

            if ($matlUomRec) {
                if ($delivDtl->tr_type === 'PD') {
                    // Restore qty_fgr by the quantity being deleted
                    $matlUomRec->increment('qty_fgr', $delivDtl->qty);
                } elseif ($delivDtl->tr_type === 'SD') {
                    $matlUomRec->increment('qty_fgi', $delivDtl->qty); // Ensure this is applied only once
                }
                MatlUom::recalcMatlUomQtyOh($delivDtl->matl_id, $delivDtl->matl_uom);
            }

            BillingDtl::where('trhdr_id', $delivDtl->trhdr_id)
                ->where('tr_seq', $delivDtl->tr_seq)
                ->forceDelete();
        });
    }

    /**
     * Scope untuk mendapatkan data berdasarkan delivery header dan tipe transaksi
     */
    public function scopeGetByDelivHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)
            ->where('tr_type', $trType);
    }

    #region Relations

    /**
     * Relasi ke master Material
     */
    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }

    /**
     * Relasi ke delivery header berdasarkan tipe transaksi
     */
    public function DelivHdr()
    {
        if ($this->tr_type) {
            return $this->belongsTo(DelivHdr::class, 'trhdr_id', 'id')
                ->where('tr_type', $this->tr_type);
        }
        return null;
    }

    public function OrderDtl()
    {
        return $this->belongsTo(OrderDtl::class, 'reffdtl_id', 'id')
            ->where('tr_type', $this->reffhdrtr_type);
    }

    #endregion
}
