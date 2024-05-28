<?php

namespace App\Models\TrdJewel1\Master;
use App\Helpers\SequenceUtility;
use App\Models\Base\BaseModel;
use App\Models\Base\BaseModel\Attachment;
use DB;
class Material extends BaseModel
{
    protected $table = 'materials';
    const JEWELRY = 'JEWELRY';
    const GOLD = 'GOLD';
    const GEMSTONE = 'GEMSTONE';
    const DIAMOND = 'DIAMOND';

    protected static function boot()
    {
        parent::boot();
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
        'jwl_selling_price_usd',
        'jwl_selling_price',
        'jwl_sides_calc_method',
        'jwl_matl_price',
        'jwl_sellprc_calc_method',
        'jwl_price_markup_id',
        'jwl_price_markup_code',
        'jwl_buying_price',
        'brand',
        'dimension',
        'wgt',
        'qty_min',
        'taxable',
        'info',
        'status_code',
    ];

    public static function getNextSequenceValue()
    {
        $sequenceName = 'materials_id_seq';

        $currentSequenceValue = DB::select("SELECT last_value FROM $sequenceName")[0]->last_value;

        return $currentSequenceValue;
    }

    public function getAllColumnValues($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            if ($attribute == "jwl_selling_price") {
                return currencyToNumeric($this->attributes[$attribute]);
            }
            if ($attribute == "jwl_buying_price") {
                return currencyToNumeric($this->attributes[$attribute]);
            }
            if ($attribute == "jwl_wgt_gold") {
                return numberFormat($this->attributes[$attribute]);
            }
            return $this->attributes[$attribute];
        }
        return null;
    }

    public function hasQuantity()
    {
        $exists = DB::table('ivt_bals')
            ->where('matl_id', $this->id)
            ->where('qty_oh', '>', 0)
            ->exists();

        return $exists;
    }

    public function MatlUom()
    {
        return $this->hasMany(MatlUom::class, 'matl_id');
    }

    public function MatlBom()
    {
        return $this->hasMany(MatlBom::class, 'matl_id');
    }

}
