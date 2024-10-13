<?php

namespace App\Models\TrdRetail1\Master;

use App\Helpers\SequenceUtility;
use App\Models\TrdRetail1\Base\TrdRetail1BaseModel;
use App\Models\Base\BaseModel\Attachment;
use App\Models\TrdRetail1\Inventories\IvtBal;
use App\Models\TrdRetail1\Transaction\OrderDtl;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class Material extends TrdRetail1BaseModel
{
    protected $table = 'materials';
    use SoftDeletes;

    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'code',
        'name',
        'descr',
        'type_code',
        'class_code',
        'partner_id',
        'partner_code',
        'matl_price',
        'sellprc_calc_method',
        'price_markup_id',
        'price_markup_code',
        'buying_price_usd',
        'buying_price_idr',
        'selling_price_usd',
        'selling_price_idr',
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
    public function getSellingPriceTextAttribute()
    {
        $orderedMaterial = !is_null($this->partner_id);
        if ($orderedMaterial) {
            return rupiah($this->selling_price_idr);
        } else {
            return dollar($this->selling_price_usd);
        }
    }

    public function getBuyingPriceTextAttribute()
    {
        $orderedMaterial = !is_null($this->partner_id);
        if ($orderedMaterial) {
            return rupiah($this->buying_price_idr);
        } else {
            return dollar($this->buying_price_usd);
        }
    }

    public function getSellingPriceAttribute()
    {
        $orderedMaterial = !is_null($this->partner_id);
        if ($orderedMaterial) {
            return $this->selling_price_idr;
        } else {
            return $this->selling_price_usd;
        }
    }

    public function getBuyingPriceAttribute()
    {
        $orderedMaterial = !is_null($this->partner_id);
        if ($orderedMaterial) {
            return $this->buying_price_idr;
        } else {
            return $this->buying_price_usd;
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
        return IvtBal::where('matl_id', $this->id)
            ->where('qty_oh', '>', 0)
            ->exists();
    }

    public function getStockAttribute()
    {
        return $this->ivtBal ? $this->ivtBal->qty_oh : 0;
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
        return OrderDtl::where('matl_id', $matl_id)->exists();
    }

    public function isItemExistonAnotherPO(int $matl_id): bool
    {
        return OrderDtl::where('matl_id', $matl_id)
            ->where('tr_type', 'PO')
            ->exists();
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
