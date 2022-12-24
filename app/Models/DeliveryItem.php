<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryItem extends Model
{
    use HasFactory;
    protected $fillable = ['delivery_date','item_id','delivery_id'];
   
    public function item(){
        return $this->belongsTo('App\Models\Item');
    }

    public function delivery(){
        return $this->hasMany('App\Models\Delivery');
    }
    
    public function delivery_details(){
        return $this->hasMany('App\Models\DeliveryDetail');
    }
}
