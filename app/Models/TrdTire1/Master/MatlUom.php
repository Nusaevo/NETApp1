<?php

namespace App\Models\TrdTire1\Master;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;
use App\Models\TrdTire1\Inventories\IvtBal;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TrdTire1\Inventories\IvtBalUnit;

class MatlUom extends BaseModel
{
    protected $table = 'matl_uoms';
    use SoftDeletes;

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
        'qty_fgi',
        'selling_price',
        'last_buying_price',
        'last_buying_date'
        // 'initial_qty_fgr' // Pastikan field ini ada jika diperlukan
    ];

    protected static function boot()
    {
        parent::boot();
    }

    public function Material()
    {
        return $this->belongsTo(Material::class, 'matl_id');
    }

    public function ivtBal()
    {
        return $this->hasMany(IvtBal::class, 'matl_id');
    }

    public function ivtBalUnit()
    {
        return $this->hasMany(IvtBalUnit::class, 'matl_id');
    }

    public function scopeFindMaterialId($query, $matl_id)
    {
        return $query->where('matl_id', $matl_id);
    }

    public static function updLastBuyingPrice(
        int $matlId,
        string $matlUom,
        float $lastBuyingPrice,
        string|Carbon $lastBuyingDate
    ): void {
        $matlUomRec = self::where([
            'matl_id'  => $matlId,
            'matl_uom' => $matlUom,
        ])->first();
        if ($matlUomRec) {
            if (is_null($matlUomRec->last_buying_date) || $lastBuyingDate >= $matlUomRec->last_buying_date) {
                $matlUomRec->update([
                    'last_buying_price' => $lastBuyingPrice,
                    'last_buying_date' => $lastBuyingDate,
                ]);
            }
        }
    }
}
