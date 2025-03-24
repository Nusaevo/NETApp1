<?php

namespace App\Models\TrdRetail1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdRetail1\Master\{Material, MatlUom};
use App\Models\TrdRetail1\Inventories\{IvtBal, IvtBalUnit, IvtLog};
use App\Models\SysConfig1\ConfigConst;
use Illuminate\Database\Eloquent\SoftDeletes;

class DelivDtl extends BaseModel
{
    use SoftDeletes;
    protected static function boot()
    {
        parent::boot();
        /**
         * Event "saved"
         */
        static::saved(function ($delivDtl) {
            // --- 1. Ambil data lama dan baru ---
            $oldMatlId = $delivDtl->getOriginal('matl_id');
            $oldMatlUom = $delivDtl->getOriginal('matl_uom');
            $oldQty = (float) $delivDtl->getOriginal('qty', 0);

            $newMatlId = $delivDtl->matl_id;
            $newMatlUom = $delivDtl->matl_uom;
            $newQty = (float) $delivDtl->qty;

            // Cek apakah kombinasi (matl_id, matl_uom) berubah
            $combinationChanged = $oldMatlId != $newMatlId || $oldMatlUom != $newMatlUom;

            // --- 2. Jika kombinasi berubah, kembalikan stok lama (revert) ---
            if ($combinationChanged && $oldQty != 0) {
                $oldIvtBal = IvtBal::where([
                    'matl_id' => $oldMatlId,
                    'matl_uom' => $oldMatlUom,
                    'wh_id' => $delivDtl->wh_id,
                ])->first();

                if ($oldIvtBal) {
                    // Delta revert untuk stok lama
                    $delta = $delivDtl->tr_type === 'PD' ? -$oldQty : $oldQty;

                    // Lakukan penyesuaian
                    $oldIvtBal->increment('qty_oh', $delta);

                    // Buat log revert
                    self::createIvtLog(
                        'DEL', // Tipe log
                        $delivDtl,
                        $oldIvtBal->id,
                        $oldMatlId,
                        $oldMatlUom,
                        $delta,
                        $delivDtl->tr_type == 'PD' ? 'Reverse old PD stock' : 'Reverse old SD stock',
                    );
                }

                // Revert MatlUom lama jika perlu (qty_fgr/qty_fgi dsb.)
                $oldMatlUomRec = MatlUom::where([
                    'matl_id' => $oldMatlId,
                    'matl_uom' => $oldMatlUom,
                ])->first();

                if ($oldMatlUomRec) {
                    if ($delivDtl->tr_type == 'PD') {
                        // Contoh: kembalikan qty_fgr
                        $oldMatlUomRec->increment('qty_fgr', $oldQty);
                    } elseif ($delivDtl->tr_type == 'SD') {
                        // Contoh: kembalikan qty_fgi
                        $oldMatlUomRec->decrement('qty_fgi', $oldQty);
                    }

                    // Recalc total oh lintas gudang
                    self::recalcMatlUomQtyOh($oldMatlId, $oldMatlUom);
                }
            }

            // --- 3. Hitung delta stok baru ---
            $delta = 0;
            if ($combinationChanged) {
                // Jika kombinasi berubah, delta stok baru = full newQty
                $delta = $newQty;
            } else {
                // Jika kombinasi sama, delta = (qty baru - qty lama)
                $delta = $newQty - $oldQty;
            }

            // --- 4. Update stok baru hanya jika ada selisih ---
            if ($delta != 0) {
                // A. Cari atau buat IvtBal (kombinasi baru)
                $ivtBal = IvtBal::firstOrCreate(
                    [
                        'matl_id' => $newMatlId,
                        'matl_uom' => $newMatlUom,
                        'wh_id' => $delivDtl->wh_id,
                        'wh_code' => $delivDtl->wh_code,
                    ],
                    [
                        'matl_code' => $delivDtl->matl_code,
                        'matl_descr' => $delivDtl->matl_descr,
                        'qty_oh' => 0,
                    ],
                );

                // B. Tambah/kurangi stok tergantung PD atau SD
                $adjustment = $delivDtl->tr_type == 'PD' ? $delta : -$delta;
                $ivtBal->increment('qty_oh', $adjustment);

                // C. Update ivt_id di $delivDtl tanpa memicu event
                $delivDtl->ivt_id = $ivtBal->id;
                $delivDtl->saveQuietly();

                // D. Update MatlUom baru (recalc qty_oh)
                self::recalcMatlUomQtyOh($newMatlId, $newMatlUom);

                // E. Buat/Update IvtLog untuk transaksi baru
                IvtLog::updateOrCreate(
                    [
                        'trhdr_id' => $delivDtl->trhdr_id,
                        'tr_type' => $delivDtl->tr_type,
                        'tr_seq' => $delivDtl->tr_seq,
                    ],
                    [
                        'tr_id' => $delivDtl->tr_id,
                        'trdtl_id' => $delivDtl->id,
                        'ivt_id' => $delivDtl->ivt_id,
                        'matl_id' => $newMatlId,
                        'matl_code' => $delivDtl->matl_code,
                        'matl_uom' => $newMatlUom,
                        'wh_id' => $delivDtl->wh_id,
                        'wh_code' => $delivDtl->wh_code,
                        'batch_code' => $delivDtl->batch_code ?? '',
                        'tr_date' => date('Y-m-d'),
                        'qty' => $newQty,
                        'price' => $delivDtl->OrderDtl->amt ?? 0,
                        'amt' => $newQty * ($delivDtl->OrderDtl->amt ?? 0),
                        'tr_desc' => $delivDtl->matl_descr,
                    ],
                );
            }

            // --- 5. Update qty_reff di OrderDtl jika perlu ---
            if ($delivDtl->relationLoaded('OrderDtl') && $delivDtl->OrderDtl && $delta != 0) {
                $delivDtl->OrderDtl->increment('qty_reff', $delta);
            }
        });

        /**
         * Event "deleting"
         */
        static::deleting(function ($delivDtl) {
            // Pastikan wh_id terisi
            if (empty($delivDtl->wh_id)) {
                $warehouse = ConfigConst::where('str1', $delivDtl->wh_code)->first();
                if ($warehouse) {
                    $delivDtl->wh_id = $warehouse->id;
                }
            }

            // Ambil IvtBal
            $existingBal = IvtBal::where([
                'matl_id' => $delivDtl->matl_id,
                'wh_id' => $delivDtl->wh_id,
                'matl_uom' => $delivDtl->matl_uom,
                'batch_code' => $delivDtl->batch_code,
            ])->first();

            if ($existingBal) {
                // Hitung qtyRevert
                $qtyRevert = 0;
                if ($delivDtl->tr_type == 'PD') {
                    $qtyRevert = -$delivDtl->qty; // PD => dulu stok bertambah => hapus => stok berkurang
                } elseif ($delivDtl->tr_type == 'SD') {
                    $qtyRevert = $delivDtl->qty; // SD => dulu stok berkurang => hapus => stok bertambah
                }

                // Jika perlu penyesuaian stok
                if ($qtyRevert != 0) {
                    $existingBal->increment('qty_oh', $qtyRevert);

                    // Buat log "delete" / "revert"
                    self::createIvtLog('DEL', $delivDtl, $existingBal->id, $delivDtl->matl_id, $delivDtl->matl_uom, $qtyRevert, 'Revert stock on deleting DeliveryDetail');
                }
            }

            // Recalc MatlUom total oh
            self::recalcMatlUomQtyOh($delivDtl->matl_id, $delivDtl->matl_uom);
        });
    }
    // ------------------------------------------------------------------------------
    //                            HELPER METHODS
    // ------------------------------------------------------------------------------

