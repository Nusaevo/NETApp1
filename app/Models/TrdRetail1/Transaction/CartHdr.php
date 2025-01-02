<?php

namespace App\Models\TrdRetail1\Transaction;

use App\Models\TrdRetail1\Base\TrdRetail1BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
class CartHdr extends TrdRetail1BaseModel
{
    use SoftDeletes;


    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($cartHdr) {
            $cartHdr->CartDtl()->delete();
        });
    }

    protected $fillable = [
        'tr_type',
        'tr_date',
        'curr_id',
        'curr_code',
        'curr_rate',
        'status_code',
    ];

    #region Relations

    public function CartDtl()
    {
        return $this->hasMany(CartDtl::class, 'trhdr_id', 'id');
    }

    #endregion

    #region Attributes
    public function getTotalQtyAttribute()
    {
        return (int) $this->CartDtl()->sum('qty');
    }

    public function getTotalAmtAttribute()
    {
        return (int) $this->CartDtl()->sum('amt');
    }

    #endregion

    public static function getCartDetailCount($usercode)
    {
        return self::where('created_by', $usercode)
                    ->where('tr_type', 'C')
                    ->whereHas('cartDtl')
                    ->withCount('cartDtl')
                    ->first()
                    ->cart_dtl_count ?? 0;
    }
}
