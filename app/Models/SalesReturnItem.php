<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    use HasFactory;
    protected $fillable = ['item_id','receive_id','sales_return_id'];

    public function item(){
        return $this->belongsTo('App\Models\Item');
    }
    
    public function delivery(){
        return $this->belongsTo('App\Models\Delivery');
    }

    public function sales_return(){
        return $this->belongsTo('App\Models\SalesReturn');
    }

    public function sales_return_details(){
        return $this->hasMany('App\Models\SalesReturnDetail');
    }
}
