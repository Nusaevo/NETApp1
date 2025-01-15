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
        'category',
        'dimension',
        'wgt',
        'selling_price',
        'cost',
        'specs',
        'tag',
        'reserved',
        'stock',
        'point'
    ];
    public function MatlUom()
    {
        return $this->hasMany(MatlUom::class, 'matl_id');
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
            return rupiah($this->selling_price);

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
        return self::whereHas('ivtBal', function ($query) {
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


    public static function retrieveBomDetail($detail)
    {
        $baseMaterial = ConfigConst::where('id', $detail->base_matl_id)->first();
        $formattedDetail = populateArrayFromModel($detail);

        $formattedDetail['base_matl_id'] = strval($baseMaterial->id) . "-" . strval($baseMaterial->note1);
        $formattedDetail['base_matl_id_value'] = $baseMaterial->id;
        $formattedDetail['base_matl_id_note'] = $baseMaterial->note1;

        $decodedData = $detail->jwl_sides_spec;

        switch ($formattedDetail['base_matl_id_note']) {
            case self::JEWELRY:
                $formattedDetail['purity'] = $decodedData['purity'] ?? null;
                break;
            case self::DIAMOND:
                $formattedDetail['shapes'] = $decodedData['shapes'] ?? null;
                $formattedDetail['clarity'] = $decodedData['clarity'] ?? null;
                $formattedDetail['color'] = $decodedData['color'] ?? null;
                $formattedDetail['cut'] = $decodedData['cut'] ?? null;
                $formattedDetail['gia_number'] = $decodedData['gia_number'] ?? 0;
                break;
            case self::GEMSTONE:
                $formattedDetail['gemstone'] = $decodedData['gemstone'] ?? null;
                $formattedDetail['gemcolor'] = $decodedData['gemcolor'] ?? null;
                break;
            case self::GOLD:
                $formattedDetail['production_year'] = $decodedData['production_year'] ?? 0;
                $formattedDetail['ref_mark'] = $decodedData['ref_mark'] ?? null;
                break;
        }

        return $formattedDetail;
    }

    public static function generateMaterialDescriptionsFromBOMs($matl_boms)
    {
        $materialDescriptions = '';

        if ($matl_boms && count($matl_boms) > 0) {
            $bomIds = array_filter(array_column($matl_boms, 'base_matl_id_value'), function($value) {
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

}
