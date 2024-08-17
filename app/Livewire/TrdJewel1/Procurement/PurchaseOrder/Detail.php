<?php

namespace App\Livewire\TrdJewel1\Procurement\PurchaseOrder;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Transaction\OrderHdr;
use App\Models\TrdJewel1\Transaction\OrderDtl;
use App\Models\TrdJewel1\Master\Partner;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdJewel1\Master\Material;
use App\Enums\Status;
use App\Models\TrdJewel1\Master\GoldPriceLog;
use App\Services\TrdJewel1\Master\MasterService;
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
        'input_details.*.price' => ['required', 'gt:0'],
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
        $this->currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($this->currencyRate == 0) {
            abort(431, __('generic.string.currency_needed'));
        }
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
        $this->warehouses = $this->masterService->getWarehouses($this->appCode);
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
        $this->inputs['curr_rate'] = GoldPriceLog::GetTodayCurrencyRate();
        $this->inputs['wh_code'] = 18;
        $this->inputs['partner_id'] = 0;
    }

    public function render()
    {
        return view($this->renderRoute);
    }
    #endregion

    #region CRUD Methods

    public function onValidateAndSave()
    {
        if($this->actionValue == 'Edit')
        {
            if($this->object->isOrderCompleted())
            {
                $this->notify('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
                return;
            }

        }

        // foreach ($this->input_details as $index => $detail) {
        //     $material = Material::find($detail['matl_id']);
        //     if ($material && !$material->isOrderedMaterial()) {
        //         // Check if the price is set for ordered material
        //         if (empty($detail['price']) || $detail['price'] <= 0) {
        //             $this->notify('error', 'Harga wajib diisi untuk barang yang bukan pesanan.');
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
            return redirect()->route('TrdJewel1.Procurement.PurchaseOrder.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);
        }
        $this->retrieveMaterials();
    }

    public function materialSaved($material_id)
    {
        try {
            if (isset($this->input_details)) {
                $matl_ids = array_column($this->input_details, 'matl_id');
                if ($this->object->isItemHasBuyBack($material_id)) {
                    $this->notify('error', 'Item ini sudah ada di PO lain.');
                    return;
                }
                if (in_array($material_id, $matl_ids)) {
                    $this->notify('error',__($this->langBasePath.'.message.product_duplicated'));
                    return;
                }

            }

            if(!$this->addDetails($material_id)){
                return;
            }
            $this->SaveWithoutNotification();
            $this->notify('success', __($this->langBasePath.'.message.product_added'));
            $this->dispatch('closeMaterialDialog');
        } catch (Exception $e) {
            $this->notify('error', __('generic.error.save', ['message' => $e->getMessage()]));
        }
    }


    public function deleteDetails($index)
    {
        if ($this->object->isItemHasSalesOrder($this->input_details[$index]['matl_id'])) {
            $this->notify('warning', 'Item ini tidak bisa dihapus, karena item sudah terjual.');
            return;
        }
        if (isset($this->input_details[$index]['id'])) {
            $deletedItemId = $this->input_details[$index]['id'];
            $orderDtl = OrderDtl::withTrashed()->find($deletedItemId);
            if ($orderDtl) {
                $orderDtl->forceDelete();
            }
        }
        unset($this->input_details[$index]);
        $this->input_details = array_values($this->input_details);
        $this->countTotalAmount();
    }


    public function delete()
    {
        try {
            if ($this->object->isOrderCompleted()) {
                $this->notify('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
                return;
            }

            if (!$this->object->isOrderEnableToDelete()) {
                $this->notify('warning', 'Nota ini tidak bisa delete, karena memiliki material yang sudah dijual.');
                return;
            }

            //$this->updateVersionNumber();
            if (isset($this->object->status_code)) {
                $this->object->status_code =  Status::NONACTIVE;
            }
            $this->object->save();
            $this->object->delete();
            $messageKey = 'generic.string.disable';
            $this->notify('success', __($messageKey));
        } catch (Exception $e) {
            $this->notify('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
    }
    #endregion

    #region Component Events

    public function saveCheck()
    {
        if (!$this->object->isNew())
            $this->SaveWithoutNotification();
    }

    public function OpenDialogBox(){
        if ($this->inputs['curr_rate'] == 0) {
            $this->notify('warning',__('generic.string.currency_needed'));
            return;
        }
        if (isNullOrEmptyNumber($this->inputs['partner_id'])) {
            $this->notify('warning', __('generic.error.field_required', ['field' => "Supplier"]));
            $this->addError('inputs.partner_id', __('generic.error.field_required', ['field' => "Supplier"]));
            return;
        }
        $this->dispatch('openMaterialDialog');
    }


    public function changePrice($id, $value)
    {
        if (isset($this->input_details[$id]['qty'])) {
            $total = toNumberFormatter($this->input_details[$id]['qty']) * toNumberFormatter($value);
            $this->input_details[$id]['amt'] = numberFormat($total) ;
            $this->input_details[$id]['price'] = $total;
            $this->countTotalAmount();
            $this->SaveWithoutNotification();
        }
    }

    public function addDetails($material_id = null)
    {
        $detail = [
            'tr_type' => $this->trType,
        ];
        $material = Material::find($material_id);

        if (!$material) {
            $this->notify('error', 'Material tidak ditemukan.');
            return false;
        }

        if (!$this->object->isNew()) {
            if ($this->object->isItemHasOrderedMaterial()) {

                $hasOrderedMaterialInNota = $this->object->OrderDtl->contains(function ($orderDtl) {
                    return $orderDtl->Material && $orderDtl->Material->isOrderedMaterial();
                });

                if ($hasOrderedMaterialInNota && !$material->isOrderedMaterial()) {
                    $this->notify('error','Material yang bukan pesanan tidak boleh digabungkan dengan material pesanan dalam satu nota.');
                    return false;
                }

                if (!$hasOrderedMaterialInNota && $material->isOrderedMaterial()) {
                    $this->notify('error','Material pesanan tidak boleh digabungkan dengan material yang bukan material pesanan dalam satu nota.');
                    return false;
                }
            }
        }

        $detail['matl_id'] = $material->id;
        $detail['matl_code'] = $material->code;
        $detail['matl_descr'] = $material->descr ?? "";
        $detail['name'] = $material->name ?? "";
        $detail['matl_uom'] = $material->MatlUom[0]->id;
        $detail['image_path'] = $material->Attachment->first() ? $material->Attachment->first()->getUrl() : null;
        $detail['barcode'] = $material->MatlUom[0]->barcode;
        $detail['price'] = $material->jwl_buying_price ?? 0;
        $detail['selling_price'] = $material->jwl_selling_price ?? 0;
        $detail['isOrderedMaterial'] = $material->isOrderedMaterial();
        $detail['qty'] = 1;
        $maxTrSeq = $this->object->OrderDtl()->max('tr_seq') ?? 0;
        $maxTrSeq++;
        $detail['tr_seq'] = $maxTrSeq;

        array_push($this->input_details, $detail);
        $newDetail = end($this->input_details);
        $this->newItems[] = $newDetail;
        $this->countTotalAmount();

        return true;
    }


    public function Add()
    {
        // $this->dispatch('materialSaved', 2);
        // $this->dispatch('materialSaved', 3);
        // $this->dispatch('materialSaved', 4);
    }

    public function countTotalAmount()
    {
        $this->total_amount = 0;
        foreach ($this->input_details as $item_id => $input_detail) {
            if (isset($input_detail['price'])) {
                $this->total_amount += $input_detail['price'];
            }
        }
        $this->inputs['amt'] = $this->total_amount;
    }
    #endregion



}
