<?php

namespace App\Models\TrdJewel1\Transaction;

use App\Models\Base\BaseModel;

class CartHdr extends BaseModel
{
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($cartHdr) {
            $cartHdr->CartDtl()->delete();
        });
    }

    protected $fillable = [
        'tr_type',
        'tr_id',
        'tr_date',
        'curr_id',
        'curr_code',
        'curr_rate',
        'status_code',
    ];

    public function getTotalQtyAttribute()
    {
        return $this->CartDtl()->sum('qty');
    }

    public static function getCartDetailCount($usercode)
    {
        return self::where('created_by', $usercode)
                    ->where('tr_type', 'C')
                    ->whereHas('cartDtl')
                    ->withCount('cartDtl')
                    ->first()
                    ->cart_dtl_count ?? 0;
    }

    public function getTotalAmtAttribute()
    {
        return $this->CartDtl()->sum('amt');
    }

    public function CartDtl()
    {
        return $this->hasMany(CartDtl::class, 'trhdr_id', 'id');
    }
}
