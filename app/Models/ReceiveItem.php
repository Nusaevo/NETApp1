<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiveItem extends Model
{
    use HasFactory;
    
    protected $fillable = ['receive_id','item_id'];
   
    public function item(){
        return $this->belongsTo('App\Models\Item');
    }

    public function receive(){
        return $this->belongsTo('App\Models\Receive');
    }

    public function receive_details(){
        return $this->hasMany('App\Models\ReceiveDetail');
    }
}
