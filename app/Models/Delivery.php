<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;
    protected $fillable = ['delivery_date','reference_number','sales_order_id'];
   
    public function sales_order(){
        return $this->belongsTo('App\Models\SalesOrder');
    }

    public function delivery_items(){
        return $this->hasMany('App\Models\DeliveryItem');
    }
}
