<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
class MatlBom extends BaseModel
{
    protected $table = 'matl_boms';

    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'matl_id',
        'matl_code',
        'base_matl_id',
        'base_category_id',
        'seq',
        'jwl_sides_carat',
        'jwl_sides_cnt',
        'jwl_sides_matl',
        'jwl_sides_parcel',
        'jwl_sides_price',
        'jwl_sides_amt'
    ];

    public function getAllColumnValues($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            if ($attribute == "jwl_sides_carat") {
                return int_qty($this->attributes[$attribute]);
            }
            if ($attribute == "jwl_sides_cnt") {
                return int_qty($this->attributes[$attribute]);
            }
            if ($attribute == "jwl_sides_price") {
                return int_qty($this->attributes[$attribute]);
            }
            return $this->attributes[$attribute];
        }
        return null;
    }

    /**
     * Retrieve the JSON attribute as an array.
     *
     * @return array|null
     */
    public function getDetailsAttribute()
    {
        return $this->attributes['specs'] ? json_decode($this->attributes['details'], true) : null;
    }

    /**
     * Set the JSON attribute.
     *
     * @param  array  $value
     * @return void
     */
    public function setDetailsAttribute($value)
    {
        $this->attributes['specs'] = $value ? json_encode($value) : null;
    }

    public function baseMaterials()
    {
        return $this->belongsTo('App\Models\Settings\ConfigConst', 'base_matl_id', 'id');
    }
}
