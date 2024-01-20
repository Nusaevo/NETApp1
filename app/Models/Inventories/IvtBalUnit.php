<?php

namespace App\Models\Inventories;

use App\Models\BaseModel;
use Illuminate\Support\Str;

class IvtBalUnit extends BaseModel
{
    protected $table = 'ivt_bal_units';

    public static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'ivt_id',
        'matl_id',
        'matl_uom_id',
        'uom',
        'wh_id',
        'batch_code',
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
