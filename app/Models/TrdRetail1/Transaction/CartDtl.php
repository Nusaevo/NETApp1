<?php

namespace App\Models\TrdRetail1\Transaction;

use App\Models\TrdRetail1\Master\Material;
use App\Models\TrdRetail1\Base\TrdRetail1BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
class CartDtl extends TrdRetail1BaseModel
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
