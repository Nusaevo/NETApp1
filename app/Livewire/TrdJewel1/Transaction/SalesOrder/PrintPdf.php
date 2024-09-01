<?php

// App\Livewire\TrdJewel1\Transaction\SalesOrder\PrintPdf.php
namespace App\Livewire\TrdJewel1\Transaction\SalesOrder;

use Livewire\Component;
use App\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Transaction\OrderHdr;
use App\Services\TrdJewel1\Master\MasterService;

class PrintPdf extends BaseComponent
{
    #region Constant Variables
    public $printSettings = [];
    public $printRemarks = [];
    public $isShowPrice = false;

    #endregion

    #region Populate Data methods

    public function onPreRender()
    {
        $masterService = new MasterService();
        $this->printSettings = $masterService->getPrintSettings($this->appCode);
        $this->printRemarks = $masterService->getPrintRemarks($this->appCode);

        $this->object = OrderHdr::findOrFail($this->objectIdValue);
        $this->printSettings = json_decode($this->object->print_settings, true) ?? $this->printSettings;
        if ($this->object->print_settings) {
            $savedSettings = json_decode($this->object->print_settings, true);
            foreach ($this->printSettings as &$setting) {
                foreach ($savedSettings as $savedSetting) {
                    if ($setting['code'] === $savedSetting['code'] && $setting['value'] === $savedSetting['value']) {
                        $setting['checked'] = $savedSetting['checked'];
                        break;
                    }
                }
            }unset($settings);
            $this->isShowPrice = $this->isSettingChecked($this->printSettings, 'A1');
        }

        if ($this->object->print_remarks) {
            $savedRemarks = json_decode($this->object->print_remarks, true);
            foreach ($this->printRemarks as &$remark) {
                foreach ($savedRemarks as $savedRemark) {
                    if ($remark['code'] === $savedRemark['code'] && $remark['value'] === $savedRemark['value']) {
                        $remark['checked'] = $savedRemark['checked'];
                        break;
                    }
                }
            }unset($settings);
        }
    }

    public function isSettingChecked($settings, $code)
    {
        foreach ($settings as $setting) {
            if ($setting['code'] === $code && isset($setting['checked']) && $setting['checked']) {
                return true;
            }
        }
        return false;
    }

    public function render()
    {
        return view($this->renderRoute);
    }

    #endregion

    #region CRUD Methods


    #endregion

    #region Component Events


    #endregion


}
