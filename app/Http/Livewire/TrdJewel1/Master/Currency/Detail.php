<?php

namespace App\Http\Livewire\TrdJewel1\Master\Currency;

use App\Http\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Master\GoldPriceLog;
use App\Models\SysConfig1\ConfigConst;
use Illuminate\Validation\Rule;
use DB;
use Exception;

class Detail extends BaseComponent
{
    public $inputs = [];
    public $currencies = [];

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.curr_id' => $this->trans('currency'),
            'inputs.curr_rate' => $this->trans('currency_rate'),
            'inputs.goldprice_curr' => $this->trans('gold_price_currency'),
            'inputs.goldprice_basecurr' => $this->trans('gold_price_base'),
        ];
    }

    public $rules = [
        'inputs.curr_id' => 'required|integer',
        'inputs.curr_rate' => 'required',
        'inputs.goldprice_curr' => 'required',
        'inputs.goldprice_basecurr' => 'required',
    ];

    protected function onLoadForEdit()
    {
        $this->object = GoldPriceLog::withTrashed()->find($this->objectIdValue);
        $this->inputs = populateArrayFromModel($this->object);
    }

    public function render()
    {
        return view($this->renderRoute);
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    public function onReset()
    {
        $this->reset('inputs');
        $this->inputs['log_date']  = date('d-m-Y');
        $this->object = new GoldPriceLog();
    }

    public function refreshCurrencies()
    {
        $data = ConfigConst::GetCurrencyData($this->appCode);
        $currencies = $data->map(function ($item) {
            return [
                'label' => $item->str1 . " - " . $item->str2,
                'value' => $item->id,
            ];
        });
        $defaultCurrency = $currencies->first();
        $this->currencies = $currencies;
        $this->inputs['curr_id'] = $defaultCurrency['value'];
    }

    protected function onPopulateDropdowns()
    {
        $this->refreshCurrencies();
    }

    public function onValidateAndSave()
    {
        if (isset($this->inputs['log_date'])) {
            $existingLog = GoldPriceLog::whereDate('log_date', dateFormat($this->inputs['log_date'],'Y-m-d'))
                                        ->where('id', '!=', $this->object->id ?? null)
                                        ->exists();
            if ($existingLog) {
                $this->addError('inputs.log_date', $this->trans('message.log_date_already_exists'));
                throw new Exception($this->trans('message.log_date_already_exists'));
            }
        }

        $this->object->fillAndSanitize($this->inputs);
        $this->object->save();
    }


    public function currencyChanged()
    {
        if (empty($this->inputs['curr_rate']) || empty($this->inputs['goldprice_basecurr'])) {
            return null;
        }

        $baseCurrency = toNumberFormatter($this->inputs['goldprice_basecurr']);
        $currentRate = toNumberFormatter($this->inputs['curr_rate']);

        $this->inputs['goldprice_curr'] = numberFormat($baseCurrency / $currentRate, 2);
    }

    public function changeStatus()
    {
        $this->change();
    }
}
