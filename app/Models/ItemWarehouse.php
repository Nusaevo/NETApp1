<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemWarehouse extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;
    protected $fillable = ['qty', 'qty_defect', 'item_unit_id', 'warehouse_id'];

    public function scopeFindItemWarehouse($query, $item_unit_id, $warehouse_id)
    {
        return $query->where('item_unit_id', $item_unit_id)->where('warehouse_id', $warehouse_id);
    }

    public function item_unit()
    {
        return $this->belongsTo('App\Models\ItemUnit');
    }

    public function warehouse()
    {
        return $this->belongsTo('App\Models\Warehouse');
    }
}
