<?php

namespace App\Models\TrdJewel1\Transaction;

use App\Models\TrdJewel1\Base\TrdJewel1BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
class CartHdr extends TrdJewel1BaseModel
{
    use SoftDeletes;
    protected $connection;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = Constant::Trdjewel1_ConnectionString();
    }

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
        return $this->CartDtl()->sum('qty');
    }

    public function getTotalAmtAttribute()
    {
        return $this->CartDtl()->sum('amt');
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
