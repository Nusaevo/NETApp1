<?php
namespace App\Http\Livewire\TrdJewel1\Home;

use Livewire\Component;
use App\Models\TrdJewel1\Master\GoldPriceLog;
use Carbon\Carbon;  // Carbon for handling dates

class Index extends Component
{
    public $currencyRates = [];
    public $goldPrices = [];
    public $todayCurrencyRate;
    public $todayGoldPrice;

    public function mount() {
        $this->fetchData();
    }

    public function fetchData() {
        $today = Carbon::today()->toDateString();

        $currencyRatesData = GoldPriceLog::orderBy('log_date', 'desc')
                                  ->take(30)
                                  ->get(['log_date', 'curr_rate']);

        $goldPricesData = GoldPriceLog::orderBy('log_date', 'desc')
                               ->take(30)
                               ->get(['log_date', 'goldprice_basecurr']);

        // Convert currency values to numeric and store in array
        $this->currencyRates = $currencyRatesData->map(function ($item) {
            return [
                'log_date' => $item->log_date,
                'curr_rate' => currencyToNumeric($item->curr_rate)
            ];
        })->toArray();

        $this->goldPrices = $goldPricesData->map(function ($item) {
            return [
                'log_date' => $item->log_date,
                'goldprice_basecurr' => currencyToNumeric($item->goldprice_basecurr)
            ];
        })->toArray();

        // Fetch today's rate if available
        $this->todayCurrencyRate = rupiah(currencyToNumeric($currencyRatesData->firstWhere('log_date', $today)?->curr_rate));
        $this->todayGoldPrice = rupiah(currencyToNumeric($goldPricesData->firstWhere('log_date', $today)?->goldprice_basecurr));
    }

    public function render()
    {
        return view('livewire.trd-jewel1.home.index', [
            'currencyRates' => $this->currencyRates,
            'goldPrices' => $this->goldPrices,
        ]);
    }
}
