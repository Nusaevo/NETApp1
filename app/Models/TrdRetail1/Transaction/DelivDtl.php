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
            $oldMatlId = $delivDtl->getOriginal('matl_id');
            $oldMatlUom = $delivDtl->getOriginal('matl_uom');
            $oldQty = (float) $delivDtl->getOriginal('qty', 0);

            $newMatlId = $delivDtl->matl_id;
            $newMatlUom = $delivDtl->matl_uom;
            $newQty = (float) $delivDtl->qty;

            $combinationChanged = $oldMatlId != $newMatlId || $oldMatlUom != $newMatlUom;

            if ($combinationChanged && $oldQty != 0) {
                $oldIvtBal = IvtBal::where([
                    'matl_id' => $oldMatlId,
                    'matl_uom' => $oldMatlUom,
                    'wh_id' => $delivDtl->wh_id,
                ])->first();

                if ($oldIvtBal) {
                    $delta = $delivDtl->tr_type === 'PD' ? -$oldQty : $oldQty;

                    $oldIvtBal->increment('qty_oh', $delta);

                    // Hapus log lama
                    IvtLog::removeIvtLogIfExists($delivDtl->trhdr_id, $delivDtl->tr_type, $delivDtl->tr_seq);
                }

                $oldMatlUomRec = MatlUom::where([
                    'matl_id' => $oldMatlId,
                    'matl_uom' => $oldMatlUom,
                ])->first();

                if ($oldMatlUomRec) {
                    if ($delivDtl->tr_type == 'PD') {
                        $oldMatlUomRec->increment('qty_fgr', $oldQty);
                    } elseif ($delivDtl->tr_type == 'SD') {
                        $oldMatlUomRec->decrement('qty_fgi', $oldQty);
                    }

                    MatlUom::recalcMatlUomQtyOh($oldMatlId, $oldMatlUom);
                }
            }

            $delta = $combinationChanged ? $newQty : $newQty - $oldQty;

            if ($delta != 0) {
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

                $adjustment = $delivDtl->tr_type == 'PD' ? $delta : -$delta;
                $ivtBal->increment('qty_oh', $adjustment);

                $delivDtl->ivt_id = $ivtBal->id;
                $delivDtl->saveQuietly();

                MatlUom::recalcMatlUomQtyOh($newMatlId, $newMatlUom);

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

            if ($delivDtl->relationLoaded('OrderDtl') && $delivDtl->OrderDtl && $delta != 0) {
                $delivDtl->OrderDtl->increment('qty_reff', $delta);
            }
        });

        // static::deleting(function ($delivDtl) {
        //     if (empty($delivDtl->wh_id)) {
        //         $warehouse = ConfigConst::where('str1', $delivDtl->wh_code)->first();
        //         if ($warehouse) {
        //             $delivDtl->wh_id = $warehouse->id;
        //         }
        //     }

        //     $existingBal = IvtBal::where([
        //         'matl_id' => $delivDtl->matl_id,
        //         'wh_id' => $delivDtl->wh_id,
        //         'matl_uom' => $delivDtl->matl_uom,
        //         'batch_code' => $delivDtl->batch_code,
        //     ])->first();

        //     if ($existingBal) {
        //         $qtyRevert = 0;
        //         if ($delivDtl->tr_type == 'PD') {
        //             $qtyRevert = -$delivDtl->qty;
        //         } elseif ($delivDtl->tr_type == 'SD') {
        //             $qtyRevert = $delivDtl->qty;
        //         }

        //         if ($qtyRevert != 0) {
        //             $existingBal->increment('qty_oh', $qtyRevert);

        //             // Hapus log lama
        //             self::removeIvtLogIfExists($delivDtl->trhdr_id, $delivDtl->tr_type, $delivDtl->tr_seq);
        //         }
        //     }

        //     self::recalcMatlUomQtyOh($delivDtl->matl_id, $delivDtl->matl_uom);
        // });
    }
    // ------------------------------------------------------------------------------
    //                            HELPER METHODS
    // ------------------------------------------------------------------------------

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
