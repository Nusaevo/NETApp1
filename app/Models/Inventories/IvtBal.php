<?php

namespace App\Models\Inventories;
use App\Models\Base\BaseModel;

class IvtBal extends BaseModel
{
    protected $table = 'ivt_bals';

    public static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'matl_id',
        'matl_uom',
        'wh_id',
        'wh_code',
        'batch_code',
        'qty_oh',
        'wh_bin',
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
