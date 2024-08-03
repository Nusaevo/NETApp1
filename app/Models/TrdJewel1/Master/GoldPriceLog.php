<?php

namespace App\Models\TrdJewel1\Master;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
class GoldPriceLog extends BaseModel
{
    protected $table = 'goldprice_logs';
    use SoftDeletes;
   
    public static function boot()
    {
        parent::boot();

        static::retrieved(function ($model) {
            if (array_key_exists('goldprice_curr', $model->attributes)) {
                $model->goldprice_curr = numberFormat($model->attributes['goldprice_curr'], 2);
            }
        });
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

    public static function calculateGoldPrice($baseCurrency, $currentRate)
    {
        if (empty($baseCurrency) || empty($currentRate)) {
            return null;
        }

        $baseCurrency = toNumberFormatter($baseCurrency);
        $currentRate = toNumberFormatter($currentRate);

        return numberFormat($baseCurrency / $currentRate, 2);
    }
}
