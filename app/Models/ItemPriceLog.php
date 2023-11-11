<?php

namespace App\Models;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemPriceLog extends Model
{
    use HasFactory, SoftDeletes;
    use ModelTrait;

    protected $fillable = [
        'old_price',
        'new_price',
        'item_price_id',
    ];

    public function item_price()
    {
        return $this->belongsTo(ItemPrice::class);
    }
}
