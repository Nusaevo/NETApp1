<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adjustment extends Model
{
    use HasFactory;
    protected $fillable = ['adjustment_date','warehouse_id'];

    public function warehouse(){
        return $this->belongsTo('App\Models\Warehouse');
    }

    public function adjustment_items(){
        return $this->belongsTo('App\Models\AdjustmentItem');
    }
}
