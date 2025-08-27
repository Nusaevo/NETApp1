<?php

namespace App\Models\TrdRetail1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdRetail1\Master\Partner;
use App\Models\TrdRetail1\Transaction\{ReturnDtl, OrderHdr, DelivHdr, DelivDtl, BillingHdr, BillingDtl, PaymentHdr, PaymentDtl};
use App\Enums\Status;
use App\Models\SysConfig1\ConfigSnum;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use App\Enums\Constant;
class ReturnHdr extends BaseModel
{
    use SoftDeletes;

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($returnHdr) {
            foreach ($returnHdr->ReturnDtl as $returnDtl) {
                $returnDtl->delete();
            }
        });
    }
    protected $fillable = [
        'tr_id',
        'tr_type',
        'tr_date',
        'reff_code',
        'partner_id',
        'partner_code',
        'deliv_by',
        'payment_term_id',
        'payment_term',
        'curr_id',
        'curr_code',
        'curr_rate',
        'status_code'
    ];

    #region Relations

    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function ReturnDtl()
    {
        return $this->hasMany(ReturnDtl::class, 'trhdr_id', 'id');
    }

    public function ExchangeOrder()
    {
        return $this->hasOne(OrderHdr::class, 'tr_id', 'tr_id')
                    ->where('tr_type', 'SOR')
                    ->latest('id'); // Get the most recent one if multiple exist
    }

    #endregion

    #region Attributes

    public function getTotalQtyAttribute()
    {
        return (int) $this->ReturnDtl()->sum('qty');
    }

    public function getTotalAmtAttribute()
    {
        return (int) $this->ReturnDtl()->sum('amt');
    }

    public function getMatlCodesAttribute()
    {
        $matlCodes = $this->ReturnDtl()->pluck('matl_code')->toArray();
        return implode(', ', $matlCodes);
    }

    public function getExchangeQtyAttribute()
    {
        return $this->ExchangeOrder ? $this->ExchangeOrder->total_qty : 0;
    }

    public function getExchangeAmtAttribute()
    {
        return $this->ExchangeOrder ? $this->ExchangeOrder->total_amt : 0;
    }

    #endregion

    public function isOrderCompleted(): bool
    {
        if ($this->status_code == Status::COMPLETED) {
            return true;
        }
        return false;
    }

    /**
     * Save Return Header and Details
     * This method handles saving return transactions which increase inventory stock
     *
     * @param string $trType Transaction type (should be 'SR' for Sales Return)
     * @param array $inputs Header data from form
     * @param array $inputDetails Return item details
     * @param bool $createBillingDelivery If true, automatically create DelivDtl, BillingDtl & PaymentDtl
     * @return $this
     * @throws \Exception
     */
    public function saveReturn(string $trType, array $inputs, array $inputDetails, bool $createBillingDelivery = true)
    {
        // --- Save/update HEADER ---
        $this->fill($inputs);
        $this->generateReturnTransactionId($trType);

        if ($this->isNew()) {
            $this->status_code = Status::OPEN;
        }

        $this->save();

        // --- Optional: Create Header Delivery, Billing, & Payment ---
        if ($createBillingDelivery) {
            $this->createReturnDeliveryAndBillingHeader($trType, $inputs);
        }

        // --- Synchronize & Save DETAILS ---
        $existingDetails = ReturnDtl::where('trhdr_id', $this->id)
            ->where('tr_type', $trType)
            ->get()
            ->keyBy('tr_seq')
            ->toArray();

        $inputDetailsKeyed = collect($inputDetails)->keyBy('tr_seq')->toArray();
        $itemsToDelete = array_diff_key($existingDetails, $inputDetailsKeyed);

        // Delete removed items
        foreach ($itemsToDelete as $tr_seq => $detail) {
            $returnDtl = ReturnDtl::find($detail['id']);
            if ($returnDtl) {
                $returnDtl->forceDelete();
            }
        }

        $trTypeValues = $this->getReturnTrTypeValues($trType);

        // Save/update return details
        foreach ($inputDetails as $index => $detail) {
            $detail['tr_seq'] = $index + 1;
            $detail['tr_id'] = $this->tr_id ?? 0;
            $detail['trhdr_id'] = $this->id ?? 0;
            $detail['tr_type'] = $trType ?? '';

            // Set qty_reff for returns (reference quantity)
            if (isset($detail['qty'])) {
                $detail['qty_reff'] = $detail['qty'];
            }

            // Ensure all fields have default values
            $detail['matl_id'] = $detail['matl_id'] ?? 0;
            $detail['matl_code'] = $detail['matl_code'] ?? '';
            $detail['matl_uom'] = $detail['matl_uom'] ?? '';
            $detail['wh_id'] = $detail['wh_id'] ?? 0;
            $detail['wh_code'] = $detail['wh_code'] ?? '';
            $detail['qty'] = $detail['qty'] ?? 0;
            $detail['price'] = $detail['price'] ?? 0;
            $detail['amt'] = $detail['amt'] ?? 0;

            $returnDtl = ReturnDtl::firstOrNew([
                'tr_id' => $detail['tr_id'],
                'tr_seq' => $detail['tr_seq'],
            ]);

            $returnDtl->fill($detail);

            if ($returnDtl->isNew()) {
                $returnDtl->status_code = Status::OPEN;
            }

            $returnDtl->save();

            if ($createBillingDelivery) {
                $this->createReturnDeliveryAndBillingDetail(
                    $returnDtl,
                    $detail,
                    $trTypeValues['delivTrType'],
                    $trTypeValues['billingTrType'],
                    $trTypeValues['paymentTrType']
                );
            }
        }

        return $this;
    }

    /**
     * Generate return transaction ID from RETURN_ORDER_LASTID serial
     *
     * @param string $trType
     */
    private function generateReturnTransactionId($trType)
    {
        $configCode = 'RETURN_ORDER_LASTID';

        if ($this->tr_id === null || $this->tr_id == 0) {
            $configSnum = ConfigSnum::where('code', '=', $configCode)->first();
            if ($configSnum) {
                $stepCnt = $configSnum->step_cnt;
                $proposedTrId = $configSnum->last_cnt + $stepCnt;

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

    /**
     * Get transaction type values for Return Delivery, Billing, and Payment.
     *
     * @param string $trType
     * @return array Example: ['delivTrType' => 'RD', 'billingTrType' => 'RB', 'paymentTrType' => 'RP']
     */
    private function getReturnTrTypeValues($trType)
    {
        // For Sales Return (SR), create Return Delivery (RD), Return Billing (RB), Return Payment (RP)
        return [
            'delivTrType'   => 'RD',  // Return Delivery (increases stock)
            'billingTrType' => 'RB',  // Return Billing (credit to customer)
            'paymentTrType' => 'RP',  // Return Payment (refund to customer)
        ];
    }

    /**
     * Create or update header Return Delivery, Billing, and Payment.
     *
     * @param string $trType
     * @param array $inputs Header data from form
     * @return void
     */
    private function createReturnDeliveryAndBillingHeader($trType, array $inputs)
    {
        $values = $this->getReturnTrTypeValues($trType);

        // --- Return DelivHdr (RD) - This will increase stock ---
        $delivHdr = DelivHdr::firstOrNew([
            'tr_id'   => $this->tr_id,
            'tr_type' => $values['delivTrType'],
        ]);

        $delivHdr->fill([
            'tr_id'        => $this->tr_id ?? 0,
            'tr_type'      => $values['delivTrType'] ?? '',
            'tr_date'      => $this->tr_date ?? date('Y-m-d'),
            'partner_id'   => $this->partner_id ?? 0,
            'partner_code' => $this->partner_code ?? '',
            'deliv_by'     => $inputs['deliv_by'] ?? '',
        ]);

        if ($delivHdr->isNew()) {
            $delivHdr->status_code = Status::OPEN;
        }
        $delivHdr->save();

        // --- Return BillingHdr (RB) - Credit to customer ---
        $billingHdr = BillingHdr::firstOrNew([
            'tr_id'   => $this->tr_id,
            'tr_type' => $values['billingTrType'],
        ]);

        $billingHdr->fill([
            'tr_id'        => $this->tr_id ?? 0,
            'tr_type'      => $values['billingTrType'] ?? '',
            'tr_date'      => $this->tr_date ?? date('Y-m-d'),
            'partner_id'   => $this->partner_id ?? 0,
            'partner_code' => $this->partner_code ?? '',
            'payment_term_id' => $this->payment_term_id ?? 0,
            'payment_term' => $this->payment_term ?? '',
            'payment_due_days' => 0,
        ]);

        if ($billingHdr->isNew()) {
            $billingHdr->status_code = Status::OPEN;
        }
        $billingHdr->save();

        // --- Return PaymentHdr (RP) - Refund to customer ---
        $paymentHdr = PaymentHdr::firstOrNew([
            'tr_id'   => $this->tr_id,
            'tr_type' => $values['paymentTrType'],
        ]);

        $paymentHdr->fill([
            'tr_id'        => $this->tr_id ?? 0,
            'tr_type'      => $values['paymentTrType'] ?? '',
            'tr_date'      => $this->tr_date ?? date('Y-m-d'),
            'partner_id'   => $this->partner_id ?? 0,
            'partner_code' => $this->partner_code ?? '',
            'bank_id'      => $inputs['bank_id'] ?? 0,
            'bank_code'    => $inputs['bank_code'] ?? '',
            'bank_reff'    => $inputs['bank_reff'] ?? '',
            'bank_due'     => $inputs['bank_due'] ?? '1900-01-01',
            'bank_rcv'     => $inputs['bank_rcv'] ?? 0,
            'bank_rcv_base'=> $inputs['bank_rcv_base'] ?? 0,
            'bank_note'    => $inputs['bank_note'] ?? '',
            'curr_id'      => $this->curr_id ?? 0,
            'curr_rate'    => $this->curr_rate ?? 0,
        ]);

        if ($paymentHdr->isNew()) {
            $paymentHdr->status_code = Status::OPEN;
        }
        $paymentHdr->save();
    }

    /**
     * Create or update detail Return Delivery, Billing, and Payment for a ReturnDtl.
     *
     * For return delivery and billing detail, trhdr_id is taken from the corresponding header,
     * as well as payment detail.
     *
     * @param ReturnDtl $returnDtl ReturnDtl that was just saved/updated
     * @param array $detailData Additional data (e.g., 'wh_id') from form
     * @param string $delivTrType Transaction type for Delivery (e.g., 'RD')
     * @param string $billingTrType Transaction type for Billing (e.g., 'RB')
     * @param string $paymentTrType Transaction type for Payment (e.g., 'RP')
     * @return void
     */
    private function createReturnDeliveryAndBillingDetail($returnDtl, array $detailData, $delivTrType, $billingTrType, $paymentTrType)
    {
        // Get related headers based on tr_id and tr_type
        $delivHdr = DelivHdr::where('tr_id', $this->tr_id)
            ->where('tr_type', $delivTrType)
            ->first();

        $billingHdr = BillingHdr::where('tr_id', $this->tr_id)
            ->where('tr_type', $billingTrType)
            ->first();

        $paymentHdr = PaymentHdr::where('tr_id', $this->tr_id)
            ->where('tr_type', $paymentTrType)
            ->first();

        // --- Return DelivDtl (RD) - This increases stock ---
        $delivDtl = DelivDtl::firstOrNew([
            'trhdr_id' => $delivHdr->id, // use Delivery header ID
            'tr_seq'   => $returnDtl->tr_seq,
            'tr_type'  => $delivTrType,
        ]);

        $delivDtl->fill([
            'trhdr_id'      => $delivHdr->id,
            'tr_type'       => $delivTrType,
            'tr_id'         => $this->tr_id,
            'tr_seq'        => $returnDtl->tr_seq,
            'reffdtl_id'    => $returnDtl->id ?? 0,
            'reffhdrtr_type'=> $this->tr_type ?? '',
            'reffhdrtr_id'  => $this->tr_id ?? 0,
            'reffdtltr_seq' => $returnDtl->tr_seq ?? 0,
            'matl_id'       => $returnDtl->matl_id ?? 0,
            'matl_code'     => $returnDtl->matl_code ?? '',
            'matl_descr'    => $returnDtl->matl_descr ?? '',
            'matl_uom'      => $returnDtl->matl_uom ?? '',
            'wh_id'         => $detailData['wh_id'] ?? 0,
            'wh_code'       => $detailData['wh_code'] ?? '',
            'qty'           => $returnDtl->qty ?? 0, // Positive qty increases stock
            'qty_reff'      => $returnDtl->qty_reff ?? 0,
        ]);

        if ($delivDtl->isNew()) {
            $delivDtl->status_code = Status::OPEN;
        }
        $delivDtl->save();

        // --- Return BillingDtl (RB) - Credit to customer ---
        $billingDtl = BillingDtl::firstOrNew([
            'trhdr_id' => $billingHdr->id, // use Billing header ID
            'tr_seq'   => $returnDtl->tr_seq,
            'tr_type'  => $billingTrType,
        ]);

        $billingDtl->fill([
            'trhdr_id'      => $billingHdr->id,
            'tr_type'       => $billingTrType,
            'tr_id'         => $returnDtl->tr_id ?? 0,
            'tr_seq'        => $returnDtl->tr_seq ?? 0,
            'dlvdtl_id'     => $delivDtl->id ?? 0,
            'dlvhdrtr_type' => $delivDtl->tr_type ?? '',
            'dlvhdrtr_id'   => $delivDtl->tr_id ?? 0,
            'dlvdtltr_seq'  => $delivDtl->tr_seq ?? 0,
            'matl_id'       => $returnDtl->matl_id ?? 0,
            'matl_code'     => $returnDtl->matl_code ?? '',
            'matl_uom'      => $returnDtl->matl_uom ?? '',
            'descr'         => '',
            'qty'           => $returnDtl->qty ?? 0,
            'qty_base'      => $returnDtl->qty ?? 0,
            'price'         => $returnDtl->price ?? 0,
            'price_base'    => $returnDtl->price ?? 0,
            'amt'           => $returnDtl->amt ?? 0, // Negative amount for credit
            'amt_reff'      => $returnDtl->amt ?? 0,
        ]);

        if ($billingDtl->isNew()) {
            $billingDtl->status_code = Status::OPEN;
        }
        $billingDtl->save();

        // --- Return PaymentDtl (RP) - Refund to customer ---
        $paymentDtl = PaymentDtl::firstOrNew([
            'trhdr_id' => $paymentHdr->id, // use Payment header ID
            'tr_seq'   => $returnDtl->tr_seq,
            'tr_type'  => $paymentTrType,
        ]);

        $paymentDtl->fill([
            'trhdr_id'      => $paymentHdr->id,
            'tr_type'       => $paymentTrType,
            'tr_id'         => $returnDtl->tr_id ?? 0,
            'tr_seq'        => $returnDtl->tr_seq ?? 0,
            'billdtl_id'    => $billingDtl->id ?? 0,
            'billhdrtr_type'=> $billingDtl->tr_type ?? '',
            'billhdrtr_id'  => $billingDtl->tr_id ?? 0,
            'billdtltr_seq' => $billingDtl->tr_seq ?? 0,
            'amt'           => $billingDtl->amt ?? 0, // Refund amount
            'amt_base'      => $billingDtl->amt ?? 0,
            'status_code'   => Status::OPEN,
        ]);
        $paymentDtl->save();
    }
}
