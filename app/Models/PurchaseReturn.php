<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasFactory;
    protected $fillable = ['return_date','reference_number','supplier_name','supplier_id'];

    public function supplier(){
        return $this->belongsTo('App\Models\Supplier');
    }

    public function purchase_return_items(){
        return $this->hasMany('App\Models\PurchaseReturnItem');
    }
}
