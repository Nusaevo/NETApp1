<?php

namespace App\Models\TrdRetail1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdRetail1\Master\{Partner, Material, MatlUom};
use App\Models\TrdRetail1\Inventories\{IvtBal, IvtBalUnit, IvtLog};
use App\Models\TrdRetail1\Transaction\{DelivDtl, BillingDtl};
use App\Models\SysConfig1\ConfigConst;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
use App\Traits\BaseTrait;
class ReturnDtl extends BaseModel
{
    use SoftDeletes;

    protected static function boot()
    {
        parent::boot();

        // When return detail is saved, calculate amount automatically
        static::saving(function ($returnDtl) {
            $qty = $returnDtl->qty ?? 0;
            $price = $returnDtl->price ?? 0;
            $returnDtl->amt = $qty * $price;
        });

        // When return detail is deleted, reverse the stock increase (reduce stock)
        static::deleted(function ($returnDtl) {
            $values = $returnDtl->getReturnTrTypeValues($returnDtl->tr_type);

            // Find related delivery details (Return Delivery - RD)
            $delivDtls = DelivDtl::where('tr_id', $returnDtl->tr_id)
                ->where('tr_seq', $returnDtl->tr_seq)
                ->where('tr_type', $values['delivTrType'])
                ->get();

            foreach ($delivDtls as $delivDtl) {
                // Ensure warehouse ID is set
                if (empty($delivDtl->wh_id)) {
                    $warehouse = ConfigConst::where('str1', $delivDtl->wh_code)->first();
                    if ($warehouse) {
                        $delivDtl->wh_id = $warehouse->id;
                    }
                }

                // Find existing inventory balance
                $existingBal = IvtBal::where([
                    'matl_id' => $delivDtl->matl_id,
                    'wh_id' => $delivDtl->wh_id,
                    'matl_uom' => $delivDtl->matl_uom,
                    'batch_code' => $delivDtl->batch_code,
                ])->first();

                if ($existingBal) {
                    // For return deletion: REDUCE stock (reverse the return increase)
                    $qtyRevert = match ($delivDtl->tr_type) {
                        'RD' => -$delivDtl->qty,  // Return Delivery deletion reduces stock
                        default => 0,
                    };

                    if ($qtyRevert != 0) {
                        $existingBal->increment('qty_oh', $qtyRevert);

                        // Remove inventory log if exists
                        IvtLog::removeIvtLogIfExists(
                            $delivDtl->trhdr_id,
                            $delivDtl->tr_type,
                            $delivDtl->tr_seq
                        );
                    }
                }

                // Recalculate material UOM quantity on hand
                MatlUom::recalcMatlUomQtyOh($delivDtl->matl_id, $delivDtl->matl_uom);

                // Force delete the delivery detail
                $delivDtl->forceDelete();
            }

            // Delete related billing details
            BillingDtl::where('tr_id', $returnDtl->tr_id)
                ->where('tr_seq', $returnDtl->tr_seq)
                ->where('tr_type', $values['billingTrType'])
                ->forceDelete();
        });
    }

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_id',
        'tr_seq',
        'dlvdtl_id',
        'dlvhdrtr_type',
        'dlvhdrtr_id',
        'dlvdtltr_seq',
        'matl_id',
        'matl_code',
        'matl_uom',
        'matl_descr',
        'qty',
        'qty_uom',
        'qty_base',
        'price',
        'price_uom',
        'price_base',
        'amt',
        'status_code',
        'qty_reff',
    ];

    #region Relations
    public function ReturnHdr()
    {
        return $this->belongsTo(ReturnHdr::class, 'trhdr_id', 'id');
    }

    public function OrderDtl()
    {
        return $this->belongsTo(OrderDtl::class, 'dlvdtl_id', 'id')->where('tr_type', 'SO');;
    }

    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }
    #endregion

    /**
     * Get transaction type values for Return Delivery, Billing, and Payment.
     *
     * @param string $trType
     * @return array Example: ['delivTrType' => 'RD', 'billingTrType' => 'RB']
     */
    private function getReturnTrTypeValues($trType)
    {
        // For Sales Return (SR), related records are Return Delivery (RD), Return Billing (RB)
        return [
            'delivTrType'   => 'RD',  // Return Delivery
            'billingTrType' => 'RB',  // Return Billing
        ];
    }


    public function scopeGetByOrderHdr($query, $id, $trType)
    {
        return $query->where('trhdr_id', $id)
        ->where('tr_type', $trType);;
    }
}
