<?php

namespace App\Models;

use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceCategory extends Model
{
    use HasFactory;
    use SoftDeletes;
    use ModelTrait;

    protected $fillable = ['name'];

    public function item_prices(){
        return $this->hasMany('App\Models\ItemPrice');
    }
    public function customers(){
        return $this->hasMany('App\Models\Customer');
    }
}
