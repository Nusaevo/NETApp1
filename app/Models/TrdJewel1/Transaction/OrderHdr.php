<?php

namespace App\Models\TrdJewel1\Transaction;
use App\Models\Base\BaseModel;
use App\Models\SysConfig1\ConfigSnum;
use App\Models\TrdJewel1\Master\Partner;
use Illuminate\Support\Facades\Session;
class OrderHdr extends BaseModel
{
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($orderHdr) {
            if (is_null($orderHdr->tr_id) && in_array($orderHdr->tr_type, ['PO', 'SO'])) {
                $app_code = Session::get('app_code');
                $configSnum = ConfigSnum::where('code', 'LIKE', '%AR_INVOICE%')->where('app_code', 'LIKE', $app_code)->firstOrFail();
                $stepCnt = $configSnum->step_cnt;
                $orderHdr->tr_id = $configSnum->last_cnt + $stepCnt;
                $configSnum->update(['last_cnt' => $orderHdr->tr_id]);

                $orderHdr->save();
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

    public function OrderDtl()
    {
        return $this->hasMany(OrderDtl::class, 'trhdr_id', 'id');
    }

    public function ReturnHdr()
    {
        return $this->hasMany(ReturnHdr::class, 'tr_id', 'id');
    }

    public static function getByCreatedByAndTrType($createdBy, $trType)
    {
        return self::where('created_by', $createdBy)->where('tr_type', $trType)->get();
    }

    public function isOrderReturnCreated(): bool
    {
        foreach ($this->OrderDtl as $orderDtl) {
            if ($orderDtl->qty_reff !== $orderDtl->qty) {
                return true;
            }
        }

        return false;
    }
}
