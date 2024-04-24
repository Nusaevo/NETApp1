<?php

namespace App\Models\TrdJewel1\Master;

use App\Models\Base\BaseModel;

class GoldPriceLog extends BaseModel
{
    protected $table = 'goldprice_logs';

    public static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'log_date',
        'curr_id',
        'curr_rate',
        'goldprice_curr',
        'goldprice_basecurr',
    ];

    public function getAllColumnValues($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            if ($attribute == "log_date") {
                return dateFormat($this->attributes[$attribute], 'd-m-Y');
            }
            if ($attribute == "curr_rate") {
                return currencyToNumeric($this->attributes[$attribute]);
            }
            if ($attribute == "goldprice_curr") {
                return currencyToNumeric($this->attributes[$attribute]);
            }
            if ($attribute == "goldprice_basecurr") {
                return currencyToNumeric($this->attributes[$attribute]);
            }
            return $this->attributes[$attribute];
        }
        return null;
    }
}
