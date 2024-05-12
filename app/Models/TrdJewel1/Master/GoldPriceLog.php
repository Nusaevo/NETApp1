<?php

namespace App\Models\TrdJewel1\Master;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;
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

    public static function GetTodayCurrencyRate()
    {
        $currentDate = Carbon::today();
        $currencyRatesData = self::whereDate('log_date', $currentDate)
            ->orderBy('log_date', 'asc')
            ->first(['log_date', 'curr_rate']);

        return $currencyRatesData ? currencyToNumeric($currencyRatesData->curr_rate) : 0;
    }

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
                $numericValue = currencyToNumeric($this->attributes[$attribute]);
                return numberFormat($numericValue, 2);
            }

            if ($attribute == "goldprice_basecurr") {
                return currencyToNumeric($this->attributes[$attribute]);
            }
            return $this->attributes[$attribute];
        }
        return null;
    }
}
