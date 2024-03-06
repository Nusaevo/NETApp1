<?php

namespace App\Models;
class OrderHdr extends BaseModel
{
    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
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
        'amt',
        'discount',
        'curr_id',
        'curr_code',
        'curr_rate',
        'status_code',
    ];

    public function Partner()
    {
        return $this->belongsTo('App\Models\Partner', 'partner_id', 'id');
    }

    public function OrderDtl()
    {
        return $this->hasMany('App\Models\OrderDtl', 'trhdr_id', 'id');
    }
}
