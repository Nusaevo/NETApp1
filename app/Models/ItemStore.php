<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemStore extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;
    protected $fillable = ['qty', 'qty_defect', 'price', 'item_variant_id', 'store_id'];

    public function scopeFindItemStore($query, $item_unit_id, $store_id)
    {
        return $query->where('item_variant_id', $item_unit_id)->where('store_id', $store_id);
    }

    public function item_variant()
    {
        return $this->belongsTo('App\Models\ItemVariants');
    }
    public function store()
    {
        return $this->belongsTo('App\Models\Store');
    }
}
