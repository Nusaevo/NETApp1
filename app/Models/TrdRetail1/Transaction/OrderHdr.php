<?php

namespace App\Models\TrdRetail1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdRetail1\Master\Partner;
use App\Models\TrdRetail1\Master\Material;
use App\Enums\Status;
use App\Models\SysConfig1\ConfigSnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderHdr extends BaseModel
{
    use SoftDeletes;

    protected $fillable = ['tr_id', 'tr_type', 'tr_date', 'reff_code', 'partner_id', 'partner_code', 'sales_id', 'sales_code', 'deliv_by', 'payment_term_id', 'payment_term', 'curr_id', 'curr_code', 'curr_rate', 'status_code', 'print_settings', 'print_remarks'];

    /* ======================================================
     *                     BOOT METHOD
     * ====================================================== */
    protected static function boot()
    {
        parent::boot();

        // Cascade deletes for related records
        static::deleting(function ($orderHdr) {
            // Delete DelivHdr and DelivDtl
            $delivHdr = $orderHdr->DelivHdr;
            if ($delivHdr) {
                foreach ($delivHdr->DelivDtl as $delivDtl) {
                    $delivDtl->delete();
                }
                $delivHdr->delete();
            }

            // Delete BillingHdr and BillingDtl
            $billingHdr = $orderHdr->BillingHdr;
            if ($billingHdr) {
                foreach ($billingHdr->BillingDtl as $billingDtl) {
                    $billingDtl->delete();
                }
                $billingHdr->delete();
            }

            // Delete OrderDtl
            foreach ($orderHdr->OrderDtl as $orderDtl) {
                $orderDtl->delete();
            }
        });

        // Example: format goldprice_curr on retrieval
        static::retrieved(function ($model) {
            if (array_key_exists('goldprice_curr', $model->attributes)) {
                $model->goldprice_curr = numberFormat($model->attributes['goldprice_curr'], 2);
            }
        });
    }

    /* ======================================================
     *                   RELATIONSHIPS
     * ====================================================== */
    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function OrderDtl()
    {
        return $this->hasMany(OrderDtl::class, 'tr_id', 'tr_id')->where('tr_type', $this->tr_type)->orderBy('tr_seq');
    }

    public function Materials()
    {
        return $this->hasManyThrough(
            Material::class,
            OrderDtl::class,
            'tr_id', // FK on OrderDtl
            'id', // FK on Material
            'tr_id', // Local key on OrderHdr
            'matl_id', // Local key on OrderDtl
        )->where('order_dtls.tr_type', $this->tr_type);
    }

    public function DelivHdr()
    {
        // Decides the related DelivHdr type based on $this->tr_type
        $values = $this->getTrTypeValues($this->tr_type);
        return $this->hasOne(DelivHdr::class, 'tr_id', 'tr_id')->where('tr_type', $values['delivTrType']);
    }

    public function BillingHdr()
    {
        // Decides the related BillingHdr type based on $this->tr_type
        $values = $this->getTrTypeValues($this->tr_type);
        return $this->hasOne(BillingHdr::class, 'tr_id', 'tr_id')->where('tr_type', $values['billingTrType']);
    }

    /* ======================================================
     *                  ACCESSORS / SCOPES
     * ====================================================== */
    public function getTotalQtyAttribute()
    {
        return (int) $this->OrderDtl()->sum('qty');
    }

    public function getTotalAmtAttribute()
    {
        return (int) $this->OrderDtl()->sum('amt');
    }

    public function getMatlCodesAttribute()
    {
        $matlCodes = $this->OrderDtl()->pluck('matl_code')->toArray();
        return implode(', ', $matlCodes);
    }

    /* ======================================================
     *         EXAMPLE HELPER / BUSINESS LOGIC CHECKS
     * ====================================================== */
    public function isOrderCompleted(): bool
    {
        return $this->status_code === Status::COMPLETED;
    }


    /* ======================================================
     *             PRIVATE HELPER METHODS
     * ====================================================== */

    /**
     * Decide Deliv & Billing tr_type based on $trType.
     * E.g. if $trType='PO', we might say DelivHdr has 'PD' and BillingHdr has 'APB'.
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
        } else {
            // For 'SO' or other
            return [
                'delivTrType' => 'SD',
                'billingTrType' => 'ARB',
            ];
        }
    }

    /**
     * Generate tr_id if this is a new record.
     *
     * @param string $trType
     */
    private function generateTransactionId($trType)
    {
        // Example: If 'PO', use 'PURCHORDER_LASTID'. If 'SO', use 'SALESORDER_LASTID'
        $configCode = $trType === 'PO' ? 'PURCHORDER_LASTID' : 'SALESORDER_LASTID';

        if ($this->tr_id === null || $this->tr_id == 0) {
            $configSnum = ConfigSnum::where('code', '=', $configCode)->first();
            if ($configSnum) {
                $stepCnt = $configSnum->step_cnt;
                $proposedTrId = $configSnum->last_cnt + $stepCnt;

                // If we exceed wrap_high, wrap to wrap_low
                if ($proposedTrId > $configSnum->wrap_high) {
                    $proposedTrId = $configSnum->wrap_low;
                }
                $proposedTrId = max($proposedTrId, $configSnum->wrap_low);

                $configSnum->last_cnt = $proposedTrId;
                $configSnum->save();

                $this->tr_id = $proposedTrId;
            }
        }
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


    /* ======================================================
     *               MAIN SAVE METHODS (REFACTORED)
     * ====================================================== */

    /**
     * Save or update the OrderHdr (header).
     * Optionally create DelivHdr & BillingHdr if $createBillingDelivery = true.
     *
     * @param  string $trType
     * @param  array  $inputs
     * @param  bool   $createBillingDelivery
     * @return $this
     * @throws \Exception
     */
    public function saveOrderHeader($trType, array $inputs, bool $createBillingDelivery = false)
    {
        // 1. Fill the header data
        $this->fill($inputs);

        // 2. Generate the transaction ID if this is new
        $this->generateTransactionId($trType);

        // 3. If new record, set status to OPEN
        if ($this->isNew()) {
            $this->status_code = Status::OPEN;
        }

        // 4. Save the OrderHdr
        $this->save();

        // 5. If needed, create both Delivery & Billing headers in one step
        if ($createBillingDelivery) {
            $this->createDeliveryAndBillingHeader($trType, $inputs);
        }

        return $this;
    }

    /**
     * Save or update order details (OrderDtl).
     * Optionally create DelivDtl & BillingDtl if $createBillingDelivery = true.
     *
     * @param  string $trType
     * @param  array  $inputDetails          Array of detail data from Livewire
     * @param  bool   $createBillingDelivery If true, create DelivDtl & BillingDtl for each detail
     * @return void
     * @throws \Exception
     */
    public function saveOrderDetails($trType, array $inputDetails, bool $createBillingDelivery = false)
    {
        $values = $this->getTrTypeValues($trType);

        foreach ($inputDetails as $detailData) {
            // 1) Create/Update the OrderDtl record
            // Identify the record by (tr_id, tr_seq)
            $orderDtl = OrderDtl::firstOrNew([
                'tr_id' => $detailData['tr_id'],
                'tr_seq' => $detailData['tr_seq'],
            ]);

            // Force the relation fields
            $detailData['trhdr_id'] = $this->id;
            $detailData['tr_type'] = $trType;

            // If your logic copies qty -> qty_reff
            if (isset($detailData['qty'])) {
                $detailData['qty_reff'] = $detailData['qty'];
            }

            // Fill & save
            $orderDtl->fill($detailData);
            if ($orderDtl->isNew()) {
                $orderDtl->status_code = Status::OPEN;
            }
            $orderDtl->save();
            // 2) If $createBillingDelivery is true, do Delivery & Billing in ONE method
            if ($createBillingDelivery) {
                $this->createDeliveryAndBillingDetail($orderDtl, $detailData, $values['delivTrType'], $values['billingTrType']);
            }
        }
    }
    /**
     * Create or update both DelivHdr and BillingHdr for this OrderHdr in one pass.
     *
     * @param  string $trType  e.g. 'PO', 'SO'
     * @param  array  $inputs  e.g. from Livewire (includes deliv_by, partner_id, etc.)
     * @return void
     */
    private function createDeliveryAndBillingHeader($trType, array $inputs)
    {
        // 1) Decide the transaction types for Delivery & Billing
        $values = $this->getTrTypeValues($trType);

        // 2) Create or update DelivHdr
        $delivHdr = DelivHdr::firstOrNew([
            'tr_id' => $this->tr_id,
            'tr_type' => $values['delivTrType'],
        ]);

        $delivHdr->fill([
            'tr_id' => $this->tr_id,
            'tr_type' => $values['delivTrType'],
            'tr_date' => $this->tr_date,
            'partner_id' => $this->partner_id,
            'partner_code' => $this->partner_code,
            'deliv_by' => $inputs['deliv_by'] ?? '',
        ]);

        if ($delivHdr->isNew()) {
            $delivHdr->status_code = Status::OPEN;
        }
        $delivHdr->save();

        // 3) Create or update BillingHdr
        $billingHdr = BillingHdr::firstOrNew([
            'tr_id' => $this->tr_id,
            'tr_type' => $values['billingTrType'],
        ]);

        $billingHdr->fill([
            'tr_id' => $this->tr_id,
            'tr_type' => $values['billingTrType'],
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

    /**
     * Create or update both DelivDtl and BillingDtl for an OrderDtl in one pass.
     *
     * @param  OrderDtl $orderDtl       The OrderDtl we just saved or updated.
     * @param  array    $detailData     Additional data (e.g., 'wh_id') from Livewire.
     * @param  string   $delivTrType    Transaction type for Delivery (e.g., 'PD', 'SD').
     * @param  string   $billingTrType  Transaction type for Billing (e.g., 'APB', 'ARB').
     * @return void
     */
    private function createDeliveryAndBillingDetail($orderDtl, array $detailData, $delivTrType, $billingTrType)
    {
        // 1) Create or update DelivDtl
        $delivDtl = DelivDtl::firstOrNew([
            'trhdr_id' => $orderDtl->trhdr_id,
            'tr_seq' => $orderDtl->tr_seq,
            'tr_type' => $delivTrType,
        ]);

        $delivDtl->fill([
            'trhdr_id' => $orderDtl->trhdr_id,
            'tr_type' => $delivTrType,
            'tr_id' => $this->tr_id, // or $orderDtl->tr_id if your logic differs
            'tr_seq' => $orderDtl->tr_seq,
            'reffdtl_id' => $orderDtl->id,
            'reffhdrtr_type' => $this->tr_type, // The original transaction type from OrderHdr
            'reffhdrtr_id' => $this->tr_id, // or $orderDtl->tr_id
            'reffdtltr_seq' => $orderDtl->tr_seq,
            'matl_id' => $orderDtl->matl_id,
            'matl_code' => $orderDtl->matl_code,
            'matl_descr' => $orderDtl->matl_descr,
            'matl_uom' => $orderDtl->matl_uom,
            'wh_id' => $detailData['wh_id'] ?? '',
            'wh_code' => $detailData['wh_code'] ?? '',
            'qty' => $orderDtl->qty,
            'qty_reff' => $orderDtl->qty_reff,
        ]);

        if ($delivDtl->isNew()) {
            $delivDtl->status_code = Status::OPEN; // or whatever status you want
        }
        $delivDtl->save();

        // 2) Create or update BillingDtl,
        //    referencing the newly created $delivDtl->id if needed
        $billingDtl = BillingDtl::firstOrNew([
            'trhdr_id' => $orderDtl->trhdr_id,
            'tr_seq' => $orderDtl->tr_seq,
            'tr_type' => $billingTrType,
        ]);

        $billingDtl->fill([
            'trhdr_id' => $orderDtl->trhdr_id,
            'tr_type' => $billingTrType,
            'tr_id' => $orderDtl->tr_id,
            'tr_seq' => $orderDtl->tr_seq,
            'dlvdtl_id' => $delivDtl->id, // link to DelivDtl
            'dlvhdrtr_type' => $delivDtl->tr_type, // or $this->tr_type
            'dlvhdrtr_id' => $delivDtl->tr_id,
            'dlvdtltr_seq' => $delivDtl->tr_seq,
            'matl_id' => $orderDtl->matl_id,
            'matl_code' => $orderDtl->matl_code,
            'matl_uom' => $orderDtl->matl_uom,
            'descr' => '',
            'qty' => $orderDtl->qty,
            'qty_base' => $orderDtl->qty,
            'price' => $orderDtl->price ?? 0,
            'price_base' => $orderDtl->price ?? 0,
            'amt' => $orderDtl->amt ?? 0,
            'amt_reff' => $orderDtl->amt ?? 0,
        ]);

        if ($billingDtl->isNew()) {
            $billingDtl->status_code = Status::OPEN;
        }

        $billingDtl->save();
    }
}
