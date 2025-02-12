<?php

namespace App\Models\TrdRetail1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdRetail1\Master\Material;
use App\Models\TrdRetail1\Inventories\IvtBal;
use App\Models\TrdRetail1\Inventories\IvtBalUnit;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use App\Enums\Constant;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdRetail1\Inventories\IvtLog;
use App\Traits\BaseTrait;
class DelivDtl extends BaseModel
{
    use SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        // Event saving: update IvtBal dan IvtBalUnit
        static::saving(function ($delivDtl) {
            if (empty($delivDtl->matl_uom)) {
                $delivDtl->matl_uom;
            }
            $warehouse = ConfigConst::where('str1', $delivDtl->wh_code)->first();
            if ($warehouse) {
                $delivDtl->wh_id = $warehouse->id;
            }

            DB::transaction(function () use ($delivDtl) {
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
                    ],
                    [
                        'matl_code'  => $delivDtl->matl_code,
                        'matl_descr' => $delivDtl->matl_descr,
                        'wh_code'    => $delivDtl->wh_code,
                        'qty_oh'     => 0, // Inisialisasi stok awal
                    ]
                );

                // Update qty_oh pada IvtBal menggunakan properti model dan simpan dengan save()
                if ($delivDtl->tr_type == 'PD') {
                    $ivtBal->qty_oh = $ivtBal->qty_oh + $delta;
                } elseif ($delivDtl->tr_type == 'SD') {
                    $ivtBal->qty_oh = $ivtBal->qty_oh - $delta;
                }
                $ivtBal->save();
                $delivDtl->ivt_id = $ivtBal->id;

                // Update IvtBalUnit
                $qtyChange = ($delivDtl->tr_type == 'PD') ? $delta : -$delta;
                $ivtBalUnit = IvtBalUnit::firstOrNew([
                    'ivt_id'     => $ivtBal->id,
                    'matl_id'    => $delivDtl->matl_id,
                    'wh_id'      => $delivDtl->wh_id,
                ]);
                if (!$ivtBalUnit->exists) {
                    $ivtBalUnit->unit_code = $delivDtl->matl_uom;
                    $ivtBalUnit->qty_oh = 0;
                }
                $ivtBalUnit->qty_oh += $qtyChange;
                $ivtBalUnit->save();
            });
        });

        // Event saved: data sudah tersimpan, ID sudah tersedia. Buat atau update IvtLog.
        static::saved(function ($delivDtl) {
            $header = $delivDtl->DelivHdr;   // Ambil data header melalui relasi (pastikan relasi ini sudah didefinisikan)
            $orderDtl = $delivDtl->OrderDtl;   // Jika diperlukan

            IvtLog::updateOrCreate(
                [
                    // Kriteria unik untuk menemukan record log yang sudah ada
                    'trhdr_id' => $header ? $header->id : $delivDtl->trhdr_id,
                    'tr_type'  => $header ? $header->tr_type : $delivDtl->tr_type,
                    'tr_seq'   => $delivDtl->tr_seq,
                ],
                [
                    // Data yang akan disimpan atau diupdate
                    'tr_code'    => $header ? $header->tr_code : $delivDtl->tr_code,
                    'trdtl_id'   => $delivDtl->id, // ID DelivDtl sudah tersedia
                    'ivt_id'     => $delivDtl->ivt_id,
                    'matl_id'    => $delivDtl->matl_id,
                    'matl_uom'   => $delivDtl->matl_uom,
                    'wh_id'      => $delivDtl->wh_id,
                    'tr_date'    => $header ? $header->tr_date : null,
                    'qty'        => $delivDtl->qty,
                    'price'      => $orderDtl ? $orderDtl->amt : 0,
                    'amt'        => $delivDtl->qty * ($orderDtl ? $orderDtl->amt : 0),
                    'tr_desc'    => $delivDtl->matl_descr,
                ]
            );
        });

        // Event deleting (tidak berubah)
        static::deleting(function ($delivDtl) {
            DB::beginTransaction();
            try {
                $delivDtls = DelivDtl::where('trhdr_id', $delivDtl->trhdr_id)
                    ->where('tr_seq', $delivDtl->tr_seq)
                    ->get();

                foreach ($delivDtls as $dtl) {
                    $warehouse = ConfigConst::where('str1', $dtl->wh_code)->first();
                    if ($warehouse) {
                        $dtl->wh_id = $warehouse->id;
                    }

                    $existingBal = IvtBal::where('matl_id', $dtl->matl_id)
                        ->where('wh_id', $dtl->wh_id)
                        ->lockForUpdate()
                        ->first();

                    $qtyChange = (float)$dtl->qty;
                    if ($dtl->tr_type === 'PD') {
                        $qtyChange = -$qtyChange;
                    }

                    if ($existingBal) {
                        $newQty = $existingBal->qty_oh - $qtyChange;
                        $existingBal->update(['qty_oh' => $newQty]);

                        $existingBalUnit = IvtBalUnit::where('matl_id', $dtl->matl_id)
                            ->where('wh_id', $dtl->wh_id)
                            ->lockForUpdate()
                            ->first();
                        if ($existingBalUnit) {
                            $newUnitQty = $existingBalUnit->qty_oh - $qtyChange;
                            $existingBalUnit->update(['qty_oh' => $newUnitQty]);
                        }
                    }
                    $dtl->forceDelete();
                }

                BillingDtl::where('trhdr_id', $delivDtl->trhdr_id)
                    ->where('tr_seq', $delivDtl->tr_seq)
                    ->forceDelete();

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        });
    }

    protected $fillable = ['trhdr_id', 'tr_type', 'tr_id', 'tr_seq', 'reffdtl_id', 'reffhdrtr_type', 'reffhdrtr_id', 'reffdtltr_seq', 'matl_id', 'matl_code', 'matl_uom', 'matl_descr', 'wh_id', 'qty', 'qty_reff', 'status_code'];
    public function scopeGetByOrderHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)->where('tr_type', $trType);
    }

    #region Relations

    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }

    public function DelivHdr()
    {
        return $this->belongsTo(DelivHdr::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type);
    }

    public function IvtBal()
    {
        return $this->hasOne(IvtBal::class, 'matl_id', 'matl_id')->where('wh_id', $this->wh_id);
    }
    #endregion
}
