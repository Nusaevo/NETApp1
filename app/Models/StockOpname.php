<?php

namespace App\Models;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockOpname extends Model
{
    use HasFactory;
    use ModelTrait;
    use SoftDeletes;
    protected $fillable = [
        'item_warehouse_id',
        'old_qty',
        'new_qty',
        'old_qty_defect',
        'new_qty_defect',
    ];

    public function item_warehouse()
    {
        return $this->belongsTo(ItemWarehouse::class);
    }
}
