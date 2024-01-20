<?php

namespace App\Models\Transactions;

use App\Models\BaseModel;
use App\Models\Masters\Partner;

class OrderHdr extends BaseModel
{
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'tr_type',
        'tr_id',
        'tr_date',
        'reff_code',
        'partner_id',
        'partner_code',
        'sales_id',
        'sales_code',
        'deliv_by',
        'payment_term_id',
        'payment_term',
        'discount',
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
}
