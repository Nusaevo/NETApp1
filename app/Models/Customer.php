<?php

namespace App\Models;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;

    protected $fillable = ['name', 'address','city','npwp','contact_name','contact_number','email','price_category_id'];

    public function sales_orders(){
        return $this->hasMany('App\Models\SalesOrder');
    }

    public function price_category(){
        return $this->belongsTo('App\Models\PriceCategory');
    }

    public function sales_returns(){
        return $this->hasMany('App\Models\SalesReturn');
    }


}
