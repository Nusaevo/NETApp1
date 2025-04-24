<?php

namespace App\Livewire\TrdRetail1\Transaction\SalesOrder;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdRetail1\Transaction\{OrderHdr, OrderDtl};
use App\Models\TrdRetail1\Master\{Partner, Material, MatlUom};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdRetail1\Master\MasterService;
use Exception;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $object_detail;
    public $inputs = [];
    public $input_details = [];
    public $return_details = [];

    public $customers = [];
    public $partners = [];
    public $partnerSearchText = '';
    public $selectedPartners = [];
    public $isPrint = true;


    public $warehouses;
    public $deletedItems = [];
    public $newItems = [];
    public $trType = "SO";

    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];

    public $returnIds = [];
    public $currencyRate = 0;

    protected $masterService;
    public $isPanelEnabled = "true";
    public $total_amount = 0;
    public $materialList = [];
    public $searchTerm = '';
    public $selectedMaterials = [];
    public $materialCategories = [];
    public $filterCategory = '';
    public $filterBrand = '';
    public $filterType = '';
    public $kategoriOptions = '';
    public $brandOptions = '';
    public $typeOptions = '';

    public $warehouseOptions = [];
    public $uomOptions = [];
    public $payments;

    public $materials;
    public $wh_code='';
    public $rules  = [
        'inputs.tr_date' => 'required',
        'inputs.partner_id' => 'required',
        'inputs.payment_term_id' => 'required',
        'input_details.*.qty' => 'required|numeric|min:1',
        'input_details.*.matl_id' => 'required',
        'wh_code' => 'required',
        'input_details.*.matl_uom' => 'required',
    ];
    protected $messages = [
        'input_details.*.qty.min'            => 'Isi Qty',
    ];
    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'delete' => 'delete',
    ];
    #endregion

    #region Populate Data methods
    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.tr_date'      => $this->trans('tr_date'),
            'inputs.partner_id'      => $this->trans('supplier'),
            'inputs.wh_code'      => $this->trans('warehouse'),
        ];

        $this->masterService = new MasterService();
        $this->payments = $this->masterService->getPaymentTerm();
        $this->warehouses = $this->masterService->getWarehouse();
        $this->partners = $this->masterService->getCustomers();

        $this->materials = $this->masterService->getMaterials();
        $this->kategoriOptions = $this->masterService->getMatlCategoryData();
        $this->brandOptions =   $this->masterService->getMatlBrandData();
        $this->typeOptions =   $this->masterService->getMatlTypeData();
        $this->warehouseOptions = $this->masterService->getWarehouseData();
        $this->wh_code = $this->warehouseOptions[0]['value'] ?? null;
        $this->uomOptions = $this->masterService->getMatlUOMData();
        if($this->isEditOrView())
        {
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['status_code_text'] = $this->object->status_Code_text;
            $this->inputs['partner_name'] = $this->object->Partner->code." - ".$this->object->Partner->name;
            $this->loadDetails();
        }
        if(!empty($this->input_details)) {
            $this->isPanelEnabled = "false";
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new OrderHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_date']  = date('Y-m-d');
        $this->inputs['tr_type']  = $this->trType;
        $this->inputs['curr_id'] = ConfigConst::CURRENCY_RUPIAH_ID;
        $this->inputs['curr_code'] = "IDR";
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
        // Jika mode edit dan order sudah completed, tampilkan peringatan dan hentikan proses.
        if ($this->actionValue === 'Edit') {
            if ($this->object->isOrderCompleted()) {
                throw new Exception('Nota ini tidak bisa di edit, karena status sudah Completed');
            }
        }
        if (!isNullOrEmptyNumber($this->inputs['partner_id']) && $this->inputs['partner_id'] > 0) {
            $partner = Partner::find($this->inputs['partner_id']);
            $this->inputs['partner_code'] = $partner ? $partner->code : null;
        } else {
            throw new Exception('Harap isi Customer terlebih dahulu');
        }

        if (!isNullOrEmptyNumber($this->inputs['payment_term_id'] && $this->inputs['payment_term_id'] > 0)) {
            $this->masterService = new MasterService();
            $paymentTerm = $this->masterService->getPaymentTermById($this->inputs['payment_term_id']);
            $this->inputs['payment_term'] = $paymentTerm ?? "";
        } else {
            throw new Exception('Harap isi Pembayaran terlebih dahulu');
        }
        // Persiapkan array detail untuk disimpan.
        // Contoh: Update urutan (tr_seq), tambahkan info warehouse dan material.
        $itemsToSave = [];
        foreach ($this->input_details as $index => $detail) {
            $detail['tr_seq'] = $index + 1;          // Pastikan urutan detail tersimpan dengan benar.
            $detail['tr_id'] = $this->object->tr_id;   // Gunakan tr_id yang sudah di-generate.
            $detail['trhdr_id'] = $this->object->id;    // Header ID pada OrderHdr.
            $detail['tr_type'] = $this->trType;
            $detail['wh_code'] = $this->wh_code;        // Misalnya warehouse code disediakan dari properti komponen.

            // Cari konfigurasi warehouse jika diperlukan.
            $configConst = ConfigConst::where('const_group', 'MWAREHOUSE_LOCL1')
                ->where('str1', $detail['wh_code'] ?? '')
                ->first();
            $detail['wh_id'] = $configConst ? $configConst->id : null;

            // Ambil data material untuk mendapatkan material code.
            $material = Material::withTrashed()->find($detail['matl_id'] ?? null);
            $detail['matl_code'] = $material ? $material->code : null;

            // Jika diperlukan, tambahkan atau ubah field lain di detail.
            $itemsToSave[] = $detail;
        }

        // Simpan header dan detail secara terpadu menggunakan method saveOrder.
        // Parameter: tipe transaksi, data header, data detail, dan flag untuk membuat header delivery/billing/payment.
        $this->object->saveOrder(
            $this->trType,
            $this->inputs,
            $itemsToSave,
            true
        );

        // Redirect bila aksi adalah Create, atau lakukan tindakan lanjutan sesuai kebutuhan.
        if ($this->actionValue === 'Create') {
            return redirect()->route($this->appCode . '.Transaction.SalesOrder.Detail', [
                'action'   => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id),
            ]);
        }
        if($this->isPrint) {
            return redirect()->route(
                $this->appCode . '.Transaction.SalesOrder.PrintPdf',
                [
                    'action'   => encryptWithSessionKey('Edit'),
                    'objectId' => encryptWithSessionKey($this->object->id),
                ]
            );
        }
    }
    public function SaveAndPrint()
    {
        $this->isPrint = true;
        $this->Save();
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
    public function openPartnerDialogBox()
    {
        $this->partnerSearchText = '';
        $this->customers = [];
        $this->selectedPartners = [];
        $this->dispatch('openPartnerDialogBox');
    }
    public function searchPartners()
    {
        if (!empty($this->partnerSearchText)) {
            $searchTerm = strtoupper($this->partnerSearchText);
            $this->customers = Partner::where('grp',Partner::SUPPLIER)
            ->where(function ($query) use ($searchTerm) {
                 $query->whereRaw("UPPER(code) LIKE ?", ["%{$searchTerm}%"])
                       ->orWhereRaw("UPPER(name) LIKE ?", ["%{$searchTerm}%"]);
            })
            ->get();
        }else{
            $this->dispatch('error', "Mohon isi kode atau nama supplier");
        }
    }

    public function selectPartner($partnerId)
    {
        $key = array_search($partnerId, $this->selectedPartners);

        if ($key !== false) {
            unset($this->selectedPartners[$key]);
            $this->selectedPartners = array_values($this->selectedPartners);
        } else {
            $this->selectedPartners[] = $partnerId;
        }
    }


    // public function confirmSelection()
    // {
    //     if (empty($this->selectedPartners)) {
    //         $this->dispatch('error', "Silakan pilih satu supplier terlebih dahulu.");
    //         return;
    //     }
    //     if (count($this->selectedPartners) > 1) {
    //         $this->dispatch('error', "Hanya boleh memilih satu supplier.");
    //         return;
    //     }
    //     $partner = Partner::find($this->selectedPartners[0]);

    //     if ($partner) {
    //         $this->inputs['partner_id'] = $partner->id;
    //         $this->inputs['partner_name'] = $partner->code . " - " . $partner->name;
    //         $this->dispatch('success', "Supplier berhasil dipilih.");
    //         $this->dispatch('closePartnerDialogBox');
    //     }
    // }
    public function addItem()
    {
        $this->input_details[] = [
            'matl_id' => null,
            'qty' => null,
            'price' => 0.0
        ];
    }
    public function onMaterialChanged($key, $matl_id)
    {
        if ($matl_id) {
            $duplicate = collect($this->input_details)->contains(function ($detail, $index) use ($key, $matl_id) {
                return $index != $key && isset($detail['matl_id']) && $detail['matl_id'] == $matl_id;
            });

            if ($duplicate) {
                $this->dispatch('error', 'Material sudah ada dalam daftar.');
                return;
            }

            $material = Material::find($matl_id);
            if ($material) {
                $this->input_details[$key]['matl_id'] = $material->id;
                $this->input_details[$key]['matl_code'] = $material->code;
                $this->input_details[$key]['price'] = $material->selling_price;
                $this->input_details[$key]['matl_uom'] = $material->DefaultUom->matl_uom ?? null;
                $this->input_details[$key]['matl_descr'] = $material->name;
                $this->input_details[$key]['price'] = $material->DefaultUom->selling_price ?? 0;
                $attachment = optional($material->Attachment)->first();
                $this->input_details[$key]['image_url'] = $attachment ? $attachment->getUrl() : '';
                $this->updateItemAmount($key);
            } else {
                $this->dispatch('error', 'Material_not_found');
            }
        }
    }
    public function onUomChanged($key, $uomId)
    {
        $materialId = $this->input_details[$key]['matl_id'] ?? null;

        if ($materialId) {
            $matlUom = MatlUom::where('matl_id', $materialId)
                ->where('matl_uom', $uomId)
                ->first();

            if ($matlUom) {
                $this->input_details[$key]['price'] = $matlUom->selling_price;
            }
        }
        $this->updateItemAmount($key);
    }

    public function updateItemAmount($key)
    {
        if (!empty($this->input_details[$key]['qty']) && !empty($this->input_details[$key]['price'])) {
            $amount = $this->input_details[$key]['qty'] * $this->input_details[$key]['price'];
            $this->input_details[$key]['amt'] = $amount;
        } else {
            $this->input_details[$key]['amt'] = 0;
        }
        $this->input_details[$key]['amt_idr'] = rupiah($this->input_details[$key]['amt']);

        // Update totals immediately
        $this->recalculateTotals();
    }

    public function recalculateTotals()
    {
        $this->total_amount = array_sum(
            array_map(function ($detail) {
                $qty = $detail['qty'] ?? 0;
                $price = $detail['price'] ?? 0;
                $amount = $qty * $price;
                return $amount;
            }, $this->input_details),
        );

        $this->total_amount = round($this->total_amount, 2);
    }

    public function deleteItem($index)
    {
        try {

            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            $this->dispatch('success', __('generic.string.delete_item'));
            $this->recalculateTotals();
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id, $this->object->tr_type)->orderBy('tr_seq')->get();
            foreach ($this->object_detail as $key => $detail) {
                $this->input_details[$key] = populateArrayFromModel($detail);
                $this->input_details[$key]['wh_code'] = $this->warehouseOptions[0]['value'] ?? null;
                $material = Material::withTrashed()->find($detail->matl_id);
                $attachment = optional($material->Attachment)->first();
                $this->input_details[$key]['image_url'] = $attachment ? $attachment->getUrl() : '';
                $this->updateItemAmount($key);
            }
        }
    }

    public function openItemDialogBox()
    {
        $this->searchTerm = '';
        $this->materialList = [];
        $this->selectedMaterials = [];
        $this->dispatch('openItemDialogBox');
    }

    public function searchMaterials()
    {
        $query = Material::query();

        if (!empty($this->searchTerm)) {
            $searchTermUpper = strtoupper($this->searchTerm);
            $query->where(function ($query) use ($searchTermUpper) {
                $query
                    ->whereRaw('UPPER(materials.code) LIKE ?', ['%' . $searchTermUpper . '%'])
                    ->orWhereRaw('UPPER(materials.name) LIKE ?', ['%' . $searchTermUpper . '%']);
            });
        }

        // Apply filters
        if (!empty($this->filterCategory)) {
            $query->where('category', $this->filterCategory);
        }
        if (!empty($this->filterBrand)) {
            $query->where('brand', $this->filterBrand);
        }
        if (!empty($this->filterType)) {
            $query->where('class_code', $this->filterType);
        }

        $this->materialList = $query->get();
    }

    public function selectMaterial($materialID)
    {
        $key = array_search($materialID, $this->selectedMaterials);

        if ($key !== false) {
            unset($this->selectedMaterials[$key]);
            $this->selectedMaterials = array_values($this->selectedMaterials);
        } else {
            $this->selectedMaterials[] = $materialID;
        }
    }

    public function confirmSelection()
    {
        if (empty($this->selectedMaterials)) {
            $this->dispatch('error', 'Silakan pilih setidaknya satu material terlebih dahulu.');
            return;
        }

        foreach ($this->selectedMaterials as $matl_id) {
            $exists = collect($this->input_details)->contains('matl_id', $matl_id);

            if ($exists) {
                $this->dispatch('error', "Material dengan ID $matl_id sudah ada dalam daftar.");
                continue;
            }

            // Jika tidak duplikat, tambahkan ke daftar
            $key = count($this->input_details);
            $this->input_details[] = [
                'matl_id' => $matl_id,
                'qty' => null,
                'price' => 0.0
            ];
            $this->onMaterialChanged($key, $matl_id);
        }

        $this->dispatch('success', 'Item berhasil dipilih.');
        $this->dispatch('closeItemDialogBox');
    }

    #endregion
}
