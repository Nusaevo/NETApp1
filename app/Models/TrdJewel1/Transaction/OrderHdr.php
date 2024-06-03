<?php

namespace App\Models\TrdJewel1\Transaction;
use App\Models\Base\BaseModel;
use App\Models\TrdJewel1\Master\Partner;
use App\Enums\Status;
use App\Models\SysConfig1\ConfigSnum;
class OrderHdr extends BaseModel
{
    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($orderHdr) {
            $orderHdr->DelivHdr()->forceDelete();
            $orderHdr->BillingHdr()->forceDelete();
            $orderHdr->OrderDtl()->forceDelete();
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

    public function isEnableToEdit(): bool
    {
        if ($this->status_code == Status::COMPLETED) {
            return false;
        }

        foreach ($this->OrderDtl as $orderDtl) {
            if ($orderDtl->qty_reff !== $orderDtl->qty) {
                return false;
            }
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

     public function saveOrder($appCode,$trType, $inputs, $input_details, $object_detail  = [], $createBillingDelivery = false)
     {
         $delivTrType = "";
         $billingTrType = "";
         $code = "";
         if($trType=="PO")
         {
             $delivTrType = "PD";
             $billingTrType = "APB";
             $code = "PURCHORDER_LASTID";
         }else{
             $delivTrType = "SD";
             $billingTrType = "APB";
             $code = "SALESORDER_LASTID";
         }
         $this->fillAndSanitize($inputs);
         if($this->tr_id === null || $this->tr_id == 0)
         {
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
                 $this->tr_id =  $proposedTrId;
                 $configSnum->save();
             }
         }
         $this->save();
         if($createBillingDelivery == true)
         {
             $delivHdr = DelivHdr::firstOrNew(['tr_id' => $this->tr_id,'tr_type' => $delivTrType]);
             $delivHdr->fillAndSanitize([
                 'tr_id' => $this->tr_id,
                 'tr_type' =>  $delivTrType,
                 'tr_date' => $this->tr_date,
                 'partner_id' => $this->partner_id,
                 'partner_code' => $this->partner_code,
                 'deliv_by' => $inputs['deliv_by'] ?? '',
                 'status_code' => $this->status_code,
             ]);
             $delivHdr->save();
             $billingHdr = BillingHdr::firstOrNew(['tr_id' => $this->tr_id,'tr_type' =>  $billingTrType]);
             $billingHdr->fillAndSanitize([
                 'tr_id' => $this->tr_id,
                 'tr_type' => $billingTrType,
                 'tr_date' => $this->tr_date,
                 'partner_id' => $this->partner_id,
                 'partner_code' => $this->partner_code,
                 'payment_term_id' => 0,
                 'payment_term' => '',
                 'payment_due_days' => 0,
                 'status_code' => $this->status_code,
             ]);
             $billingHdr->save();
         }
         foreach ($input_details as $index => $inputDetail) {
             if (!isset($object_detail[$index])) {
                 $object_detail[$index] = new OrderDtl();
             }
             $inputDetail['tr_id'] = $this->tr_id;
             $inputDetail['tr_seq'] = $index + 1;
             $inputDetail['trhdr_id'] = $this->id;
             $inputDetail['qty_reff'] = $inputDetail['qty'];
             $object_detail[$index]->fillAndSanitize($inputDetail);
             $object_detail[$index]->save();
             if($createBillingDelivery == true)
             {

                 $delivDtl = DelivDtl::firstOrNew([
                     'trhdr_id' =>  $object_detail[$index]->trhdr_id,
                     'tr_seq' =>  $object_detail[$index]->tr_seq,
                     'tr_type' => $delivTrType,
                 ]);
                 $delivDtl->fillAndSanitize([
                     'trhdr_id' =>  $object_detail[$index]->trhdr_id,
                     'tr_type' =>  $delivTrType,
                     'tr_id' =>  $this->tr_id,
                     'tr_seq' =>  $object_detail[$index]->tr_seq,
                     'reffdtl_id' =>  $object_detail[$index]->id,
                     'reffhdrtr_type' =>  $object_detail[$index]->tr_type,
                     'reffhdrtr_id' =>  $this->tr_id,
                     'reffdtltr_seq' =>  $object_detail[$index]->tr_seq,
                     'matl_id' =>  $object_detail[$index]->matl_id,
                     'matl_code' =>  $object_detail[$index]->matl_code,
                     'matl_descr' =>  $object_detail[$index]->matl_descr,
                     'matl_uom' =>  $object_detail[$index]->matl_uom,
                     'wh_code' =>   $inputs['wh_code'],
                     'qty' =>  $object_detail[$index]->qty,
                     'qty_reff' =>  $object_detail[$index]->qty_reff,
                     'status_code' =>  $object_detail[$index]->status_code,
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
                     'matl_uom' => $object_detail[$index]->matl_uom,
                     'descr' => '',
                     'qty' => $delivDtl->qty,
                     'qty_uom' => '',
                     'qty_base' => $delivDtl->qty,
                     'price' =>  $object_detail[$index]->price,
                     'price_uom' => '',
                     'price_base' =>  $object_detail[$index]->trhdr_id,
                     'amt' =>  $object_detail[$index]->amt,
                     'amt_reff' =>  $object_detail[$index]->amt,
                     'status_code' =>  $object_detail[$index]->status_code,
                 ]);
                 $billingDtl->save();
             }
         }
     }
}
