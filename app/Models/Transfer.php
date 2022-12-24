<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Transfer extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = ['transfer_date','origin_id','destination_id'];

    public function origin_warehouse(){
        return $this->belongsTo('App\Models\Warehouse','origin_id');
    }

    public function destination_warehouse(){
        return $this->belongsTo('App\Models\Warehouse','destination_id');
    }

    public function transfer_items(){
        return $this->hasMany('App\Models\TransferItem');
    }
}
