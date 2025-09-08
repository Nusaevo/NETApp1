<?php

namespace App\Models\TrdTire1\Master;

use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Transaction\OrderHdr;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdRetail1\Inventories\IvtBal;
use App\Models\TrdTire1\Transaction\OrderDtl;

class Material extends BaseModel
{
    protected $table = 'materials';
    const JEWELRY = 'JEWELRY';
    const GOLD = 'GOLD';
    const GEMSTONE = 'GEMSTONE';
    const DIAMOND = 'DIAMOND';
    use SoftDeletes;


    protected static function boot()
    {
        parent::boot();

        static::retrieved(function ($model) {
            if (array_key_exists('jwl_wgt_gold', $model->attributes)) {
                $model->jwl_wgt_gold = numberFormat($model->attributes['jwl_wgt_gold'], 2);
            }
        });
    }
    public static function GetByGrp($group)
    {
        return self::where('group', $group)->get();
    }

    protected $fillable = [
        'code',
        'brand',
        'name',
        'type_code',
        'class_code',
        'category',
        'dimension',
        'wgt',
        'tag',
        'specs',
        'uom',
    ];

    protected $casts = [
        'wgt' => 'float',
    ];

    public function MatlUom()
    {
        return $this->hasOne(MatlUom::class, 'matl_id');
    }
    public function DefaultMatlUom()
    {
        return $this->hasOne(MatlUom::class, 'matl_id', 'id')
            ->where('matl_uom', $this->uom);
    }

    public function IvtBal()
    {
        return $this->hasOne(IvtBal::class, 'matl_id')->withDefault([
            'qty_oh' => '0'
        ]);
    }
    public function OrderDtl()
    {
        return $this->hasMany(OrderDtl::class, 'matl_id', 'id');
    }

    #region Attributes
    public function getSellingPriceTextAttribute()
    {
        return rupiah($this->DefaultMatlUom->selling_price ?? 0);
    }

    // Getter for jwl_buying_price_text
    public function getBuyingPriceTextAttribute()
    {
        return rupiah($this->buying_price);
    }

    #endregion
    public function OrderHdr()
    {
        return $this->belongsTo(OrderHdr::class, 'material_id', 'id');
    }

    public static function getAvailableMaterials()
    {
        return self::whereHas('IvtBal', function ($query) {
            $query->where('qty_oh', '>', 0);
        })
            ->whereNull('materials.deleted_at')
            ->distinct();
    }



    public static function checkMaterialStockByMatlId($matlId)
    {
        return self::query()
            ->join('matl_uoms', 'materials.id', '=', 'matl_uoms.matl_id')
            ->join('ivt_bals', 'materials.id', '=', 'ivt_bals.matl_id')
            ->where('materials.id', $matlId)
            ->where('ivt_bals.qty_oh', '>', 0)
            ->select('materials.*')
            ->first();
    }

    public function isOrderedMaterial()
    {
        return  !isNullOrEmptyNumber($this->partner_id) && !isNullOrEmptyNumber($this->material_id);
    }

    public function getStockAttribute()
    {
        return $this->IvtBal ? $this->IvtBal->qty_oh : 0;
    }

    public static function getListMaterialByBarcode($barcode)
    {
        return self::query()
            ->join('matl_uoms', 'materials.id', '=', 'matl_uoms.matl_id')
            ->leftJoin('ivt_bals', 'materials.id', '=', 'ivt_bals.matl_id')
            ->where('matl_uoms.barcode', $barcode)
            ->select('materials.*', DB::raw('COALESCE(CAST(ivt_bals.qty_oh AS numeric), 0) as qty_oh'))
            ->first();
    }

    public static function generateMaterialDescriptionsFromBOMs($matl_boms)
    {
        $materialDescriptions = '';

        if ($matl_boms && count($matl_boms) > 0) {
            $bomIds = array_filter(array_column($matl_boms, 'base_matl_id_value'), function ($value) {
                return !is_null($value) && $value !== '';
            });

            if (!empty($bomIds)) {
                $bomData = ConfigConst::whereIn('id', $bomIds)->get()->keyBy('id');
            }

            foreach ($matl_boms as $bom) {
                if (isset($bom['base_matl_id_value'])) {
                    $baseMaterial = $bomData[$bom['base_matl_id_value']] ?? null;

                    if ($baseMaterial) {
                        $jwlSidesCnt = $bom['jwl_sides_cnt'] ?? 0;
                        $jwlSidesCarat = $bom['jwl_sides_carat'] ?? 0;
                        $materialDescriptions .= "$jwlSidesCnt $baseMaterial->str1:$jwlSidesCarat ";
                    }
                }
            }
        }

        return $materialDescriptions;
    }

    public static function generateMaterialDescriptions($materials)
    {
        $materialDescriptions = "";

        return $materialDescriptions;
    }

    public static function calculateSellingPrice($buyingPrice, $markup)
    {
        if (empty($buyingPrice)) {
            return null;
        }

        $buyingPrice = toNumberFormatter($buyingPrice);

        if (empty($markup) || toNumberFormatter($markup) == 0) {
            return numberFormat($buyingPrice);
        }

        $markupAmount = $buyingPrice * (toNumberFormatter($markup) / 100);
        return numberFormat($buyingPrice + $markupAmount);
    }

    public static function calculateMarkup($buyingPrice, $sellingPrice)
    {
        if (empty($buyingPrice) || empty($sellingPrice)) {
            return null;
        }

        $buyingPrice = toNumberFormatter($buyingPrice);
        $sellingPrice = toNumberFormatter($sellingPrice);

        if ($buyingPrice <= 0) {
            return null;
        }

        if ($buyingPrice == $sellingPrice) {
            return numberFormat(0);
        }

        $newMarkupPercentage = (($sellingPrice - $buyingPrice) / $buyingPrice) * 100;
        return numberFormat($newMarkupPercentage);
    }
    // Fungsi untuk menghasilkan nama material, bisa dipanggil di dalam model ini
    protected function generateName($brand, $size, $pattern)
    {
        return $brand . ' ' . $size . ' ' . $pattern;
    }
}
