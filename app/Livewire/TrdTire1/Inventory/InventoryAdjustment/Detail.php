<?php

namespace App\Livewire\TrdTire1\Inventory\InventoryAdjustment;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Inventories\IvttrHdr;
use App\Models\TrdTire1\Master\{Partner, Material};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdTire1\Master\MasterService;
use Illuminate\Support\Facades\{Session};
use Exception;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $inputs = [];
    public $paymentTerms = [];
    public $partnerSearchText = '';
    public $selectedPartners = [];
    public $warehouses;
    public $warehousesType;
    public $partners;
    public $transaction_id;
    public $payments;
    public $trType;
    public $deletedItems = [];
    public $newItems = [];

    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];

    public $returnIds = [];
    public $currencyRate = 0;
    protected $masterService;
    public $isPanelEnabled = "false";
    public $isEdit = "false";

    public $rules  = [
        'inputs.tr_date' => 'required',
    ];
    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'delete' => 'delete',
        'updateAmount' => 'updateAmount',
        'updateDiscount' => 'updateDiscount',
        'updateDPP' => 'updateDPP',
        'updatePPN' => 'updatePPN',
        'updateTotalTax' => 'updateTotalTax',
    ];
    #endregion

    #region Populate Data methods

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.tax'      => $this->trans('tax'),
        ];

        $this->isEdit = $this->isEditOrView() ? 'true' : 'false';
        $this->masterService = new MasterService();
        $this->warehouses = $this->masterService->getWarehouse();
        $this->warehousesType = $this->masterService->getWarehouseType();
        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }
            $this->object = IvttrHdr::with('IvttrDtl')->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['tr_type'] = $this->object->tr_type;
            $this->inputs['tr_date'] = $this->object->tr_date;
            $this->inputs['tr_id'] = $this->object->tr_id;
            $this->inputs['wh_code'] = $this->object->IvttrDtl->first()->wh_code ?? null;
            $this->inputs['tr_descr'] = $this->object->IvttrDtl->first()->tr_descr ?? null;
        }
        if (!$this->isEditOrView()) {
            $this->isPanelEnabled = "true";
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new IvttrHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_date']  = date('Y-m-d');
        $this->inputs['wh_code'] = "";
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
    #endregion

    #region CRUD Methods

    public function onValidateAndSave()
    {
        if ($this->actionValue == 'Edit') {
            if ($this->object->isOrderCompleted()) {
                $this->dispatch('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
                return;
            }
        }

        $warehouseType = ConfigConst::where('str1', $this->inputs['tr_type'])->first();
        if ($warehouseType) {
            $this->inputs['tr_type'] = $warehouseType->str1;
        }

        $this->object->saveOrderHeader($this->appCode, $this->trType, $this->inputs, 'SALESORDER_LASTID');
        if ($this->actionValue == 'Create') {
            return redirect()->route($this->appCode . '.Inventory.InventoryAdjustment.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);
        }
    }

    public function delete()
    {
        try {
            if ($this->object->isOrderCompleted()) {
                $this->dispatch('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
                return;
            }

            if (!$this->object->isOrderEnableToDelete()) {
                $this->dispatch('warning', 'Nota ini tidak bisa delete, karena memiliki material yang sudah dijual.');
                return;
            }

            if (isset($this->object->status_code)) {
                $this->object->status_code =  Status::NONACTIVE;
            }
            $this->object->save();
            $this->object->delete();
            $messageKey = 'generic.string.disable';
            $this->dispatch('success', __($messageKey));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
    }
    #endregion
}
