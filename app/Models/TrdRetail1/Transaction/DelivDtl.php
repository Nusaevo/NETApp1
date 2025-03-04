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
            if (empty($delivDtl->batch_code)) {
                $delivDtl->batch_code = date('y/m/d');
            }

            if (empty($delivDtl->wh_id)) {
                $warehouse = ConfigConst::where('str1', $delivDtl->wh_code)->first();
                if ($warehouse) {
                    $delivDtl->wh_id = $warehouse->id;
                }
            }

            $oldQty = (float) $delivDtl->getOriginal('qty', 0);
            $newQty = (float) $delivDtl->qty;
            $delta = $newQty - $oldQty;

            if ($delivDtl->relationLoaded('OrderDtl') && $delivDtl->OrderDtl) {
                $delivDtl->OrderDtl->increment('qty_reff', $delta);
            }

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

            $ivtBal->increment('qty_oh', ($delivDtl->tr_type == 'PD' ? $delta : -$delta));
            $delivDtl->ivt_id = $ivtBal->id;

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

            $orderDtl = $delivDtl->OrderDtl;

            IvtLog::updateOrCreate(
                [
                    'trhdr_id' => $delivDtl->trhdr_id,
                    'tr_type'  => $delivDtl->tr_type,
                    'tr_seq'   => $delivDtl->tr_seq,
                ],
                [
                    'tr_id'    => $delivDtl->tr_id,
                    'trdtl_id'   => $delivDtl->id,
                    'ivt_id'     => $delivDtl->ivt_id,
                    'matl_id'    => $delivDtl->matl_id,
                    'matl_code'  => $delivDtl->matl_code,
                    'matl_uom'   => $delivDtl->matl_uom,
                    'wh_id'      => $delivDtl->wh_id,
                    'wh_code'    => $delivDtl->wh_code,
                    'batch_code' => $delivDtl->batch_code,
                    'tr_date'    => date('Y-m-d'),
                    'qty'        => $delivDtl->qty,
                    'price'      => $orderDtl->amt ?? 0,
                    'amt'        => $delivDtl->qty * ($orderDtl->amt ?? 0),
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
                    'matl_id'    => $delivDtl->matl_id,
                    'matl_code'  => $delivDtl->matl_code,
                    'matl_uom'   => $delivDtl->matl_uom,
                    'descr'      => $delivDtl->matl_descr,
                    'qty'        => $delivDtl->qty,
                    'price'      => $orderDtl->amt ?? 0,
                    'amt'        => $delivDtl->qty * ($orderDtl->amt ?? 0),
                    'dlvdtl_id'  => $delivDtl->id,
                    'dlvdtlr_seq' => $header->tr_seq ?? $delivDtl->tr_seq,
                    'dlvhdr_type' => $header->tr_type ?? $delivDtl->tr_type,
                    'dlvhdr_id'  => $header->id ?? $delivDtl->trhdr_id,
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
