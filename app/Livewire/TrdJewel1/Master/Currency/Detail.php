<?php

namespace App\Livewire\TrdJewel1\Master\Currency;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Master\GoldPriceLog;
use App\Models\SysConfig1\ConfigConst;
use App\Services\TrdJewel1\Master\MasterService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Exception;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $inputs = [];
    public $currencyData;
    public $currencies = [];
    protected $masterService;

    public $rules = [
        'inputs.curr_id' => 'required|integer',
        'inputs.curr_rate' => 'required',
        'inputs.goldprice_curr' => 'required',
        'inputs.goldprice_basecurr' => 'required',
    ];
    protected $listeners = [
        'changeStatus'  => 'changeStatus'
    ];
    #endregion

    #region Populate Data methods
    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.curr_id' => $this->trans('currency'),
            'inputs.curr_rate' => $this->trans('currency_rate'),
            'inputs.goldprice_curr' => $this->trans('gold_price_currency'),
            'inputs.goldprice_basecurr' => $this->trans('gold_price_base'),
        ];
        if($this->isEditOrView())
        {
            $this->object = GoldPriceLog::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['log_date'] = dateFormat($this->object->log_date, 'd-m-Y');
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->masterService = new MasterService();
        $this->currencyData = $this->masterService->getCurrencyData($this->appCode);
        $this->currencies = $this->currencyData['currencies'];
        $defaultCurrency = $this->currencyData['defaultCurrency'];
        $this->inputs['curr_id'] = $defaultCurrency['value'];
        $this->inputs['log_date']  = date('d-m-Y');
        $this->object = new GoldPriceLog();
    }


    public function render()
    {
        return view($this->renderRoute);
    }

    #endregion

    #region CRUD Methods

    public function onValidateAndSave()
    {
        if (!isNullOrEmptyDateTime($this->inputs['log_date'])) {
            $existingLog = GoldPriceLog::whereDate('log_date', dateFormat($this->inputs['log_date'],'Y-m-d'))
                                        ->where('id', '!=', $this->object->id ?? null)
                                        ->exists();
            if ($existingLog) {
                $this->addError('inputs.log_date', $this->trans('message.log_date_already_exists'));
                throw new Exception($this->trans('message.log_date_already_exists'));
            }
        }
        $this->currencyChanged();

        $this->object->fillAndSanitize($this->inputs);
        $this->object->save();
    }

    public function changeStatus()
    {
        $this->change();
    }
    #endregion

    #region Component Events

    public function currencyChanged()
    {
        if (empty($this->inputs['curr_rate']) || empty($this->inputs['goldprice_basecurr'])) {
            return null;
        }

        $goldPrice = GoldPriceLog::calculateGoldPrice($this->inputs['goldprice_basecurr'], $this->inputs['curr_rate']);
        $this->inputs['goldprice_curr'] = $goldPrice;
    }

    #endregion

}
