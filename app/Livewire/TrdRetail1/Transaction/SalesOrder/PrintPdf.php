<?php

// App\Livewire\TrdRetail1\Transaction\SalesOrder\PrintPdf.php
namespace App\Livewire\TrdRetail1\Transaction\SalesOrder;

use Livewire\Component;
use App\Livewire\Component\BaseComponent;
use App\Models\TrdRetail1\Transaction\OrderHdr;
use App\Services\TrdRetail1\Master\MasterService;

class PrintPdf extends BaseComponent
{
    #region Constant Variables
    public $printSettings = [];
    public $printRemarks = [];
    public $isShowPrice = false;
    public $isShowLogo = false;

    #endregion

    #region Populate Data methods

    public function onPreRender()
    {
        $masterService = new MasterService();
        $this->printSettings = $masterService->getPrintSettings($this->appCode);
        $this->printRemarks = $masterService->getPrintRemarks($this->appCode);

        $this->object = OrderHdr::findOrFail($this->objectIdValue);
        $this->printSettings = $this->object->print_settings ?? $this->printSettings;
        if ($this->object->print_settings) {
            $savedSettings = $this->object->print_settings;
            foreach ($this->printSettings as &$setting) {
                foreach ($savedSettings as $savedSetting) {
                    if ($setting['code'] === $savedSetting['code'] && $setting['value'] === $savedSetting['value']) {
                        $setting['checked'] = $savedSetting['checked'];
                        break;
                    }
                }
            }unset($settings);
            $this->isShowPrice = $this->isSettingChecked($this->printSettings, 'A1');
            $this->isShowLogo = $this->isSettingChecked($this->printSettings, 'A2');
        }

        if ($this->object->print_remarks) {
            $savedRemarks = $this->object->print_remarks;
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
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    #endregion

    #region CRUD Methods


    #endregion

    #region Component Events


    #endregion


}
