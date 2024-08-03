<?php

namespace App\Models\TrdJewel1\Transaction;

use App\Models\TrdJewel1\Master\Partner;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
class PaymentDtl extends BaseModel
{
    use SoftDeletes;
        
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'trhdr_id',
        'tr_type',
        'tr_id',
        'tr_seq',
        'billdtl_id',
        'billhdrtr_type',
        'billhdrtr_id',
        'billdtltr_seq',
        'amt',
        'amt_base',
        'status_code'
    ];

    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function OrderDtl()
    {
        return $this->hasMany(PaymentHdr::class, 'trhdr_id', 'id');
    }

}
