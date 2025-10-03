<?php

namespace App\Models\TrdTire2\Inventories;

use Illuminate\Database\Eloquent\Model;
use App\Models\Base\BaseModel;
use App\Enums\Constant;
use App\Models\TrdTire2\Master\Material;

class IvtBal extends BaseModel
{
    protected $table = 'ivt_bals';
    public $timestamps = false;
    protected $primaryKey = 'id';

    public static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'matl_id',
        'matl_code',
        'matl_uom',
        'wh_id',
        'wh_code',
        'batch_code',
        'qty_oh',
        'qty_fgr',
        'qty_fgi'
    ];

    protected $casts = [
        'matl_id' => 'integer',
        'wh_id' => 'integer',
        'qty_oh' => 'float',
        'qty_fgr' => 'float',
        'qty_fgi' => 'float',
    ];

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }
    public function material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }
}
