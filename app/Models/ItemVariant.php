<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemVariant extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;
    protected $fillable = ['category_variants_id', 'item_id', 'Variant_name'];

    public function scopeIsDuplicate($query, $item_id, $unit_id)
    {
        return $query->where('item_id', $item_id)->where('unit_id', $unit_id);
    }
    public function scopeItemId($query, $id)
    {
        return $query->where('item_id', $id);
    }

    public function scopeItemUnitId($query, $id)
    {
        return $query->where('id', $id);
    }

    public function item()
    {
        return $this->belongsTo('App\Models\Item');
    }

    public function from_unit()
    {
        return $this->belongsTo('App\Models\Unit', 'unit_id');
    }

    public function to_unit()
    {
        return $this->belongsTo('App\Models\Unit', 'to_unit_id');
    }
}
