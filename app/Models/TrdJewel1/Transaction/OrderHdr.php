<?php

namespace App\Models\TrdJewel1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdJewel1\Master\Partner;
use App\Enums\Status;
use App\Models\SysConfig1\ConfigSnum;
use DB;

class OrderHdr extends BaseModel
{
    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($orderHdr) {
            $orderHdr->DelivHdr()->Delete();
            $orderHdr->BillingHdr()->Delete();
            $orderHdr->OrderDtl()->Delete();
        });
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
    ];

    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function getTotalQtyAttribute()
    {
        return $this->OrderDtl()->sum('qty');
    }

    public function getTotalAmtAttribute()
    {
        return $this->OrderDtl()->sum('amt');
    }

    public function OrderDtl()
    {
        return $this->hasMany(OrderDtl::class, 'tr_id', 'tr_id');
    }

    public function DelivHdr()
    {
        return $this->hasMany(DelivHdr::class, 'tr_id', 'tr_id');
    }

    public function BillingHdr()
    {
        return $this->hasMany(BillingHdr::class, 'tr_id', 'tr_id');
    }

    public static function getByCreatedByAndTrType($createdBy, $trType)
    {
        return self::where('created_by', $createdBy)->where('tr_type', $trType)->get();
    }

    public function isSalesEnableToEdit(): bool
    {
        if ($this->status_code == Status::COMPLETED) {
            return false;
        }

        return true;
    }

    public function isEnableToEdit(): bool
    {
        if ($this->status_code == Status::COMPLETED) {
            return false;
        }

        if ($this->tr_type == 'PO') {
            foreach ($this->OrderDtl as $orderDtl) {
                $relatedOrderDtl = OrderDtl::where('matl_id', $orderDtl->matl_id)
                    ->where('tr_type', 'PO')
                    ->first();

                if ($relatedOrderDtl) {
                    return false;
                }
            }
        }
        // else {
        //     foreach ($this->OrderDtl as $orderDtl) {
        //         if ($orderDtl->qty_reff !== $orderDtl->qty) {
        //             return false;
        //         }
        //     }
        // }

        return true;
    }

    public function isItemEnableToDelete(int $matl_id): bool
    {
        $relatedOrderDtl = OrderDtl::where('matl_id', $matl_id)
            ->where('tr_type', 'SO')
            ->first();

        if ($relatedOrderDtl) {
            return false;
        }

        return true;
    }


    public function getAllColumnValues($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            if ($attribute == "curr_rate") {
                return currencyToNumeric($this->attributes[$attribute]);
            }
            if ($attribute == "tr_date") {
                return dateFormat($this->attributes[$attribute], 'd-m-Y');
            }
            return $this->attributes[$attribute];
        }
        return null;
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
            $delivTrType = "";
            $billingTrType = "";
            $code = "";
            if ($trType == "PO") {
                $delivTrType = "PD";
                $billingTrType = "APB";
                $code = "PURCHORDER_LASTID";
            } else {
                $delivTrType = "SD";
                $billingTrType = "ARB";
                $code = "SALESORDER_LASTID";
            }
            $this->fillAndSanitize($inputs);
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
            $this->save();

            if ($createBillingDelivery == true) {
                $delivHdr = DelivHdr::firstOrNew(['tr_id' => $this->tr_id, 'tr_type' => $delivTrType]);
                $delivHdr->fillAndSanitize([
                    'tr_id' => $this->tr_id,
                    'tr_type' => $delivTrType,
                    'tr_date' => $this->tr_date,
                    'partner_id' => $this->partner_id,
                    'partner_code' => $this->partner_code,
                    'deliv_by' => $inputs['deliv_by'] ?? '',
                    'status_code' => $this->status_code,
                ]);
                $delivHdr->save();

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
                    'status_code' => $this->status_code,
                ]);
                $billingHdr->save();
            }

            foreach ($input_details as $index => $inputDetail) {
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
                $orderDtl->save();

                if ($createBillingDelivery == true) {
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
                        'status_code' => $orderDtl->status_code,
                    ]);
                    $delivDtl->save();

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
                        'matl_uom' => $orderDtl->matl_uom,
                        'descr' => '',
                        'qty' => $delivDtl->qty,
                        'qty_uom' => '',
                        'qty_base' => $delivDtl->qty,
                        'price' => $orderDtl->price,
                        'price_uom' => '',
                        'price_base' => $orderDtl->trhdr_id,
                        'amt' => $orderDtl->amt,
                        'amt_reff' => $orderDtl->amt,
                        'status_code' => $orderDtl->status_code,
                    ]);
                    $billingDtl->save();
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e; // Optionally, you can handle the exception differently or log it
        }
    }

}
