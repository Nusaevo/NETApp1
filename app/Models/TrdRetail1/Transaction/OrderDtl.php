<?php

namespace App\Models\TrdRetail1\Transaction;

use App\Models\TrdRetail1\Master\{Material,MatlUom};
use App\Models\Base\BaseModel;
use App\Models\TrdRetail1\Inventories\IvtBal;
use App\Models\TrdRetail1\Inventories\IvtBalUnit;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
class OrderDtl extends BaseModel
{
    use SoftDeletes;

    protected static function boot()
    {
        parent::boot();
        static::saving(function ($orderDtl) {
            $qty = $orderDtl->qty;
            $price = $orderDtl->price;
            $orderDtl->amt = $qty * $price;
        });
        static::saved(function ($orderDtl) {
            $lastOrderDtl = OrderDtl::join('order_hdrs', 'order_dtls.trhdr_id', '=', 'order_hdrs.id')
                ->where('order_dtls.matl_id', $orderDtl->matl_id)
                ->orderBy('order_hdrs.tr_date', 'desc')
                ->select('order_dtls.*')
                ->first();
            if ($lastOrderDtl) {
                $buyingPrice = $lastOrderDtl->price;
                $matlUom = MatlUom::where('matl_id', $orderDtl->matl_id)->first();
                if ($matlUom) {
                    $matlUom->buying_price = $buyingPrice;
                    $matlUom->save();
                }
            }

        });
        static::deleting(function ($orderDtl) {
            DB::beginTransaction();
            try {
                $delivDtls = DelivDtl::where('trhdr_id', $orderDtl->trhdr_id)
                    ->where('tr_seq', $orderDtl->tr_seq)
                    ->get();
                foreach ($delivDtls as $delivDtl) {
                    $existingBal = IvtBal::where('matl_id', $delivDtl->matl_id)
                        ->where('wh_code', $delivDtl->wh_code)
                        ->first();
                    $qtyChange = (float)$delivDtl->qty;
                    if ($delivDtl->tr_type === 'PD') {
                        $qtyChange = -$qtyChange;
                    }
                    if ($existingBal) {
                        $existingBalQty = $existingBal->qty_oh;
                        $newQty = $existingBalQty + $qtyChange;
                        $existingBal->qty_oh = $newQty;
                        $existingBal->save();
                        // Update corresponding record in IvtBalUnit
                        // $existingBalUnit = IvtBalUnit::where('matl_id', $delivDtl->matl_id)
                        //     ->where('wh_id', $delivDtl->wh_code)
                        //     ->first();
                        // if ($existingBalUnit) {
                        //     $existingBalUnitQty = $existingBalUnit->qty_oh;
                        //     $existingBalUnit->qty_oh = $existingBalUnitQty + $qtyChange;
                        //     $existingBalUnit->save();
                        // }
                    }
                    $delivDtl->forceDelete();
                }

                BillingDtl::where('trhdr_id', $orderDtl->trhdr_id)
                    ->where('tr_seq', $orderDtl->tr_seq)
                    ->forceDelete();

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        });
    }

    protected $fillable = [
        'tr_id',
        'trhdr_id',
        'tr_type',
        'tr_seq',
        'matl_id',
        'matl_code',
        'matl_descr',
        'matl_uom',
        'qty',
        'qty_reff',
        'price',
        'amt'
    ];


    #region Relations
    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }

    public function OrderHdr()
    {
        return $this->belongsTo(OrderHdr::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type);
    }

    public function DelivDtl()
    {
        return $this->hasOne(DelivDtl::class, 'reffdtl_id', 'id')
        ->where('tr_type', $this->tr_type);
    }
    #endregion



    public function scopeGetByOrderHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)
                     ->where('tr_type', $trType);
    }

}
