<?php

namespace App\Livewire\TrdJewel1\Transaction\CartOrder;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{Auth, DB};
use App\Models\TrdJewel1\Transaction\{CartHdr, CartDtl, OrderHdr};
use App\Models\TrdJewel1\Master\{Material, GoldPriceLog};
use App\Enums\Status;
use Carbon\Carbon;
use Exception;

class Index extends BaseComponent
{
    #region Constant Variables
    public $trType = "SO";
    public $object_detail;
    public $inputs = [];
    public $input_details = [];
    public $inputsearches = [];
    public $materials = [];
    public $currencyRate = 0;
    public $suppliers;
    public $warehouses;
    public $payments;
    public $deletedItems = [];
    public $newItems = [];
    public $total_amount = 0;
    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];
    public $returnIds = [];
    public $searchTerm = '';
    public $selectedMaterials = [];
    protected $listeners = [
        'changeStatus' => 'changeStatus',
        'materialSaved' => 'materialSaved',
        'tagScanned' => 'tagScanned',
        'delete' => 'delete'
    ];
    #endregion

    #region Populate Data methods
    protected function onPreRender()
    {
        $this->actionValue = 'Create';
        $this->baseRoute = $this->appCode.'.Transaction.CartOrder.Index';
        $this->currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($this->currencyRate == 0) {
            abort(422, __('generic.string.currency_needed'));
        }
        $this->customValidationAttributes  = [
            'input_details.*' => $this->trans('product'),
            'input_details.*.matl_id' => $this->trans('product'),
            'input_details.*.qty' => $this->trans('qty'),
            'input_details.*.selling_price' => $this->trans('selling_price'),
        ];
        $usercode = Auth::check() ? Auth::user()->code : '';
        if ($this->actionValue === 'Create') {
            $this->object = CartHdr::withTrashed()->where('created_by', $usercode)->first();
        }
        $this->retrieveMaterials();
    }

    public $rules  = [
        'input_details.*.qty' => 'required',
        'input_details.*.selling_price' => ['required',  'not_in:0'],
    ];

    protected function retrieveMaterials()
    {
        if ($this->object) {
            $this->object_detail = CartDtl::GetByCartHdr($this->object->id)->orderBy('tr_seq')->get();
            $this->inputs = populateArrayFromModel($this->object);
            if (is_null($this->object_detail) || $this->object_detail->isEmpty()) {
                return;
            }
            foreach ($this->object_detail as $key => $detail) {
                $this->input_details[$key] =  populateArrayFromModel($detail);
                $this->input_details[$key]['checked'] = true;
                $this->input_details[$key]['id'] = $detail->id;
                $this->input_details[$key]['name'] = $detail->Material->name ?? "";
                $this->input_details[$key]['matl_descr'] = $detail->Material->descr ?? "";
                $this->input_details[$key]['selling_price'] = $detail->price;
                $this->input_details[$key]['sub_total'] = rupiah(($detail->amt));
                $this->input_details[$key]['barcode'] = $detail->Material->MatlUom[0]->barcode ?? "";
                $imagePath = $detail->Material?->Attachment?->first()?->getUrl() ?? null;

                $this->input_details[$key]['image_path'] = $imagePath;
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
            $newDetails = CartDtl::GetByCartHdr($this->object->id)
                ->whereIn('matl_id', $newItemIds)
                ->orderBy('tr_seq')
                ->get();
            
            // Update the input_details with fresh data for new items only
            foreach ($newDetails as $detail) {
                $existingIndex = array_search($detail->matl_id, array_column($this->input_details, 'matl_id'));
                if ($existingIndex !== false) {
                    $this->input_details[$existingIndex] = populateArrayFromModel($detail);
                    $this->input_details[$existingIndex]['checked'] = true;
                    $this->input_details[$existingIndex]['id'] = $detail->id;
                    $this->input_details[$existingIndex]['name'] = $detail->Material->name ?? "";
                    $this->input_details[$existingIndex]['matl_descr'] = $detail->Material->descr ?? "";
                    $this->input_details[$existingIndex]['selling_price'] = $detail->price;
                    $this->input_details[$existingIndex]['sub_total'] = rupiah(($detail->amt));
                    $this->input_details[$existingIndex]['barcode'] = $detail->Material->MatlUom[0]->barcode ?? "";
                    $imagePath = $detail->Material?->Attachment?->first()?->getUrl() ?? null;
                    $this->input_details[$existingIndex]['image_path'] = $imagePath;
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

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    #endregion

    #region CRUD Methods

    public function onValidateAndSave()
    {
        $this->object->fill($this->inputs);
        $this->object->save();
        foreach ($this->input_details as $index => $data) {
            if (!isset($this->object_detail[$index])) {
                $this->object_detail[$index] = new CartDtl();
            }
            $this->object_detail[$index]->fill($data);
            $this->object_detail[$index]->save();
        }
    }

    #endregion

    #region Component Events

    public function OpenDialogBox(){
        $this->dispatch('openMaterialDialog');
    }

    public function Checkout()
    {
        $selectedItems = array_filter($this->input_details, function ($item) {
            return !empty($item['checked']) && (bool) $item['checked'] === true;
        });

        if (empty($selectedItems)) {
            $this->dispatch('error', 'Harap pilih item dahulu sebelum checkout');
            return;
        }

        $outOfStockItems = [];
        foreach ($selectedItems as &$selectedItem) {
            $material = Material::checkMaterialStockByMatlId($selectedItem['matl_id']);
            if (!$material) {
                $outOfStockItems[] = $selectedItem['matl_code'];
            } else {
                $selectedItem['price'] = $selectedItem['selling_price'];
                $selectedItem['amt'] = $selectedItem['selling_price'];
                $selectedItem['tr_type'] = "SO";
                $this->deletedItems[] = $selectedItem['id'];
            }
        }

        if (!empty($outOfStockItems)) {
            $this->dispatch('error','Beberapa material tidak memiliki stok: ' . implode(', ', $outOfStockItems), "Mohon cek kembali!");
            return;
        }

        if (!$this->object->isNew()) {
            foreach ($this->deletedItems as $deletedItemId) {
                CartDtl::find($deletedItemId)->forceDelete();
            }
        }

        $order_header = new OrderHdr();
        $this->inputs['wh_code'] = 18;
        $this->inputs['tr_date'] = date('Y-m-d');
        $this->inputs['tr_type'] = "SO";
        $order_header->saveOrder($this->appCode,  $this->inputs['tr_type'], $this->inputs, $selectedItems, [], true);

        return redirect()->route($this->appCode.'.Transaction.SalesOrder.Detail', [
            'action' => encryptWithSessionKey('Edit'),
            'objectId' => encryptWithSessionKey($order_header->id)
        ]);
    }


    public function tagScanned($tags)
    {
        $this->currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($this->currencyRate == 0) {
            $this->dispatch('warning', 'Diperlukan kurs mata uang.');
            return;
        }

        $tagCount = count($tags);
        // if ($tagCount == 0) {
        //     $this->dispatch('notify-swal', [
        //         'type' => 'error',
        //         'message' => 'Tidak ada tag yang discan. Silakan coba lagi.'
        //     ]);
        //     return;
        // }

        $usercode = Auth::check() ? Auth::user()->code : '';

        DB::beginTransaction();

        try {
            $cartHdr = CartHdr::firstOrCreate([
                'created_by' => $usercode,
                'tr_type' => 'C',
            ], [
                'tr_date' => Carbon::now(),
            ]);

            $addedItems = []; // Variabel untuk menghitung jumlah barang yang berhasil dimasukkan
            $failedItems = []; // Variabel untuk menyimpan barcode yang gagal ditambahkan
            $notFoundItems = []; // Variabel untuk menyimpan barcode yang tidak ditemukan
            $emptyStocks = []; // Variabel untuk menyimpan barcode yang stoknya kosong

            foreach ($tags as $barcode) {
                // Find the corresponding material
                $material = Material::getListMaterialByBarcode($barcode);

                if (!isset($material)) {
                    $notFoundItems[] = $barcode;
                    continue;
                }

                $existingOrderDtl = $cartHdr->CartDtl()->where('matl_id', $material->id)->first();
                if ($existingOrderDtl) {
                    $failedItems[] = $material->code;
                    continue;
                }

                if ($material->qty_oh <= 0) {
                    $emptyStocks[] =  $material->code;
                    continue;
                }


                $maxTrSeq = $cartHdr->CartDtl()->max('tr_seq') ?? 0;
                $maxTrSeq++;

                $cartHdr->CartDtl()->create([
                    'trhdr_id' => $cartHdr->id,
                    'qty_reff' => 1,
                    'matl_id' => $material->id,
                    'matl_code' => $material->code,
                    'qty' => 1,
                    'tr_type' => 'C',
                    'tr_seq' => $maxTrSeq,
                    'price' => $material->jwl_selling_price,
                ]);
                $addedItems[] = $material->code;
            }

            DB::commit();

            // Menampilkan pesan sukses dengan jumlah barang yang berhasil dimasukkan dan gagal
            $message = "Total tag yang discan: {$tagCount}.<br>";
            if (count($addedItems) > 0) {
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
            $this->dispatch('info',$message);
            $this->retrieveMaterials();
            $this->dispatch('updateCartCount');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error',  'Terjadi kesalahan saat menambahkan item ke keranjang: ' . $e->getMessage());
        }
    }


    public function addDetails($material_id = null)
    {
        $detail = [
            'tr_type' => $this->trType,
        ];
        $material = Material::find($material_id);
        if ($material) {
            $detail['matl_id'] = $material->id;
            $detail['matl_code'] = $material->code;
            $detail['matl_descr'] = $material->descr ?? "";
            $detail['name'] = $material->name ?? "";
            $detail['matl_uom'] = $material->MatlUom[0]->id;
            $detail['image_path'] = $material->Attachment->first() ? $material->Attachment->first()->getUrl() : null;
            $detail['barcode'] = $material->MatlUom[0]->barcode;
            $detail['price'] = $material->jwl_buying_price ?? 0;
            $detail['selling_price'] = $material->jwl_selling_price ?? 0;
            $detail['qty'] = 1;
            $detail['amt'] = $detail['qty'] * $detail['price'];
        }
        array_push($this->input_details, $detail);
        $newDetail = end($this->input_details);
        $this->newItems[] = $newDetail;
        $this->countTotalAmount();
    }

    public function deleteDetails($index)
    {
        if (isset($this->input_details[$index]['id'])) {
            $deletedItemId = $this->input_details[$index]['id'];
            $orderDtl = CartDtl::withTrashed()->find($deletedItemId);
            if ($orderDtl) {
                $orderDtl->forceDelete();
            }
        }
        
        // Use optimized refresh method for deletion
        $this->refreshAfterDelete($index);
    }

    public function changePrice($id, $value)
    {
        if (isset($this->input_details[$id]['qty'])) {
            $total = toNumberFormatter($this->input_details[$id]['qty']) * toNumberFormatter($value);
            $this->input_details[$id]['amt'] = numberFormat($total);
            $this->input_details[$id]['price'] = $total;
            $this->countTotalAmount();
            $this->SaveWithoutNotification();
        }
    }

    public function countTotalAmount()
    {
        $this->total_amount = 0;
        foreach ($this->input_details as $input_detail) {
            if (isset($input_detail['qty']) && isset($input_detail['selling_price'])) {
                $this->total_amount += toNumberFormatter($input_detail['selling_price']) * toNumberFormatter($input_detail['qty']);
            }
        }
        $this->inputs['amt'] = numberFormat($this->total_amount);
    }

    public function searchMaterials()
    {
        $this->currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($this->currencyRate == 0) {
            $this->dispatch('warning', __('generic.string.currency_needed'));
            return;
        }

        $query = Material::getAvailableMaterials();

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

        $usercode = Auth::check() ? Auth::user()->code : '';

        DB::beginTransaction();

        try {
            $cartHdr = CartHdr::firstOrCreate([
                'created_by' => $usercode,
                'tr_type' => 'C',
            ], [
                'tr_date' => Carbon::now(),
            ]);

            foreach ($this->selectedMaterials as $material_id) {
                $material = Material::find($material_id);
                if (!$material) {
                    continue;
                }

                $existingOrderDtl = $cartHdr->CartDtl()->where('matl_id', $material_id)->first();

                if ($existingOrderDtl) {
                    throw new Exception("Item {$material->code} sudah ada di cart");
                }

                $price = $material->jwl_selling_price_usd * $this->currencyRate;
                $maxTrSeq = $cartHdr->CartDtl()->max('tr_seq') ?? 0;
                $maxTrSeq++;

                $cartHdr->CartDtl()->create([
                    'trhdr_id' => $cartHdr->id,
                    'qty_reff' => 1,
                    'matl_id' => $material_id,
                    'matl_code' => $material->code,
                    'qty' => 1,
                    'qty_reff' => 1,
                    'tr_type' => 'C',
                    'tr_seq' => $maxTrSeq,
                    'price' => $price,
                ]);
            }

            DB::commit();

            $this->dispatch('success', 'Berhasil menambahkan item ke cart');
            $this->selectedMaterials = [];
            $this->retrieveMaterials();
            $this->searchMaterials();
            $this->dispatch('updateCartCount');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', 'Terjadi kesalahan saat menambahkan item ke cart. ' . $e->getMessage());
        }
    }
    #endregion


}
