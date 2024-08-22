<?php

namespace App\Models\TrdJewel1\Transaction;

use App\Models\TrdJewel1\Base\TrdJewel1BaseModel;
use App\Models\TrdJewel1\Master\Partner;
use App\Models\TrdJewel1\Master\Material;
use App\Enums\Status;
use App\Models\SysConfig1\ConfigSnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;

class OrderHdr extends TrdJewel1BaseModel
{
    use SoftDeletes;
    protected $connection;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = Constant::Trdjewel1_ConnectionString();
    }
    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($orderHdr) {
            // Delete related DelivHdr and its DelivDtl
            $delivHdr = $orderHdr->DelivHdr;
            if ($delivHdr) {
                foreach ($delivHdr->DelivDtl as $delivDtl) {
                    $delivDtl->delete(); // Delete each DelivDtl
                }
                $delivHdr->delete(); // Delete the DelivHdr after its DelivDtl records are deleted
            }

            // Delete related BillingHdr and its BillingDtl
            $billingHdr = $orderHdr->BillingHdr;
            if ($billingHdr) {
                foreach ($billingHdr->BillingDtl as $billingDtl) {
                    $billingDtl->delete(); // Delete each BillingDtl
                }
                $billingHdr->delete(); // Delete the BillingHdr after its BillingDtl records are deleted
            }

            // Delete related OrderDtl records
            foreach ($orderHdr->OrderDtl as $orderDtl) {
                $orderDtl->delete(); // Delete each OrderDtl
            }
        });


        static::retrieved(function ($model) {

            if (array_key_exists('goldprice_curr', $model->attributes)) {
                $model->goldprice_curr = numberFormat($model->attributes['goldprice_curr'], 2);
            }
        });
    }

    public function getTrTypeValues($trType)
    {
        if ($trType == "PO") {
            return [
                'delivTrType' => "PD",
                'billingTrType' => "APB"
            ];
        } else {
            return [
                'delivTrType' => "SD",
                'billingTrType' => "ARB"
            ];
        }
    }

    protected $fillable = [
        'tr_id',
        'tr_type',
        'tr_date',
        'reff_code',
        'partner_id',
        'partner_code',
        'sales_id',
        'sales_code',
        'deliv_by',
        'payment_term_id',
        'payment_term',
        'curr_id',
        'curr_code',
        'curr_rate',
        'status_code',
        'print_settings',
        'print_remarks',
    ];

    #region Relations
    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function OrderDtl()
    {
        return $this->hasMany(OrderDtl::class, 'tr_id', 'tr_id')->where('tr_type', $this->tr_type);
    }

    public function Materials()
    {
        return $this->hasManyThrough(Material::class, OrderDtl::class, 'tr_id', 'id', 'tr_id', 'matl_id')
            ->where('order_dtls.tr_type', $this->tr_type);
    }

    public function DelivHdr()
    {
        $values = $this->getTrTypeValues($this->tr_type);
        return $this->hasOne(DelivHdr::class, 'tr_id', 'tr_id')->where('tr_type', $values['delivTrType']);
    }

    public function BillingHdr()
    {
        $values = $this->getTrTypeValues($this->tr_type);
        return $this->hasOne(BillingHdr::class, 'tr_id', 'tr_id')->where('tr_type', $values['billingTrType']);
    }
    #endregion

    #region Attributes
    public function getTotalQtyAttribute()
    {
        return currencyToNumeric($this->OrderDtl()->sum('qty'));
    }

    public function getTotalAmtAttribute()
    {
        return currencyToNumeric($this->OrderDtl()->sum('amt'));
    }

    public function getMatlCodesAttribute()
    {
        $matlCodes = $this->OrderDtl()->pluck('matl_code')->toArray();
        return implode(', ', $matlCodes);
    }
    #endregion


    public static function getByCreatedByAndTrType($createdBy, $trType)
    {
        return self::where('created_by', $createdBy)->where('tr_type', $trType)->get();
    }


    public function isOrderCompleted(): bool
    {
        if ($this->status_code == Status::COMPLETED) {
            return true;
        }
        return false;
    }

    public function isItemHasOrderedMaterial(): bool
    {
        foreach ($this->OrderDtl as $orderDtl) {
            if ($orderDtl->Material && $orderDtl->Material->isOrderedMaterial()) {
                return true;
            }
        }
        return false;
    }

    public function isOrderEnableToDelete(): bool
    {
        if ($this->tr_type == 'PO') {
            foreach ($this->OrderDtl as $orderDtl) {
                $relatedOrderDtl = OrderDtl::where('matl_id', $orderDtl->matl_id)
                    ->where('tr_type', 'SO')
                    ->where('tr_id', '!=', $this->tr_id)
                    ->first();

                if ($relatedOrderDtl) {
                    return false;
                }
            }
        }

        if ($this->tr_type == 'SO') {
            foreach ($this->OrderDtl as $orderDtl) {
                $relatedOrderDtl = OrderDtl::where('matl_id', $orderDtl->matl_id)
                    ->where('tr_type', 'BB')
                    ->where('tr_id', '!=', $this->tr_id)
                    ->first();

                if ($relatedOrderDtl) {
                    return false;
                }
            }
        }
        return true;
    }


    public function isItemHasPurchaseOrder(int $matl_id): bool
    {
        $relatedOrderDtl = OrderDtl::where('matl_id', $matl_id)
            ->where('tr_type', 'PO')
            ->where('tr_id', '!=', $this->tr_id)
            ->first();

        if ($relatedOrderDtl) {
            return true;
        }

        return false;
    }

    public function isItemHasSalesOrder(int $matl_id): bool
    {
        $relatedOrderDtl = OrderDtl::where('matl_id', $matl_id)
            ->where('tr_type', 'SO')
            ->where('tr_id', '!=', $this->tr_id)
            ->first();

        if ($relatedOrderDtl) {
            return true;
        }

        return false;
    }

    public function isItemHasBuyback(int $matl_id): bool
    {
        $relatedOrderDtl = ReturnDtl::where('matl_id', $matl_id)
            ->where('tr_type', 'BB')
            ->where('tr_id', '!=', $this->dlvhdrtr_id)
            ->first();

        if ($relatedOrderDtl) {
            return true;
        }

        return false;
    }

    /**
     * Saves all details related to the purchase order, including delivery and billing information.
     *
     * @param array $inputs The main input data for the purchase order.
     * @param array $inputDetails Details for each line item in the purchase order.
     */
    public function saveOrder($appCode, $trType, $inputs, $input_details, $createBillingDelivery = false)
    {
        DB::beginTransaction();
        try {
            list($delivTrType, $billingTrType, $code) = $this->initializeTransactionTypes($trType);

            $this->fillAndSanitize($inputs);
            $this->generateTransactionId($appCode, $code);

            if ($this->isNew()) {
                $this->status_code = Status::OPEN;
            }
            $this->save();

            if ($createBillingDelivery) {
                $this->createDeliveryAndBillingHeaders($delivTrType, $billingTrType, $inputs);
            }

            $this->saveOrderDetails($input_details, $trType, $inputs, $createBillingDelivery, $delivTrType, $billingTrType);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function initializeTransactionTypes($trType)
    {
        if ($trType == "PO") {
            return ["PD", "APB", "PURCHORDER_LASTID"];
        } else {
            return ["SD", "ARB", "SALESORDER_LASTID"];
        }
    }

    private function generateTransactionId($appCode, $code)
    {
        if ($this->tr_id === null || $this->tr_id == 0) {
            $configSnum = ConfigSnum::where('app_code', '=', $appCode)
                ->where('code', '=', $code)
                ->first();
            if ($configSnum != null) {
                $stepCnt = $configSnum->step_cnt;
                $proposedTrId = $configSnum->last_cnt + $stepCnt;
                if ($proposedTrId > $configSnum->wrap_high) {
                    $proposedTrId = $configSnum->wrap_low;
                }
                $proposedTrId = max($proposedTrId, $configSnum->wrap_low);
                $configSnum->last_cnt = $proposedTrId;
                $this->tr_id = $proposedTrId;
                $configSnum->save();
            }
        }
    }

    private function createDeliveryAndBillingHeaders($delivTrType, $billingTrType, $inputs)
    {
        $this->createDeliveryHeader($delivTrType, $inputs);
        $this->createBillingHeader($billingTrType);
    }

    private function createDeliveryHeader($delivTrType, $inputs)
    {
        $delivHdr = DelivHdr::firstOrNew(['tr_id' => $this->tr_id, 'tr_type' => $delivTrType]);
        $delivHdr->fillAndSanitize([
            'tr_id' => $this->tr_id,
            'tr_type' => $delivTrType,
            'tr_date' => $this->tr_date,
            'partner_id' => $this->partner_id,
            'partner_code' => $this->partner_code,
            'deliv_by' => $inputs['deliv_by'] ?? '',
        ]);
        if ($delivHdr->isNew()) {
            $delivHdr->status_code = Status::OPEN;
        }
        $delivHdr->save();
    }

    private function createBillingHeader($billingTrType)
    {
        $billingHdr = BillingHdr::firstOrNew(['tr_id' => $this->tr_id, 'tr_type' => $billingTrType]);
        $billingHdr->fillAndSanitize([
            'tr_id' => $this->tr_id,
            'tr_type' => $billingTrType,
            'tr_date' => $this->tr_date,
            'partner_id' => $this->partner_id,
            'partner_code' => $this->partner_code,
            'payment_term_id' => $this->payment_term_id,
            'payment_term' => '',
            'payment_due_days' => 0,
        ]);
        if ($billingHdr->isNew()) {
            $billingHdr->status_code = Status::OPEN;
        }
        $billingHdr->save();
    }

    private function saveOrderDetails($input_details, $trType, $inputs, $createBillingDelivery, $delivTrType, $billingTrType)
    {
        foreach ($input_details as $index => $inputDetail) {
            $orderDtl = $this->createOrUpdateOrderDetail($inputDetail, $trType);

            if ($createBillingDelivery) {
                $this->createDeliveryDetail($orderDtl, $inputs, $delivTrType);
                $this->createBillingDetail($orderDtl, $billingTrType);
            }
        }
    }

    private function createOrUpdateOrderDetail($inputDetail, $trType)
    {
        $orderDtl = OrderDtl::firstOrNew([
            'tr_id' => $this->tr_id,
            'tr_seq' => $inputDetail['tr_seq'],
        ]);

        $inputDetail['tr_id'] = $this->tr_id;
        $inputDetail['tr_seq'] = $inputDetail['tr_seq'];
        $inputDetail['trhdr_id'] = $this->id;
        $inputDetail['qty_reff'] = $inputDetail['qty'];
        $inputDetail['tr_type'] = $trType;
        $orderDtl->fillAndSanitize($inputDetail);
        if ($orderDtl->isNew()) {
            $orderDtl->status_code = Status::OPEN;
        }
        $orderDtl->save();

        return $orderDtl;
    }

    private function createDeliveryDetail($orderDtl, $inputs, $delivTrType)
    {
        $delivDtl = DelivDtl::firstOrNew([
            'trhdr_id' => $orderDtl->trhdr_id,
            'tr_seq' => $orderDtl->tr_seq,
            'tr_type' => $delivTrType,
        ]);
        $delivDtl->fillAndSanitize([
            'trhdr_id' => $orderDtl->trhdr_id,
            'tr_type' => $delivTrType,
            'tr_id' => $this->tr_id,
            'tr_seq' => $orderDtl->tr_seq,
            'reffdtl_id' => $orderDtl->id,
            'reffhdrtr_type' => $orderDtl->tr_type,
            'reffhdrtr_id' => $this->tr_id,
            'reffdtltr_seq' => $orderDtl->tr_seq,
            'matl_id' => $orderDtl->matl_id,
            'matl_code' => $orderDtl->matl_code,
            'matl_descr' => $orderDtl->matl_descr,
            'matl_uom' => $orderDtl->matl_uom,
            'wh_code' => $inputs['wh_code'],
            'qty' => $orderDtl->qty,
            'qty_reff' => $orderDtl->qty_reff,
        ]);
        if ($delivDtl->isNew()) {
            $delivDtl->status_code = Status::OPEN;
        }
        $delivDtl->save();
    }

    private function createBillingDetail($delivDtl, $billingTrType)
    {
        $billingDtl = BillingDtl::firstOrNew([
            'trhdr_id' => $delivDtl->trhdr_id,
            'tr_seq' => $delivDtl->tr_seq,
            'tr_type' => $billingTrType,
        ]);
        $billingDtl->fillAndSanitize([
            'trhdr_id' => $delivDtl->trhdr_id,
            'tr_type' => $billingTrType,
            'tr_id' => $delivDtl->tr_id,
            'tr_seq' => $delivDtl->tr_seq,
            'dlvdtl_id' => $delivDtl->id,
            'dlvhdrtr_type' => $delivDtl->tr_type,
            'dlvhdrtr_id' => $delivDtl->tr_id,
            'dlvdtltr_seq' => $delivDtl->tr_seq,
            'matl_id' => $delivDtl->matl_id,
            'matl_code' => $delivDtl->matl_code,
            'matl_uom' => $delivDtl->matl_uom,
            'descr' => '',
            'qty' => $delivDtl->qty,
            'qty_uom' => '',
            'qty_base' => $delivDtl->qty,
            'price' => $delivDtl->price,
            'price_uom' => '',
            'price_base' => $delivDtl->trhdr_id,
            'amt' => $delivDtl->amt,
            'amt_reff' => $delivDtl->amt,
        ]);
        if ($billingDtl->isNew()) {
            $billingDtl->status_code = Status::OPEN;
        }
        $billingDtl->save();
    }
}
