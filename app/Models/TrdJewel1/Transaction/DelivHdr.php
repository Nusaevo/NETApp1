<?php

namespace App\Models\TrdJewel1\Transaction;

use App\Models\TrdJewel1\Master\Partner;
use App\Models\Base\BaseModel;
class DelivHdr extends BaseModel
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
        'status_code'
    ];

    public function getAllColumns()
    {
        return $this->fillable;
    }

    public function getAllColumnValues($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            return $this->attributes[$attribute];
        }
        return null;
    }

    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function PaymentDtl()
    {
        return $this->hasMany(DelivDtl::class, 'trhdr_id', 'id');
    }
}
