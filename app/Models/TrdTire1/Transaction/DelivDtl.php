<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Master\Material;
use App\Models\TrdTire1\Inventories\IvtBal;
use App\Models\TrdTire1\Inventories\IvtBalUnit;
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
            // Persiapan data
            if (empty($delivDtl->batch_code)) {
                $delivDtl->batch_code = date('y/m/d');
            }
            if (empty($delivDtl->matl_uom)) {
                // Pastikan value sudah terisi jika diperlukan
                $delivDtl->matl_uom;
            }
            $warehouse = ConfigConst::where('str1', $delivDtl->wh_code)->first();
            if ($warehouse) {
                $delivDtl->wh_id = $warehouse->id;
            }

            // Cek apakah record DelivDtl sudah ada (update) atau baru (create)
            $existing = null;
            $oldQty = 0;
            if ($delivDtl->exists) {
                $existing = DelivDtl::find($delivDtl->id);
                if ($existing) {
                    $oldQty = (float)$existing->qty;
                }
            }

            $newQty = (float)$delivDtl->qty;
            $delta = $newQty - $oldQty;

            // Cari atau buat record IvtBal
            $ivtBal = IvtBal::firstOrCreate(
                [
                    'matl_id'    => $delivDtl->matl_id,
                    'matl_uom'   => $delivDtl->matl_uom,
                    'wh_id'      => $delivDtl->wh_id,
                    'batch_code' => $delivDtl->batch_code,
                ],
                [
                    'matl_code'  => $delivDtl->matl_code,
                    'matl_descr' => $delivDtl->matl_descr,
                    'wh_code'    => $delivDtl->wh_code,
                    'qty_oh'     => 0, // Inisialisasi stok awal
                ]
            );

            // Update qty_oh pada IvtBal
            if ($delivDtl->tr_type == 'PD') {
                $ivtBal->qty_oh += $delta;
            } elseif ($delivDtl->tr_type == 'SD') {
                $ivtBal->qty_oh -= $delta;
            }
            $ivtBal->save();
            $delivDtl->ivt_id = $ivtBal->id;

            // Update IvtBalUnit
            $qtyChange = ($delivDtl->tr_type == 'PD') ? $delta : -$delta;
            $ivtBalUnit = IvtBalUnit::firstOrNew([
                'ivt_id'     => $ivtBal->id,
                'matl_id'    => $delivDtl->matl_id,
                'wh_id'      => $delivDtl->wh_id,
                'batch_code' => $delivDtl->batch_code,
            ]);
            if (!$ivtBalUnit->exists) {
                $ivtBalUnit->unit_code = $delivDtl->matl_uom;
                $ivtBalUnit->qty_oh = 0;
            }
            $ivtBalUnit->qty_oh += $qtyChange;
            $ivtBalUnit->save();

            // Update qty_oh pada MatlUom
            $matlUom = MatlUom::where('matl_id', $delivDtl->matl_id)
                ->where('matl_uom', $delivDtl->matl_uom)
                ->first();
            if ($matlUom) {
                if ($delivDtl->tr_type == 'PD') {
                    $matlUom->qty_oh += $delta;
                    // Kurangi qty_fgr
                    $matlUom->qty_fgr -= $delta;
                } elseif ($delivDtl->tr_type == 'SD') {
                    $matlUom->qty_oh -= $delta;
                    $matlUom->qty_fgi -= $delta;
                }
                $matlUom->save();
            }
        });
    });

    // Event saved: setelah data tersimpan, update IvtLog dan BillingDtl di dalam transaksi
    static::saved(function ($delivDtl) {
        DB::transaction(function () use ($delivDtl) {
            $header = $delivDtl->DelivHdr;   // Ambil data header melalui relasi (pastikan relasi sudah didefinisikan)
            $orderDtl = $delivDtl->OrderDtl;   // Jika diperlukan

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
                    'matl_uom'   => $delivDtl->matl_uom,
                    'wh_id'      => $delivDtl->wh_id,
                    'wh_code'    => $delivDtl->wh_code,
                    'batch_code' => $delivDtl->batch_code,
                    'tr_date'    => $header ? $header->tr_date : null,
                    'qty'        => $delivDtl->qty,
                    'price'      => $orderDtl ? $orderDtl->amt : 0,
                    'amt'        => $delivDtl->qty * ($orderDtl ? $orderDtl->amt : 0),
                    'tr_desc'    => $delivDtl->matl_descr,
                ]
            );

            BillingDtl::updateOrCreate(
                [
                    'trhdr_id' => $delivDtl->trhdr_id,
                    'tr_seq'   => $delivDtl->tr_seq,
                    'tr_type'  => $delivDtl->tr_type == 'SD' ? 'ARB' : 'APB',
                ],
                [
                    'trhdr_id'   => $delivDtl->trhdr_id,
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
    });

    // Event deleting: update IvtBal dan IvtBalUnit dalam transaksi saat data dihapus
    static::deleting(function ($delivDtl) {
        DB::transaction(function () use ($delivDtl) {
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

                $existingBalUnit = IvtBalUnit::where('matl_id', $delivDtl->matl_id)
                    ->where('wh_id', $delivDtl->wh_id)
                    ->lockForUpdate()
                    ->first();
                if ($existingBalUnit) {
                    $newUnitQty = $existingBalUnit->qty_oh + $qtyChange;
                    $existingBalUnit->update(['qty_oh' => $newUnitQty]);
                }
            }

            BillingDtl::where('trhdr_id', $delivDtl->trhdr_id)
                ->where('tr_seq', $delivDtl->tr_seq)
                ->forceDelete();
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
