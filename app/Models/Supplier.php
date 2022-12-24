<?php

namespace App\Models;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;

    protected $fillable = ['name','contact_name','contact_number'];
    public function purchase_orders(){
        return $this->hasMany('App\Models\PurchaseOrder');
    }

    public function purchase_returns(){
        return $this->hasMany('App\Models\PurchaseReturn');
    }
}
