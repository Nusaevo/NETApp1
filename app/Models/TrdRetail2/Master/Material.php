<?php

namespace App\Models\TrdRetail2\Master;

use App\Helpers\SequenceUtility;
use App\Models\Base\BaseModel;
use App\Models\Base\BaseModel\Attachment;
use App\Models\TrdRetail2\Inventories\IvtBal;
use App\Models\TrdRetail2\Transaction\OrderDtl;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Constant;

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

        // static::created(function ($model) {
        //     $model->insertIvtBalData();
        // });

        // static::deleting(function ($material) {
        //     $material->uoms->each(function ($uoms) {
        //         $uoms->delete();
        //     });
        //     $material->boms->each(function ($boms) {
        //         $boms->delete();
        //     });
        // });
    }

    protected $fillable = [
        'code',
        'name',
        'descr',
        'type_code',
        'class_code',
        'partner_id',
        'partner_code',
        'gold_price',
        'jwl_cost',
        'jwl_carat',
        'jwl_base_matl',
        'jwl_category1',
        'jwl_category2',
        'jwl_category3',
        'jwl_category4',
        'jwl_category5',
        'jwl_wgt_gold',
        'jwl_supplier_id',
        'jwl_supplier_code',
        'jwl_supplier_id1',
        'jwl_supplier_id2',
        'jwl_supplier_id3',
        'jwl_sides_carat',
        'jwl_sides_cnt',
        'jwl_sides_matl',
        'jwl_sides_calc_method',
        'jwl_matl_price',
        'jwl_sellprc_calc_method',
        'jwl_price_markup_id',
        'jwl_price_markup_code',
        'jwl_buying_price_usd',
        'jwl_buying_price_idr',
        'jwl_selling_price_usd',
        'jwl_selling_price_idr',
        'brand',
        'dimension',
        'wgt',
        'qty_min',
        'taxable',
        'info',
        'status_code',
        'remark'
    ];

    #region Relations
    public function MatlUom()
    {
        return $this->hasMany(MatlUom::class, 'matl_id');
    }

    public function MatlBom()
    {
        return $this->hasMany(MatlBom::class, 'matl_id')->orderBy('seq');
    }

    public function ivtBal()
    {
        return $this->hasOne(IvtBal::class, 'matl_id')->withDefault([
            'qty_oh' => '$0.00'
        ]);
    }
    #endregion

    #region Attributes
    public function getJwlSellingPriceTextAttribute()
    {
        $orderedMaterial = !isNullOrEmptyNumber($this->partner_id);
        if ($orderedMaterial) {
            return rupiah($this->jwl_selling_price_idr);
        } else {
            return dollar($this->jwl_selling_price_usd);
        }
    }

    // Getter for jwl_buying_price_text
    public function getJwlBuyingPriceTextAttribute()
    {
        $orderedMaterial = !isNullOrEmptyNumber($this->partner_id);

        if ($orderedMaterial) {
            return rupiah($this->jwl_buying_price_idr);
        } else {
            return dollar($this->jwl_buying_price_usd);
        }
    }

    // Getter for jwl_selling_price
    public function getJwlSellingPriceAttribute()
    {
        $orderedMaterial = !isNullOrEmptyNumber($this->partner_id);
        $currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($orderedMaterial) {
            return $this->jwl_selling_price_idr;
        } else {
            return $this->jwl_selling_price_usd * $currencyRate;
        }
    }

    // Getter for jwl_buying_price
    public function getJwlBuyingPriceAttribute()
    {
        $orderedMaterial = !isNullOrEmptyNumber($this->partner_id);
        $currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($orderedMaterial) {
            return $this->jwl_buying_price_idr;
        } else {
            return $this->jwl_buying_price_usd * $currencyRate;
        }
    }

    #endregion


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

    public function hasQuantity()
    {
        $exists = IvtBal::where('matl_id', $this->id)
            ->where('qty_oh', '>', 0)
            ->exists();

        return $exists;
    }


    public function isOrderedMaterial()
    {
        return  !isNullOrEmptyNumber($this->partner_id);
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

    public function isItemExistonOrder(int $matl_id): bool
    {
        $relatedOrderDtl = OrderDtl::where('matl_id', $matl_id)
            ->first();

        if ($relatedOrderDtl) {
            return true;
        }

        return false;
    }


    public function isItemExistonAnotherPO(int $matl_id): bool
    {
        $relatedOrderDtl = OrderDtl::where('matl_id', $matl_id)
            ->where('tr_type', 'PO')
            ->first();

        if ($relatedOrderDtl) {
            return true;
        }

        return false;
    }


    public static function retrieveBomDetail($detail)
    {
        $baseMaterial = ConfigConst::where('id', $detail->base_matl_id)->first();
        $formattedDetail = populateArrayFromModel($detail);

        $formattedDetail['base_matl_id'] = strval($baseMaterial->id) . "-" . strval($baseMaterial->note1);
        $formattedDetail['base_matl_id_value'] = $baseMaterial->id;
        $formattedDetail['base_matl_id_note'] = $baseMaterial->note1;

        $decodedData = json_decode($detail->jwl_sides_spec, true);

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
        $jwl_category1 = $materials['jwl_category1'] ?? '';
        $jwl_category2 = $materials['jwl_category2'] ?? '';
        $jwl_wgt_gold = $materials['jwl_wgt_gold'] ?? '';

        $materialDescriptions = "";

        if (!empty($jwl_category1)) {
            $materialDescriptions .= $jwl_category1;
        }
        if (!empty($jwl_category2)) {
            $materialDescriptions .= " " . $jwl_category2;
        }

        if (!empty($jwl_wgt_gold)) {
            if (!empty($materialDescriptions)) {
                $materialDescriptions .= " ";
            }
            $materialDescriptions .= $jwl_wgt_gold . " GR";
        }
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

        // If buying price equals selling price, markup is 0
        if ($buyingPrice == $sellingPrice) {
            return numberFormat(0);
        }

        $newMarkupPercentage = (($sellingPrice - $buyingPrice) / $buyingPrice) * 100;
        return numberFormat($newMarkupPercentage);
    }
}
