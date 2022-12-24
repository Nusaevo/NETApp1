<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    use HasFactory;
    protected $fillable = ['return_date','reference_number','customer_name','customer_id'];

    public function customer(){
        return $this->belongsTo('App\Models\Customer');
    }

    public function sales_return_items(){
        return $this->hasMany('App\Models\SalesReturnItem');
    }
}
