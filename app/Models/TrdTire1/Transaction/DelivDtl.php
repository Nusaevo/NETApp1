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
            DB::transaction(function () use ($delivDtl) {
                // Generate batch code jika kosong
                if (empty($delivDtl->batch_code)) {
                    $delivDtl->batch_code = date('ymd');
                }

                $oldMatlId = $delivDtl->getOriginal('matl_id');
                $oldMatlUom = $delivDtl->getOriginal('matl_uom');
                $oldQty = (float) $delivDtl->getOriginal('qty', 0);
                $newQty = (float) $delivDtl->qty;

                // Handle perubahan material/uom
                if ($oldMatlId != $delivDtl->matl_id || $oldMatlUom != $delivDtl->matl_uom) {
                    // Rollback stok lama untuk PD
                    if ($delivDtl->tr_type === 'PD' && $oldQty != 0) {
                        $oldIvtBal = IvtBal::where([
                            'matl_id' => $oldMatlId,
                            'matl_uom' => $oldMatlUom,
                            'wh_id' => $delivDtl->wh_id,
                            'batch_code' => $delivDtl->batch_code,
                        ])->first();

                        if ($oldIvtBal) {
                            $oldIvtBal->increment('qty_oh', $oldQty);
                        }

                        $oldMatlUomRec = MatlUom::where([
                            'matl_id' => $oldMatlId,
                            'matl_uom' => $oldMatlUom,
                        ])->first();

                        if ($oldMatlUomRec) {
                            $oldMatlUomRec->increment('qty_fgr', $oldQty);
                            $oldMatlUomRec->decrement('qty_oh', $oldQty);
                        }
                    }
                }

                // Hitung delta berdasarkan tipe transaksi
                $delta = $newQty - ($delivDtl->exists ? $oldQty : 0);

                // Handle PD (Goods Receipt)
                if ($delivDtl->tr_type === 'PD') {
                    $ivtBal = IvtBal::firstOrCreate(
                        [
                            'matl_id' => $delivDtl->matl_id,
                            'matl_uom' => $delivDtl->matl_uom,
                            'wh_id' => $delivDtl->wh_id,
                            'batch_code' => $delivDtl->batch_code,
                        ],
                        ['qty_oh' => 0]
                    );

                    $ivtBal->increment('qty_oh', $delta);
                    $delivDtl->ivt_id = $ivtBal->id;

                    // Update MatlUom
                    $matlUomRec = MatlUom::where([
                        'matl_id' => $delivDtl->matl_id,
                        'matl_uom' => $delivDtl->matl_uom,
                    ])->first();

                    if ($matlUomRec) {
                        $matlUomRec->decrement('qty_fgr', $delta);
                        $matlUomRec->increment('qty_oh', $delta);
                    }
                }
                // Handle SD (Goods Issue)
                elseif ($delivDtl->tr_type === 'SD') {
                    // Buat atau ambil IvtBal
                    $ivtBal = IvtBal::firstOrCreate(
                        [
                            'matl_id'    => $delivDtl->matl_id,
                            'matl_uom'   => $delivDtl->matl_uom,
                            'wh_id'      => $delivDtl->wh_id,
                            'batch_code' => $delivDtl->batch_code,
                        ],
                        ['qty_oh' => 0]  // default kalau baru dibuat
                    );

                    // Assign ivt_id agar tidak null
                    $delivDtl->ivt_id = $ivtBal->id;


                    // Update qty_fgi di MatlUom
                    if ($delta != 0) {
                        $matlUomRec = MatlUom::where([
                            'matl_id'  => $delivDtl->matl_id,
                            'matl_uom' => $delivDtl->matl_uom,
                            ])->first();

                            if ($matlUomRec) {
                                $matlUomRec->decrement('qty_fgi', $delta); // Ensure this is applied only once
                            }
                        }
                        // Kurangi stok on-hand
                        if ($delta > 0) {
                            $ivtBal->decrement('qty_oh', $delta);
                        }
                }

                // Update OrderDtl
                if ($delivDtl->OrderDtl && $delta != 0) {
                    $delivDtl->OrderDtl->increment('qty_reff', $delta);
                }
            });
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
            DB::transaction(function () use ($delivDtl) {
                // Handle PD (Goods Receipt)
                if ($delivDtl->tr_type === 'PD') {
                    $ivtBal = IvtBal::where([
                        'matl_id' => $delivDtl->matl_id,
                        'matl_uom' => $delivDtl->matl_uom,
                        'wh_id' => $delivDtl->wh_id,
                        'batch_code' => $delivDtl->batch_code,
                    ])->first();

                    if ($ivtBal) {
                        $ivtBal->decrement('qty_oh', $delivDtl->qty);
                    }

                    $matlUomRec = MatlUom::where([
                        'matl_id' => $delivDtl->matl_id,
                        'matl_uom' => $delivDtl->matl_uom,
                    ])->first();

                    if ($matlUomRec) {
                        $matlUomRec->increment('qty_fgr', $delivDtl->qty);
                        $matlUomRec->decrement('qty_oh', $delivDtl->qty);
                    }
                }
                // Handle SD (Goods Issue)
                elseif ($delivDtl->tr_type === 'SD') {
                    $matlUomRec = MatlUom::where([
                        'matl_id' => $delivDtl->matl_id,
                        'matl_uom' => $delivDtl->matl_uom,
                    ])->first();

                    if ($matlUomRec) {
                        // $matlUomRec->increment('qty_fgi', $delivDtl->qty);
                    }
                }

                // Rollback OrderDtl
                if ($delivDtl->OrderDtl) {
                    $delivDtl->OrderDtl->decrement('qty_reff', $delivDtl->qty);
                }

                // Hapus log dan billing
                IvtLog::where([
                    'trhdr_id' => $delivDtl->trhdr_id,
                    'tr_type' => $delivDtl->tr_type,
                    'tr_seq' => $delivDtl->tr_seq,
                ])->delete();

                BillingDtl::where('dlvdtl_id', $delivDtl->id)->forceDelete();
            });
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
