<?php

namespace App\Livewire\TrdJewel1\Transaction\CartOrder;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Transaction\CartHdr;
use App\Models\TrdJewel1\Transaction\CartDtl;
use App\Models\TrdJewel1\Master\Material;
use Illuminate\Support\Facades\Auth;
use App\Enums\Status;
use App\Models\TrdJewel1\Master\GoldPriceLog;
use App\Models\TrdJewel1\Transaction\OrderHdr;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Detail extends BaseComponent
{
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

    protected function onPreRender()
    {
        $this->currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($this->currencyRate == 0) {
            abort(431, __('generic.string.currency_needed'));
        }
        $this->customValidationAttributes  = [
            'input_details.*' => $this->trans('product'),
            'input_details.*.matl_id' => $this->trans('product'),
            'input_details.*.qty' => $this->trans('qty'),
            'input_details.*.price' => $this->trans('price'),
        ];
        $usercode = Auth::check() ? Auth::user()->code : '';

        if ($this->actionValue === 'Create') {
            $this->object = CartHdr::withTrashed()->where('created_by', $usercode)->first();
        }

        $this->retrieveMaterials();
    }

    public $rules  = [
        'input_details.*.price' => 'required',
        'input_details.*.qty' => 'required',
    ];

    protected function retrieveMaterials()
    {
        if ($this->object) {
            $this->object_detail = CartDtl::GetByCartHdr($this->object->id)->orderBy('tr_seq')->get();
            $this->inputs = populateArrayFromModel($this->object);

            foreach ($this->object_detail as $key => $detail) {
                $this->input_details[$key] =  populateArrayFromModel($detail);
                $this->input_details[$key]['checked'] = 1;
                $this->input_details[$key]['id'] = $detail->id;
                $this->input_details[$key]['price'] = ceil(currencyToNumeric($detail->price));
                $this->input_details[$key]['qty'] = ceil(currencyToNumeric($detail->qty));
                $this->input_details[$key]['amt'] = ceil(currencyToNumeric($detail->amt));
                $this->input_details[$key]['name'] = $detail->Material->name ?? "";
                $this->input_details[$key]['matl_descr'] = $detail->Material->descr ?? "";
                $this->input_details[$key]['selling_price'] = ceil(currencyToNumeric($detail->price));
                $this->input_details[$key]['sub_total'] = rupiah(ceil(currencyToNumeric($detail->amt)));
                $this->input_details[$key]['barcode'] = $detail->Material->MatlUom[0]->barcode ?? "";
                $imagePath = $detail->Material?->Attachment?->first()?->getUrl() ?? null;

                $this->input_details[$key]['image_path'] = $imagePath;
            }

            $this->countTotalAmount();
        }
    }

    protected function onLoadForEdit()
    {
    }

    public function render()
    {
        return view($this->renderRoute);
    }

    protected $listeners = [
        'changeStatus' => 'changeStatus',
        'materialSaved' => 'materialSaved',
        'tagScanned' => 'tagScanned',
        'delete' => 'delete'
    ];

    public function OpenDialogBox(){
        if ($this->inputs['curr_rate'] == 0) {
            $this->notify('warning',__('generic.string.currency_needed'));
            return;
        }
        $this->dispatch('openMaterialDialog');
    }

    protected function onPopulateDropdowns()
    {
    }

    public function onValidateAndSave()
    {
        $this->object->fillAndSanitize($this->inputs);
        $this->object->save();
        foreach ($this->input_details as $index => $data) {
            if (!isset($this->object_detail[$index])) {
                $this->object_detail[$index] = new CartDtl();
            }
            $this->object_detail[$index]->fillAndSanitize($data);
            $this->object_detail[$index]->save();
        }
    }

    public function Checkout()
    {
        $selectedItems = array_filter($this->input_details, function ($item) {
            return $item['checked'] == 1;
        });

        if (empty($selectedItems)) {
            $this->dispatch('notify-swal', [
                'type' => 'error',
                'message' => 'Harap pilih item dahulu sebelum checkout'
            ]);
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
            $this->notify('error','Beberapa material tidak memiliki stok: ' . implode(', ', $outOfStockItems), "Mohon cek kembali!");
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

        return redirect()->route('TrdJewel1.Transaction.SalesOrder.Detail', [
            'action' => encryptWithSessionKey('Edit'),
            'objectId' => encryptWithSessionKey($order_header->id)
        ]);
    }


    public function onReset()
    {
    }


    public function tagScanned($tags)
    {
        $this->currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($this->currencyRate == 0) {
            $this->dispatch('notify-swal', [
                'type' => 'warning',
                'message' => 'Diperlukan kurs mata uang.'
            ]);
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


                $price = currencyToNumeric($material->jwl_selling_price) * $this->currencyRate;
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
                    'price' => $price,
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
            $this->notify('info',$message);
            $this->retrieveMaterials();
            $this->dispatch('updateCartCount');
        } catch (\Exception $e) {
            DB::rollback();
            $this->notify('error',  'Terjadi kesalahan saat menambahkan item ke keranjang: ' . $e->getMessage());
        }
    }


    public function addDetails($material_id = null)
    {
        $this->dispatch('toggle-modal');
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
            $detail['price'] = currencyToNumeric($material->jwl_buying_price) ?? 0;
            $detail['selling_price'] = currencyToNumeric($material->jwl_selling_price) ?? 0;
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
        unset($this->input_details[$index]);
        $this->input_details = array_values($this->input_details);
        $this->countTotalAmount();
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
        foreach ($this->input_details as $item_id => $input_detail) {
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
            $this->notify('warning', __('generic.string.currency_needed'));
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
            $this->notify('warning', __('generic.string.currency_needed'));
            return;
        }

        if (empty($this->selectedMaterials)) {
            $this->dispatch('notify-swal', [
                'type' => 'error',
                'message' => 'Harap pilih item dahulu sebelum menambahkan ke cart'
            ]);
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
                    DB::rollback();
                    $this->dispatch('notify-swal', [
                        'type' => 'error',
                        'message' => "Item {$material->code} sudah ada di cart"
                    ]);
                    return;
                }

                $price = currencyToNumeric($material->jwl_selling_price) * $this->currencyRate;
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

            $this->dispatch('notify-swal', [
                'type' => 'success',
                'message' => 'Berhasil menambahkan item ke cart'
            ]);
            $this->selectedMaterials = [];
            $this->retrieveMaterials();
            $this->searchMaterials();
            $this->dispatch('updateCartCount');
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('notify-swal', [
                'type' => 'error',
                'message' => 'Terjadi kesalahan saat menambahkan item ke cart'
            ]);
        }
    }
}
