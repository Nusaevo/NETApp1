<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiveDetail extends Model
{
    use HasFactory;
    
    protected $fillable = ['qty','remark','warehouse_id','receive_item_id'];
   
    public function receive_item(){
        return $this->belongsTo('App\Models\ReceiveItem');
    }

    public function warehouse(){
        return $this->belongsTo('App\Models\Warehouse');
    }
}
