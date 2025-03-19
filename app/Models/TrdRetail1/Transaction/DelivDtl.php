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

        static::saved(function ($delivDtl) {
            // Ensure defaults are set
            if (empty($delivDtl->batch_code)) {
                $delivDtl->batch_code = "";
            }

            if (empty($delivDtl->wh_id)) {
                $warehouse = ConfigConst::where('str1', $delivDtl->wh_code)->first();
                if ($warehouse) {
                    $delivDtl->wh_id = $warehouse->id;
                }
            }

            // Check if the material ID has changed
            $oldMatlId = $delivDtl->getOriginal('matl_id');
            $matlChanged = ($oldMatlId != $delivDtl->matl_id);

            if ($matlChanged) {
                // Restore inventory for the old material using its original qty
                $oldQty = (float) $delivDtl->getOriginal('qty', 0);

                // Reverse old material's IvtBal record update
                $oldIvtBal = IvtBal::where([
                    'matl_id'    => $oldMatlId,
                    'matl_uom'   => $delivDtl->getOriginal('matl_uom'),
                    'wh_id'      => $delivDtl->wh_id,
                    'batch_code' => $delivDtl->batch_code,
                ])->first();

                if ($oldIvtBal) {
                    if ($delivDtl->tr_type == 'PD') {
                        // Originally added oldQty, so subtract it
                        $oldIvtBal->decrement('qty_oh', $oldQty);
                    } elseif ($delivDtl->tr_type == 'SD') {
                        // Originally subtracted oldQty, so add it back
                        $oldIvtBal->increment('qty_oh', $oldQty);
                    }
                }

                // Reverse old material's MatlUom update
                $oldMatlUom = MatlUom::where([
                    'matl_id'  => $oldMatlId,
                    'matl_uom' => $delivDtl->getOriginal('matl_uom')
                ])->first();

                if ($oldMatlUom) {
                    if ($delivDtl->tr_type == 'PD') {
                        $oldMatlUom->decrement('qty_oh', $oldQty);
                        $oldMatlUom->increment('qty_fgr', $oldQty);
                    } elseif ($delivDtl->tr_type == 'SD') {
                        $oldMatlUom->increment('qty_oh', $oldQty);
                        $oldMatlUom->increment('qty_fgi', $oldQty);
                    }
                }

                // For the new material, treat this as a new entry so use its full new qty
                $delta = (float) $delivDtl->qty;
            } else {
                // No material change: delta is computed as the difference in qty
                $oldQty = (float) $delivDtl->getOriginal('qty', 0);
                $newQty = (float) $delivDtl->qty;
                $delta = $newQty - $oldQty;
            }

            // Update OrderDtl reference quantity if loaded
            if ($delivDtl->relationLoaded('OrderDtl') && $delivDtl->OrderDtl) {
                $delivDtl->OrderDtl->increment('qty_reff', $delta);
            }

            // Update (or create) the IvtBal record for the new material
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
                    'qty_oh'     => 0,
                ]
            );

            // For PD transactions, add new qty; for SD, subtract new qty
            $ivtBal->increment('qty_oh', ($delivDtl->tr_type == 'PD' ? $delta : -$delta));
            $delivDtl->ivt_id = $ivtBal->id;

            // Update (or create) the MatlUom record for the new material
            $matlUom = MatlUom::where([
                'matl_id'  => $delivDtl->matl_id,
                'matl_uom' => $delivDtl->matl_uom
            ])->first();

            if ($matlUom) {
                if ($delivDtl->tr_type == 'PD') {
                    $matlUom->increment('qty_oh', $delta);
                    $matlUom->decrement('qty_fgr', $delta);
                } elseif ($delivDtl->tr_type == 'SD') {
                    $matlUom->decrement('qty_oh', $delta);
                    $matlUom->decrement('qty_fgi', $delta);
                }
            }

            // Update or create IvtLog record as before
            IvtLog::updateOrCreate(
                [
                    'trhdr_id' => $delivDtl->trhdr_id,
                    'tr_type'  => $delivDtl->tr_type,
                    'tr_seq'   => $delivDtl->tr_seq,
                ],
                [
                    'tr_id'     => $delivDtl->tr_id,
                    'trdtl_id'  => $delivDtl->id,
                    'ivt_id'    => $delivDtl->ivt_id,
                    'matl_id'   => $delivDtl->matl_id,
                    'matl_code' => $delivDtl->matl_code,
                    'matl_uom'  => $delivDtl->matl_uom,
                    'wh_id'     => $delivDtl->wh_id,
                    'wh_code'   => $delivDtl->wh_code,
                    'batch_code'=> $delivDtl->batch_code,
                    'tr_date'   => date('Y-m-d'),
                    'qty'       => $delivDtl->qty,
                    'price'     => $delivDtl->OrderDtl->amt ?? 0,
                    'amt'       => $delivDtl->qty * ($delivDtl->OrderDtl->amt ?? 0),
                    'tr_desc'   => $delivDtl->matl_descr,
                ]
            );

            // Update or create BillingDtl record as before
            BillingDtl::updateOrCreate(
                [
                    'trhdr_id' => $delivDtl->trhdr_id,
                    'tr_seq'   => $delivDtl->tr_seq,
                    'tr_type'  => $delivDtl->tr_type == 'SD' ? 'ARB' : 'APB',
                ],
                [
                    'matl_id'     => $delivDtl->matl_id,
                    'matl_code'   => $delivDtl->matl_code,
                    'matl_uom'    => $delivDtl->matl_uom,
                    'descr'       => $delivDtl->matl_descr,
                    'qty'         => $delivDtl->qty,
                    'price'       => $delivDtl->OrderDtl->amt ?? 0,
                    'amt'         => $delivDtl->qty * ($delivDtl->OrderDtl->amt ?? 0),
                    'dlvdtl_id'   => $delivDtl->id,
                    'dlvdtlr_seq' => $header->tr_seq ?? $delivDtl->tr_seq,
                    'dlvhdr_type' => $header->tr_type ?? $delivDtl->tr_type,
                    'dlvhdr_id'   => $header->id ?? $delivDtl->trhdr_id,
                ]
            );
        });

        static::deleting(function ($delivDtl) {
            if (empty($delivDtl->wh_id)) {
                $warehouse = ConfigConst::where('str1', $delivDtl->wh_code)->first();
                if ($warehouse) {
                    $delivDtl->wh_id = $warehouse->id;
                }
            }

            $existingBal = IvtBal::where([
                'matl_id'    => $delivDtl->matl_id,
                'wh_id'      => $delivDtl->wh_id,
                'batch_code' => $delivDtl->batch_code,
            ])->first();

            if ($existingBal) {
                $qtyChange = ($delivDtl->tr_type == 'PD' ? -$delivDtl->qty : ($delivDtl->tr_type == 'SD' ? 0 : $delivDtl->qty));
                $existingBal->decrement('qty_oh', $qtyChange);
            }

            BillingDtl::where('trhdr_id', $delivDtl->trhdr_id)
                ->where('tr_seq', $delivDtl->tr_seq)
                ->forceDelete();
        });
    }



    protected $fillable = ['trhdr_id', 'tr_type', 'tr_id', 'tr_seq', 'reffdtl_id', 'reffhdrtr_type', 'reffhdrtr_id', 'reffdtltr_seq', 'matl_id', 'matl_code', 'matl_uom', 'matl_descr', 'wh_code', 'qty', 'qty_reff', 'status_code'];
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
    public function IvtBal()
    {
        return $this->hasOne(IvtBal::class, 'matl_id', 'matl_id')->where('wh_id', $this->wh_id);
    }
    #endregion
}
