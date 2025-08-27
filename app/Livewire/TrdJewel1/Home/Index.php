<?php
namespace App\Livewire\TrdJewel1\Home;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Master\GoldPriceLog;
use Carbon\Carbon;  // Carbon for handling dates

class Index extends BaseComponent
{
    #region Constant Variables

    public $currencyRates = [];
    public $goldPrices = [];
    public $todayCurrencyRate;
    public $todayGoldPrice;

    #endregion

    #region Populate Data methods

    protected function onPreRender()
    {
        $this->bypassPermissions = true;
        $this->fetchData();
    }


    public function fetchData() {
        $today = Carbon::today()->toDateString();

        $currencyRatesData = GoldPriceLog::orderBy('log_date', 'desc')
            ->take(30)
            ->get(['log_date', 'curr_rate'])
            ->sortBy('log_date');

        $goldPricesData = GoldPriceLog::orderBy('log_date', 'desc')
            ->take(30)
            ->get(['log_date', 'goldprice_basecurr'])
            ->sortBy('log_date');

        // Convert currency values to numeric and store in array
        $this->currencyRates = $currencyRatesData->map(function ($item) {
            return [
                'log_date' => Carbon::parse($item->log_date)->format('Y-m-d'),
                'curr_rate' => (float) $item->curr_rate // Ensure numeric value
            ];
        })->values()->toArray();

        $this->goldPrices = $goldPricesData->map(function ($item) {
            return [
                'log_date' => Carbon::parse($item->log_date)->format('Y-m-d'),
                'goldprice_basecurr' => (float) $item->goldprice_basecurr // Ensure numeric value
            ];
        })->values()->toArray();

        // Debug logging
        \Log::info('TrdJewel1 Home - Currency Rates Data:', [
            'count' => count($this->currencyRates),
            'sample' => array_slice($this->currencyRates, 0, 3)
        ]);

        \Log::info('TrdJewel1 Home - Gold Prices Data:', [
            'count' => count($this->goldPrices),
            'sample' => array_slice($this->goldPrices, 0, 3)
        ]);

        // Fetch today's rate if available
        $todayRate = $currencyRatesData->firstWhere('log_date', $today);
        $todayGold = $goldPricesData->firstWhere('log_date', $today);

        $this->todayCurrencyRate = $todayRate ? rupiah($todayRate->curr_rate) : null;
        $this->todayGoldPrice = $todayGold ? rupiah($todayGold->goldprice_basecurr) : null;
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute, [
            'currencyRates' => $this->currencyRates,
            'goldPrices' => $this->goldPrices,
        ]);

    }

    #endregion

    #region CRUD Methods


    #endregion

    #region Component Events


    #endregion



}
