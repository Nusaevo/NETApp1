<?php

namespace App\Models\TrdJewel1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdJewel1\Master\Partner;
use App\Models\TrdJewel1\Transaction\ReturnDtl;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnHdr extends BaseModel
{
    protected static function boot()
    {
        parent::boot();
    }
    protected $fillable = [
        'tr_id',
        'tr_type',
        'tr_id',
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

    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function ReturnDtl()
    {
        return $this->hasMany(ReturnDtl::class, 'trhdr_id', 'id');
    }
}
