<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdjustmentDetail extends Model
{
    use HasFactory;
    protected $fillable = ['adjustment_item_id','qty','remark'];

    public function adjustment_item(){
        return $this->belongsTo('App\Models\AdjustmentItem');
    }
}
