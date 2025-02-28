<?php

namespace App\Models\TrdRetail1\Inventories;
use Illuminate\Database\Eloquent\Model;
use App\Models\Base\BaseModel;
use App\Enums\Constant;
use App\Models\TrdTire1\Master\Material;

class IvtBal extends BaseModel
{
    protected $table = 'ivt_bals';
    public $timestamps = false;
    public static $defaultWhCode = 'G01';

    public static function boot()
    {
        parent::boot();
        static::saving(function ($IvtBal) {
        });
    }

    protected $fillable = [
        'matl_id',
        'matl_uom',
        'wh_id',
        'wh_code',
        'batch_code',
        'qty_oh',
        'wh_id',
        'wh_code',
    ];


    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

}
