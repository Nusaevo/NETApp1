<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdjustmentItem extends Model
{
    use HasFactory;
    protected $fillable = ['adjustment_id','item_id'];

    public function adjustment(){
        return $this->belongsTo('App\Models\Adjustment');
    }

    public function item(){
        return $this->belongsTo('App\Models\Item');
    }

    public function adjustment_details(){
        return $this->hasMany('App\Models\AdjustmentDetail');
    }
}
