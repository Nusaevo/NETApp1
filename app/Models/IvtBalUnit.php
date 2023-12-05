<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use Illuminate\Support\Str;

class IvtBalUnit extends Model
{
    use HasFactory, SoftDeletes;
    use BaseTrait;

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

    public function getAllColumns()
    {
        return $this->fillable;
    }

    public function getAllColumnValues($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            return $this->attributes[$attribute];
        }
        return null;
    }

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
