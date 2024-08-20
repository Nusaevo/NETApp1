<?php

namespace App\Models\TrdJewel1\Master;
use App\Models\Base\BaseModel;
use App\Models\SysConfig1\ConfigConst;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;

class MatlBom extends BaseModel
{
    protected $table = 'matl_boms';
    use SoftDeletes;
    protected $connection;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = Constant::Trdjewel1_ConnectionString();
    }

    protected static function boot()
    {
        parent::boot();
        static::retrieved(function ($model) {
            if (array_key_exists('jwl_sides_carat', $model->attributes)) {
                $model->jwl_sides_carat = numberFormat($model->attributes['jwl_sides_carat'], 3);
            }
        });
    }

    protected $fillable = [
        'matl_id',
        'matl_code',
        'base_matl_id',
        'matl_origin_id',
        'seq',
        'jwl_sides_carat',
        'jwl_sides_cnt',
        'jwl_sides_matl',
        'jwl_sides_parcel',
        'jwl_sides_price',
        'jwl_sides_amt',
        'jwl_sides_spec'
    ];

    #region Attributes
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
    #endregion

    #region Relations
    public function ConfigConst()
    {
        return $this->belongsTo(ConfigConst::class, 'base_matl_id', 'id');
    }
    #endregion
}
