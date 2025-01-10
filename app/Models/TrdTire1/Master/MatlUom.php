<?php

namespace App\Models\TrdTire1\Master;
use App\Helpers\SequenceUtility;
use App\Models\Base\BaseModel;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdTire1\Inventories\IvtBal;
use App\Models\TrdTire1\Inventories\IvtBalUnit;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;

class MatlUom extends BaseModel
{
    protected $table = 'matl_uoms';
    use SoftDeletes;


    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'matl_uom',
        'matl_id',
        'matl_code',
        'reff_uom',
        'reff_factor',
        'base_factor',
        'price_grp',
        'barcode',
        'qty_oh',
        'qty_fgr',
        'qty_fgi'
    ];

    #region Relations
    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }

    public function ivtBals()
    {
        return $this->hasMany(IvtBal::class, 'matl_id');
    }

    public function ivtBalUnits()
    {
        return $this->hasMany(IvtBalUnit::class, 'matl_id');
    }

    #endregion

    public function scopeFindMaterialId($query, $matl_id)
    {
        return $query->where('matl_id', $matl_id);
    }

    public function createIvtBals()
    {
        $warehouseIds = ConfigConst::GetWarehouse()->pluck('id');

        $inventoryBalData = $warehouseIds->map(function ($warehouseId) {
            return [
                'matl_id' => $this->matl_id,
                'wh_id' => $warehouseId,
                'wh_code' => $warehouseId,
            ];
        })->toArray();

        IvtBal::insert($inventoryBalData);

        $newIvtBals = IvtBal::whereIn('wh_id', $warehouseIds)
                            ->where('matl_id', $this->matl_id)
                            ->get();

        // Prepare the inventory balance units data
        $inventoryBalUnitsData = $newIvtBals->map(function ($ivtBal) {
            return [
                'ivt_id' => $ivtBal->id,
                'matl_id' => $this->matl_id,
                'wh_id' => $ivtBal->wh_id,
                'matl_uom_id' => $this->id,
                'uom' => $this->name,
            ];
        })->toArray();
        IvtBalUnit::insert($inventoryBalUnitsData);
    }

}
