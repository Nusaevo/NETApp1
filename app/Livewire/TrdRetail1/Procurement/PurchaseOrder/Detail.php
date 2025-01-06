<?php

namespace App\Livewire\TrdRetail1\Procurement\PurchaseOrder;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdRetail1\Transaction\OrderHdr;
use App\Models\TrdRetail1\Transaction\OrderDtl;
use App\Models\TrdRetail1\Master\Partner;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdRetail1\Master\Material;
use App\Enums\Status;
use App\Services\TrdRetail1\Master\MasterService;
use Exception;


class Detail extends BaseComponent
{
    #region Constant Variables
    public $object_detail;
    public $inputs = [];
    public $input_details = [];

    public $suppliers;
    public $warehouses;
    public $payments;
    public $deletedItems = [];
    public $newItems = [];

    public $total_amount = 0;
    public $trType = "PO";

    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];

    public $returnIds = [];
    public $currencyRate = 0;

    protected $masterService;
    public $isPanelEnabled = "true";

    public $rules  = [
        // 'inputs.partner_id' =>  'required',
        // 'inputs.wh_code' =>  'required',
        'inputs.tr_date' => 'required',
        'input_details.*.price' => ['required', 'not_in:0'],
        // 'input_details.*.price' => 'required',
        // 'input_details.*.qty' => 'required',
    ];
    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'changeItem'  => 'changeItem',
        'materialSaved' => 'materialSaved',
        'delete' => 'delete',
        'saveCheck' => 'saveCheck',
    ];
    #endregion

    #region Populate Data methods
    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.tr_date'      => $this->trans('tr_date'),
            'inputs.partner_id'      => $this->trans('supplier'),
            'inputs.wh_code'      => $this->trans('warehouse'),
            'input_details.*'              => $this->trans('product'),
            'input_details.*.matl_id' => $this->trans('product'),
            'input_details.*.qty' => $this->trans('qty'),
            'input_details.*.price' => $this->trans('price'),
        ];

        $this->masterService = new MasterService();
        $this->suppliers = $this->masterService->getSuppliers();
        $this->warehouses = $this->masterService->getWarehouse();
        if($this->isEditOrView())
        {
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->retrieveMaterials();
        }
        if(!empty($this->input_details)) {
            $this->isPanelEnabled = "false";
        }
    }
    protected function retrieveMaterials()
    {
        if ($this->object) {
            $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id, $this->trType)->orderBy('tr_seq')->get();
            if (is_null($this->object_detail) || $this->object_detail->isEmpty()) {
                return;
            }
            foreach ($this->object_detail as $key => $detail) {
                $this->input_details[$key] =  populateArrayFromModel($detail);
                $this->input_details[$key]['name'] = $detail->Material?->name;
                $this->input_details[$key]['id'] = $detail->id;
                $this->input_details[$key]['selling_price'] = $detail->Material->jwl_selling_price;
                $this->input_details[$key]['sub_total'] = $detail->amt;
                $this->input_details[$key]['isOrderedMaterial'] = $detail->Material->isOrderedMaterial();
                $this->input_details[$key]['barcode'] = $detail->Material?->MatlUom[0]->barcode;
                $this->input_details[$key]['image_path'] = $detail->Material?->Attachment->first() ? $detail->Material->Attachment->first()->getUrl() : null;
            }
            $this->countTotalAmount();
        }
    }


    public function onReset()
    {
        $this->reset('inputs');
        $this->reset('input_details');
        $this->object = new OrderHdr();
        $this->object_detail = [];
        $this->total_amount = 0;
        $this->inputs['tr_date']  = date('Y-m-d');
        $this->inputs['tr_type']  = $this->trType;
        $this->inputs['curr_id'] = ConfigConst::CURRENCY_DOLLAR_ID;
        $this->inputs['curr_code'] = "USD";
        $this->inputs['wh_code'] = 18;
        $this->inputs['partner_id'] = 0;
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
        if($this->actionValue == 'Edit')
        {
            if($this->object->isOrderCompleted())
            {
                $this->dispatch('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
                return;
            }

        }

        // foreach ($this->input_details as $index => $detail) {
        //     $material = Material::find($detail['matl_id']);
        //     if ($material && !$material->isOrderedMaterial()) {
        //         // Check if the price is set for ordered material
        //         if (empty($detail['price']) || $detail['price'] <= 0) {
        //             $this->dispatch('error', 'Harga wajib diisi untuk barang yang bukan pesanan.');
        //             $this->addError("input_details.$index.price", 'Harga wajib diisi untuk barang yang bukan pesanan.');
        //             return;
        //         }
        //     }
        // }

        if (!isNullOrEmptyNumber($this->inputs['partner_id'])) {
            $partner = Partner::find($this->inputs['partner_id']);
            $this->inputs['partner_code'] = $partner->code;
        }
        $this->inputs['wh_code'] = 18;
        $this->object->saveOrder($this->appCode, $this->trType, $this->inputs, $this->input_details , true);
        if($this->actionValue == 'Create')
        {
            return redirect()->route($this->appCode.'.Procurement.PurchaseOrder.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);
        }
        $this->retrieveMaterials();
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

            //$this->updateVersionNumber();
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

    #region Component Events

    #endregion
}
