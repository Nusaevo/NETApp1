<?php

namespace App\Livewire\TrdJewel1\Transaction\SalesOrder;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{Crypt, DB, Auth};
use App\Models\TrdJewel1\Transaction\{OrderHdr, OrderDtl, BillingDtl, BillingHdr, DelivDtl, DelivHdr};
use App\Models\TrdJewel1\Master\{Partner, Material, MatlUom, GoldPriceLog};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use Carbon\Carbon;
use Exception;
use App\Services\TrdJewel1\Master\MasterService;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $object_detail;
    public $inputs = [];
    public $input_details = [];

    public $partners;
    public $payments;
    public $deletedItems = [];
    public $newItems = [];

    public $total_amount = 0;
    public $trType = "SO";

    public $matl_action = 'Create';
    public $matl_objectId = null;

    public $materialDialogVisible = false;
    public $returnIds = [];
    public $searchTerm = '';
    public $selectedMaterials = [];
    public $currencyRate = 0;
    public $currency = [];
    public $materials = [];
    protected $masterService;

    public $printSettings = [];
    public $printRemarks = [];
    public $isPanelEnabled = "true";

    public $rules  = [
        // 'inputs.payment_term_id' =>  'required',
        // 'inputs.partner_id' =>  'required',
        // 'inputs.wh_code' =>  'required',
        'inputs.tr_date' => 'required',
        'input_details.*.price' => ['required', 'not_in:0'],
    ];
    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'changeItem'  => 'changeItem',
        'materialSaved' => 'materialSaved',
        'delete' => 'delete',
        'tagScanned' => 'tagScanned',
        'saveCheck' => 'saveCheck',
        'onPartnerChanged' => 'onPartnerChanged'
    ];
    #endregion

    #region Populate Data methods
    protected function onPreRender()
    {
        $this->currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($this->currencyRate == 0) {
            abort(422, __('generic.string.currency_needed'));
        }
        $this->customValidationAttributes  = [
            'inputs.tr_date'      => $this->trans('tr_date'),
            'inputs.payment_term_id'      => $this->trans('payment'),
            'inputs.partner_id'      => $this->trans('partner'),
            'input_details.*'              => $this->trans('product'),
            'input_details.*.matl_id' => $this->trans('product'),
            'input_details.*.qty' => $this->trans('qty'),
            'input_details.*.price' => $this->trans('selling_price'),
        ];

        $this->masterService = new MasterService();
        $this->partners = $this->masterService->getCustomers();
        $this->payments = $this->masterService->getPaymentTerm();
        $this->printSettings = $this->masterService->getPrintSettings();
        $this->printRemarks = $this->masterService->getPrintRemarks();
        if($this->isEditOrView())
        {
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            if ($this->object->print_settings) {
                $savedSettings = $this->object->print_settings;
                foreach ($this->printSettings as &$settings) {
                    foreach ($savedSettings as $savedSetting) {
                        if ($settings['code'] === $savedSetting['code'] && $settings['value'] === $savedSetting['value']) {
                            $settings['checked'] = $savedSetting['checked'];
                            break;
                        }
                    }
                } unset($settings);
            }

            if ($this->object->print_remarks) {
                $savedSettings = $this->object->print_remarks;
                foreach ($this->printRemarks as &$settings) {
                    foreach ($savedSettings as $savedSetting) {
                        if ($settings['code'] === $savedSetting['code'] && $settings['value'] === $savedSetting['value']) {
                            $settings['checked'] = $savedSetting['checked'];
                            break;
                        }
                    }
                } unset($settings);
            }
            $this->inputs = populateArrayFromModel($this->object);

            $this->retrieveMaterials();
        }

        if(!empty($this->input_details)) {
            $this->isPanelEnabled = "false";
        }

        if (isNullOrEmptyNumber($this->inputs['partner_id'])) {
            $this->isPanelEnabled = "true";
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
                $this->input_details[$key]['name'] = $detail->Material->name;
                $this->input_details[$key]['id'] = $detail->id;
                $this->input_details[$key]['price'] = $detail->price;
                $this->input_details[$key]['sub_total'] = rupiah(($detail->amt));
                $this->input_details[$key]['barcode'] = $detail->Material->MatlUom[0]->barcode;
                $this->input_details[$key]['image_path'] = $detail->Material->Attachment->first() ? $detail->Material->Attachment->first()->getUrl() : null;
            }
            $this->countTotalAmount();
        }
    }

    /**
     * Refresh only newly saved items instead of full retrieveMaterials for better performance
     */
    protected function refreshNewlySavedItems()
    {
        if ($this->object && !empty($this->newItems)) {
            // Get only the newly added items from database
            $newItemIds = array_column($this->newItems, 'matl_id');
            $newDetails = OrderDtl::GetByOrderHdr($this->object->id, $this->trType)
                ->whereIn('matl_id', $newItemIds)
                ->orderBy('tr_seq')
                ->get();

            // Update the input_details with fresh data for new items only
            foreach ($newDetails as $detail) {
                $existingIndex = array_search($detail->matl_id, array_column($this->input_details, 'matl_id'));
                if ($existingIndex !== false) {
                    $this->input_details[$existingIndex] = populateArrayFromModel($detail);
                    $this->input_details[$existingIndex]['name'] = $detail->Material->name;
                    $this->input_details[$existingIndex]['id'] = $detail->id;
                    $this->input_details[$existingIndex]['price'] = $detail->price;
                    $this->input_details[$existingIndex]['sub_total'] = rupiah(($detail->amt));
                    $this->input_details[$existingIndex]['barcode'] = $detail->Material->MatlUom[0]->barcode;
                    $this->input_details[$existingIndex]['image_path'] = $detail->Material->Attachment->first() ? $detail->Material->Attachment->first()->getUrl() : null;
                }
            }

            // Clear the new items array after refresh
            $this->newItems = [];
            $this->countTotalAmount();
        }
    }

    /**
     * Refresh after deleting specific item
     */
    protected function refreshAfterDelete($deletedIndex)
    {
        // Simply remove from array and reindex - no need to query database
        unset($this->input_details[$deletedIndex]);
        $this->input_details = array_values($this->input_details);
        $this->countTotalAmount();
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
        $this->inputs['partner_id'] = 0;
        $this->inputs['payment_term_id'] = 129;
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    #endregion

    #region CRUD Methods

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
            $messageKey = 'generic.string.delete';
            $this->dispatch('success', __($messageKey));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
    }

    protected function validateInputs()
    {
       if(empty($this->input_details)) {
            if ($this->inputs['curr_rate'] == 0) {
                $this->dispatch('warning', __('generic.string.currency_needed'));
                return false;
            }

            if (isNullOrEmptyNumber($this->inputs['partner_id'])) {
                $this->dispatch('warning', __('generic.error.field_required', ['field' => "Customer"]));
                $this->addError('inputs.partner_id', __('generic.error.field_required', ['field' => "Customer"]));
                return false;
            }

            if (isNullOrEmptyNumber($this->inputs['payment_term_id'])) {
                $this->dispatch('warning', __('generic.error.field_required', ['field' => "Payment"]));
                $this->addError('inputs.payment_terms_id', __('generic.error.field_required', ['field' => "Payment"]));
                return false;
            }
       }

        return true;
    }

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

        if (!empty($this->input_details)) {
            $unitIds = array_column($this->input_details, 'matl_code');
            if (count($unitIds) !== count(array_flip($unitIds))) {
                throw new Exception("Ditemukan duplikasi Item.");
            }
        }
        $this->inputs['wh_code'] = 18;

        if (!isNullOrEmptyNumber($this->inputs['partner_id'])) {
            $partner = Partner::find($this->inputs['partner_id']);
            $this->inputs['partner_code'] = $partner->code;
        }
        $this->inputs['print_settings'] = json_encode($this->filterprintRemarks($this->printSettings));
        $this->inputs['print_remarks'] = json_encode($this->filterprintRemarks($this->printRemarks));
        $this->object->saveOrder($this->appCode, $this->trType, $this->inputs, $this->input_details , true);
        if($this->actionValue == 'Create')
        {
            return redirect()->route($this->appCode.'.Transaction.SalesOrder.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);
        }

        // Only refresh newly saved items instead of full retrieveMaterials
        $this->refreshNewlySavedItems();
    }

    protected function filterprintRemarks($printRemarks)
    {
        return array_map(function ($setting) {
            return [
                'code' => $setting['code'],
                'value' => $setting['value'],
                'checked' => $setting['checked'],
            ];
        }, $printRemarks);
    }
    #endregion

    #region Component Events
    public function onPartnerChanged()
    {
        foreach ($this->input_details as $index => $detail) {
            $material = Material::find($detail['matl_id']);
            if ($material) {
                if(!isNullOrEmptyNumber($material->partner_id) && $this->inputs['partner_id'] != $material->partner_id)
                {
                    $this->dispatch('error', $material->code.' adalah barang pesanan untuk customer lain, mohon cek kembali.');
                    $this->addError("input_details.$index.matl_code",  $material->code.' adalah barang pesanan untuk customer lain, mohon cek kembali.');
                    return;
                }
            }
        }
        $this->saveCheck();
    }


    public function OpenDialogBox(){
        if (!$this->validateInputs()) {
            return;
        }
        $this->dispatch('openMaterialDialog');
    }

    public function Add()
    {
    }

    public function deleteDetails($index)
    {
        if ($this->object->isItemHasBuyBack($this->input_details[$index]['matl_id'])) {
            $this->dispatch('warning', 'Item ini tidak bisa dihapus, karena item sudah dibuyback.');
            return;
        }
        if (isset($this->input_details[$index]['id'])) {
            $deletedItemId = $this->input_details[$index]['id'];
            $orderDtl = OrderDtl::withTrashed()->find($deletedItemId);
            if ($orderDtl) {
                $orderDtl->forceDelete();
            }
        }

        // Use optimized refresh method for deletion
        $this->refreshAfterDelete($index);
    }

    public function tagScanned($tags)
    {
        if (!$this->validateInputs()) {
            return;
        }
        $tagCount = count($tags);

        $usercode = Auth::check() ? Auth::user()->code : '';

        DB::beginTransaction();

        try {
            $orderHdr = OrderHdr::firstOrNew([
                'id' => $this->objectIdValue,
            ]);

            $addedItems = []; // Variabel untuk menghitung jumlah barang yang berhasil dimasukkan
            $failedItems = []; // Variabel untuk menyimpan barcode yang gagal ditambahkan
            $notFoundItems = []; // Variabel untuk menyimpan barcode yang tidak ditemukan atau stok tidak ada
            $emptyStocks = []; // Variabel untuk menyimpan barcode yang stoknya kosong
            $otherPartnerItems = []; // Variabel untuk menyimpan barcode dengan partner_id yang berbeda

            $maxTrSeq = $orderHdr->OrderDtl()->max('tr_seq') ?? 0;
            foreach ($tags as $barcode) {
                // Find the corresponding material
                $material = Material::getListMaterialByBarcode($barcode);

                if (!isset($material)) {
                    $notFoundItems[] = $barcode;
                    continue;
                }

                if(!isNullOrEmptyNumber($material->partner_id) && $this->inputs['partner_id'] != $material->partner_id){
                    // Jika partner_id ada, tidak sama dengan 0, dan tidak sama dengan inputs['partner_id'], tambahkan ke otherPartnerItems
                    $otherPartnerItems[] = $barcode;
                    continue;
                }
                $existingOrderDtl = $orderHdr->OrderDtl()->where('matl_id', $material->id)->first();
                if ($existingOrderDtl) {
                    $failedItems[] = $material->code;
                    continue;
                }

                // Check if stock is empty
                if ($material->qty_oh <= 0) {
                    $emptyStocks[] = $material->code;
                    continue;
                }

                $maxTrSeq++;
                $newDetails[] = [
                    'qty_reff' => 1,
                    'matl_id' => $material->id,
                    'matl_descr' => $material->descr,
                    'matl_code' => $material->code,
                    'matl_uom' => $material->MatlUom[0]->id,
                    'qty' => 1,
                    'qty_reff' => 1,
                    'tr_seq' => $maxTrSeq,
                    'price' => $material->jwl_selling_price,
                ];
                $addedItems[] = $material->code;
            }

            // Menampilkan pesan sukses dengan jumlah barang yang berhasil dimasukkan dan gagal
            $message = "Total tag yang discan: {$tagCount}.<br>";
            if (count($addedItems) > 0) {
                $this->input_details = array_merge($this->input_details, $newDetails);
                $this->SaveWithoutNotification();
                DB::commit();
                $message .= "Berhasil menambahkan ". count($addedItems)  . " item: <b>" . implode(', ', $addedItems) . "</b>.<br><br>";
            }
            if (count($failedItems) > 0) {
                $message .= "Item sudah ada di keranjang untuk " . count($failedItems) . " item: <b>" . implode(', ', $failedItems) . "</b>.<br><br>";
            }
            if (count($notFoundItems) > 0) {
                $message .= "Material tidak ditemukan untuk " . count($notFoundItems) . " tag: <b>" . implode(', ', $notFoundItems) . "</b>.<br>";
            }
            if (count($emptyStocks) > 0) {
                $message .= "Material dengan stok kosong untuk " . count($emptyStocks) . " tag: <b>" . implode(', ', $emptyStocks) . "</b>.<br>";
            }
            if (count($otherPartnerItems) > 0) {
                $message .= "Material yang terkait dengan partner lain untuk " . count($otherPartnerItems) . " tag: <b>" . implode(', ', $otherPartnerItems) . "</b>.<br>";
            }
            $this->dispatch('info',$message);
            $this->dispatch('updateCartCount');
        } catch (\Exception $e) {
            DB::rollback();

            $this->dispatch('error',  'Terjadi kesalahan saat menambahkan item ke keranjang: ' . $e->getMessage());
        }
    }


    public function changeQty($id, $value)
    {
        if (isset($this->input_details[$id]['price'])) {
            $total = $this->input_details[$id]['price'] * $value;
            $this->input_details[$id]['amt'] = $total;
            $this->countTotalAmount();
        }
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

    public function countTotalAmount()
    {
        $this->total_amount = 0;
        foreach ($this->input_details as $input_detail) {
            if (isset($input_detail['qty']) && isset($input_detail['price'])) {
                $this->total_amount += $input_detail['price'] * $input_detail['qty'];
            }
        }
        $this->inputs['amt'] = $this->total_amount;
    }

    public function searchMaterials()
    {
        $this->currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($this->currencyRate == 0) {
            $this->dispatch('warning', __('generic.string.currency_needed'));
            return;
        }

        $query = Material::getAvailableMaterials();

        if (!empty($this->inputs['partner_id'])) {
            $query->where(function($query) {
                $query->where('materials.partner_id', $this->inputs['partner_id'])
                      ->orWhere('materials.partner_id', 0);
            });
        }



        if (!empty($this->searchTerm)) {
            $searchTermUpper = strtoupper($this->searchTerm);
            $query->where(function($query) use ($searchTermUpper) {
                $query->whereRaw('UPPER(materials.code) LIKE ?', ['%' . $searchTermUpper . '%'])
                      ->orWhereRaw('UPPER(materials.name) LIKE ?', ['%' . $searchTermUpper . '%'])
                      ->orWhereRaw('UPPER(materials.descr) LIKE ?', ['%' . $searchTermUpper . '%']);
            });
        }

        $this->materials = $query->get();
    }


    public function saveCheck()
    {
        if (!$this->validateInputs()) {
            return;
        }

        if (!$this->object->isNew())
            $this->SaveWithoutNotification();
    }

    public function addSelectedToCart()
    {
        $this->currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($this->currencyRate == 0) {
            $this->dispatch('warning', __('generic.string.currency_needed'));
            return;
        }

        if (empty($this->selectedMaterials)) {
            $this->dispatch('error', 'Harap pilih item dahulu sebelum menambahkan ke cart');
            return;
        }

        if (empty($this->inputs['payment_term_id'])) {
            $this->dispatch('warning', __('generic.error.field_required', ['field' => "Payment term"]));
            return;
        }

        if (empty($this->inputs['partner_id'])) {
            $this->dispatch('warning', __('generic.error.field_required', ['field' => "Partner"]));
            return;
        }

        DB::beginTransaction();

        try {
            $orderHdr = OrderHdr::firstOrNew([
                'id' => $this->objectIdValue,
            ]);

            $newDetails = [];

            $maxTrSeq = $orderHdr->OrderDtl()->max('tr_seq') ?? 0;
            foreach ($this->selectedMaterials as $material_id) {
                $material = Material::find($material_id);
                if (!$material) {
                    continue;
                }

                $existingOrderDtl = $orderHdr->OrderDtl()->where('matl_id', $material_id)->first();

                if ($existingOrderDtl) {
                    DB::rollback();
                    $this->dispatch('error',"Item {$material->code} sudah ada di Order");
                    return;
                }

                $maxTrSeq++;

                $newDetails[] = [
                    'qty_reff' => 1,
                    'matl_id' => $material_id,
                    'matl_descr' => $material->descr,
                    'matl_code' => $material->code,
                    'matl_uom' => $material->MatlUom[0]->id,
                    'qty' => 1,
                    'qty_reff' => 1,
                    'tr_seq' => $maxTrSeq,
                    'price' => $material->jwl_selling_price,
                ];
            }
            $this->input_details = array_merge($this->input_details, $newDetails);

            $this->SaveWithoutNotification();
            DB::commit();

            $this->dispatch('success', 'Berhasil menambahkan item ke cart');
            $this->selectedMaterials = [];
            $this->searchMaterials();
            $this->retrieveMaterials();
        } catch (\Exception $e) {
            DD($e);
            DB::rollback();
            $this->dispatch('error', 'Terjadi kesalahan saat menambahkan item ke Order');
        }
    }
    #endregion

}
