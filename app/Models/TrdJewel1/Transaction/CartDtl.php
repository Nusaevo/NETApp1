<?php

namespace App\Models\TrdJewel1\Transaction;

use App\Models\TrdJewel1\Master\Material;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
class CartDtl extends BaseModel
{
    use SoftDeletes;


    protected static function boot()
    {
        parent::boot();
        static::saving(function ($cartDtl) {
            $qty = $cartDtl->qty;
            $price = $cartDtl->price;
            $cartDtl->amt = $qty * $price;
        });
    }

    protected $fillable = [
        'trhdr_id',
        'tr_seq',
        'matl_id',
        'matl_code',
        'matl_descr',
        'matl_uom',
        'qty',
        'qty_reff',
        'price',
        'amt'
    ];

    #region Relations
    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }
    #endregion

    public function scopeGetByCartHdr($query, $id)
    {
        return $query->where('trhdr_id', $id);
    }
}
