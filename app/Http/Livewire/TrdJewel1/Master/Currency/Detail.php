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
        $this->customRules  = [
            'inputs.curr_id' => 'required|integer',
            'inputs.curr_rate' => 'required|numeric|min:0',
            'inputs.goldprice_curr' => 'required|numeric|min:0',
            'inputs.goldprice_basecurr' => 'required|numeric|min:0',
        ];
    }

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
        $this->inputs['log_date']  = date('Y-m-d');
        $this->object = new GoldPriceLog();
    }

    public function refreshCurrencies()
    {
        $data = DB::connection('sys-config1')
            ->table('config_consts')
            ->select('id', 'str1', 'str2', 'num1')
            ->where('const_group', 'MCURRENCY_CODE')
            ->where('app_code', $this->appCode)
            ->where('deleted_at', NULL)
            ->orderBy('seq')
            ->get();

        $currencies = $data->map(function ($item) {
            return [
                'label' => $item->str1 . " - " . $item->str2,
                'value' => $item->id,
                'num1' => currencyToNumeric($item->num1)
            ];
        });

        $defaultCurrency = $currencies->sortByDesc('num1')->first();
        $this->currencies = $currencies->toArray();
        $this->inputs['curr_id'] = $defaultCurrency['value'];
    }

    protected function onPopulateDropdowns()
    {
        $this->refreshCurrencies();
    }

    public function onValidateAndSave()
    {
        if (isset($this->inputs['log_date'])) {
            $existingLog = GoldPriceLog::whereDate('log_date', $this->inputs['log_date'])
                                        ->where('id', '!=', $this->object->id ?? null)
                                        ->exists();
            if ($existingLog) {
                $this->addError('inputs.log_date', $this->trans('message.log_date_already_exists'));
                throw new Exception($this->trans('message.log_date_already_exists'));
            }
        }

        $this->object->fill($this->inputs);
        $this->object->save();
    }

    public function changeStatus()
    {
        $this->change();
    }
}