    /**
     * Recalc qty_oh di MatlUom berdasarkan sum dari IvtBal.
     */
    private static function recalcMatlUomQtyOh($matlId, $matlUom)
    {
        $matlUomRec = MatlUom::where([
            'matl_id' => $matlId,
            'matl_uom' => $matlUom,
        ])->first();

        if ($matlUomRec) {
            $sumOh = IvtBal::where('matl_id', $matlId)->where('matl_uom', $matlUom)->sum('qty_oh');

            $matlUomRec->qty_oh = $sumOh;
            $matlUomRec->save();
        }
    }

    /**
     * Buat log IvtLog dengan parameter standar, supaya tidak duplikasi kode.
     */
    private static function createIvtLog(string $type, $delivDtl, int $ivtBalId, int $matlId, string $matlUom, float $qty, string $desc = '')
    {
        return IvtLog::create([
            'trhdr_id' => $delivDtl->trhdr_id,
            'tr_type' => $type,
            'tr_seq' => $delivDtl->tr_seq,
            'tr_id' => $delivDtl->tr_id,
            'trdtl_id' => $delivDtl->id,
            'ivt_id' => $ivtBalId,
            'matl_id' => $matlId,
            'matl_code' => $delivDtl->matl_code,
            'matl_uom' => $matlUom,
            'wh_id' => $delivDtl->wh_id,
            'wh_code' => $delivDtl->wh_code,
            'batch_code' => $delivDtl->batch_code ?? '',
            'tr_date' => date('Y-m-d'),
            'qty' => $qty,
            'price' => 0,
            'amt' => 0,
            'tr_desc' => $desc,
        ]);
    }

    protected $fillable = ['trhdr_id', 'tr_type', 'tr_id', 'tr_seq', 'reffdtl_id', 'reffhdrtr_type', 'reffhdrtr_id', 'reffdtltr_seq', 'matl_id', 'matl_code', 'matl_uom', 'matl_descr', 'wh_id', 'wh_code', 'qty', 'qty_reff', 'status_code'];
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
        if ($this->tr_type) {
            return $this->belongsTo(DelivHdr::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type);
        }
        return null;
    }
    public function OrderDtl()
    {
        return $this->belongsTo(OrderDtl::class, 'reffdtl_id', 'id')->where('tr_type', $this->reffhdrtr_type);
    }
    public function IvtBal()
    {
        return $this->hasOne(IvtBal::class, 'matl_id', 'matl_id')->where('wh_id', $this->wh_id);
    }
    #endregion
}
