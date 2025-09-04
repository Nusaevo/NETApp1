<?php

namespace App\Models\TrdRetail1\Transaction;

use App\Models\TrdRetail1\Master\{Material, MatlUom};
use App\Models\Base\BaseModel;
use App\Models\SysConfig1\{ConfigSnum, ConfigConst};
use App\Models\TrdRetail1\Inventories\IvtBal;
use App\Models\TrdRetail1\Inventories\IvtBalUnit;
use App\Models\TrdRetail1\Transaction\{DelivDtl, BillingDtl};
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
use App\Models\TrdRetail1\Inventories\IvtLog;

class OrderDtl extends BaseModel
{
    use SoftDeletes;
    protected static function boot()
    {
        parent::boot();
        static::saving(function ($orderDtl) {
            $qty = $orderDtl->qty ?? 0;
            $price = $orderDtl->price ?? 0;
            $orderDtl->amt = $qty * $price;
        });
        static::saved(function ($orderDtl) {
            $lastOrderDtl = OrderDtl::join('order_hdrs', 'order_dtls.trhdr_id', '=', 'order_hdrs.id')->where('order_dtls.matl_id', $orderDtl->matl_id)->orderBy('order_hdrs.tr_date', 'desc')->select('order_dtls.*')->first();
            if ($lastOrderDtl) {
                $buyingPrice = $lastOrderDtl->price;
                $matlUom = MatlUom::where('matl_id', $orderDtl->matl_id)->first();
                if ($matlUom) {
                    $matlUom->buying_price = $buyingPrice;
                    $matlUom->save();
                }
            }
        });
        static::deleted(function ($orderDtl) {
            $values = $orderDtl->getTrTypeValues($orderDtl->tr_type);

            $delivDtls = DelivDtl::where('tr_id', $orderDtl->tr_id)
                ->where('tr_seq', $orderDtl->tr_seq)
                ->where('tr_type', $values['delivTrType'])
                ->get();
            foreach ($delivDtls as $delivDtl) {
                if (empty($delivDtl->wh_id)) {
                    $warehouse = ConfigConst::where('str1', $delivDtl->wh_code)->first();
                    if ($warehouse) {
                        $delivDtl->wh_id = $warehouse->id;
                    }
                }
                $existingBal = IvtBal::where([
                    'matl_id' => $delivDtl->matl_id,
                    'wh_id' => $delivDtl->wh_id,
                    'matl_uom' => $delivDtl->matl_uom,
                    'batch_code' => $delivDtl->batch_code,
                ])->first();

                if ($existingBal) {
                    $qtyRevert = match ($delivDtl->tr_type) {
                        'PD' => -$delivDtl->qty,  // Purchase Delivery: reduce stock on deletion
                        'RD' => -$delivDtl->qty,  // Return Delivery: reduce stock on deletion (reverse the stock increase)
                        'SD' => $delivDtl->qty,   // Sales Delivery: increase stock on deletion
                        default => 0,
                    };

                    if ($qtyRevert != 0) {
                        $existingBal->increment('qty_oh', $qtyRevert);
                        IvtLog::removeIvtLogIfExists(
                            $delivDtl->trhdr_id,
                            $delivDtl->tr_type,
                            $delivDtl->tr_seq
                        );
                    }
                }

                MatlUom::recalcMatlUomQtyOh($delivDtl->matl_id, $delivDtl->matl_uom);
                $delivDtl->forceDelete();
            }

            BillingDtl::where('tr_id', $orderDtl->tr_id)
                ->where('tr_seq', $orderDtl->tr_seq)
                ->where('tr_type', $values['billingTrType'])
                ->forceDelete();
        });
    }

    protected $fillable = ['tr_id', 'trhdr_id', 'tr_type', 'tr_seq', 'matl_id', 'matl_code', 'matl_descr', 'matl_uom', 'qty', 'qty_reff', 'price', 'amt'];
    /**
     * Decide Deliv & Billing tr_type based on $trType.
     * E.g. if $trType='PO', we might say DelivHdr has 'PD' and BillingHdr has 'APB'.
     * For SOR (Sales Order Return), DelivDtl has 'RD' and BillingDtl has 'ARB'.
     *
     * @param  string $trType
     * @return array  [ 'delivTrType' => 'PD', 'billingTrType' => 'APB' ]
     */
    private function getTrTypeValues($trType)
    {
        if ($trType === 'PO') {
            return [
                'delivTrType' => 'PD',
                'billingTrType' => 'APB',
            ];
        } elseif ($trType === 'SOR') {
            // Sales Order Return/Exchange - uses same delivery/billing types as regular SO
            return [
                'delivTrType' => 'SD',
                'billingTrType' => 'ARB',
            ];
        } else {
            // For 'SO' or other
            return [
                'delivTrType' => 'SD',
                'billingTrType' => 'ARB',
            ];
        }
    }
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
        $values = $this->getTrTypeValues($this->tr_type);
        return $this->hasMany(DelivDtl::class, 'tr_id', 'tr_id')
            ->where('tr_type', $values['delivTrType'])
            ->where('tr_seq', $this->tr_seq);
    }

    public function BillingDtl()
    {
        $values = $this->getTrTypeValues($this->tr_type);
        return $this->hasMany(BillingDtl::class, 'tr_id', 'tr_id')
            ->where('tr_type', $values['billingTrType'])
            ->where('tr_seq', $this->tr_seq);
    }

    #endregion

    public function scopeGetByOrderHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)->where('tr_type', $trType);
    }
}
