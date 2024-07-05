<?php

namespace App\Models\TrdJewel1\Inventories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class IvtBalUnit extends Model
{
    protected $table = 'ivt_bal_units';
    public $timestamps = false;

    public static function boot()
    {
        parent::boot();
        static::saving(function ($IvtBalUnit) {
            $qty_oh = currencyToNumeric($IvtBalUnit->qty_oh);
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

    public function scopeGetByGrp($query, $grp)
    {
        return $query->where('grp', $grp)->get();
    }

    public function scopeFindItemWarehouse($query, $matl_id, $warehouse_id)
    {
        return $query->where('matl_id', $matl_id)->where('wh_id', $warehouse_id);
    }

}
