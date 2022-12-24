<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPrice extends Model
{
    use HasFactory;
    protected $fillable = ['item_unit_id','price','price_category_id'];

    public function itemUnit(){
        return $this->belongsTo('App\Models\ItemUnit');
    }

    public function price_category(){
        return $this->belongsTo('App\Models\PriceCategory');
    }
}
