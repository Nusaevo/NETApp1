<?php

namespace App\Models\TrdJewel1\Inventories;

use Illuminate\Database\Eloquent\Model;
use App\Models\TrdJewel1\Base\TrdJewel1BaseModel;
use Illuminate\Support\Str;
use App\Enums\Constant;

class IvtBalUnit extends TrdJewel1BaseModel
{
    protected $table = 'ivt_bal_units';
    public $timestamps = false;


    public static function boot()
    {
        parent::boot();
        static::saving(function ($IvtBalUnit) {
            $qty_oh = $IvtBalUnit->qty_oh;
            $IvtBalUnit->qty_oh = $qty_oh;
        });
    }

    protected $fillable = [
        'ivt_id',
        'matl_id',
        'matl_uom',
        'wh_id',
        'batch_code',
        'unit_code',
        'qty_oh',
        'status_code',
    ];

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }
}
