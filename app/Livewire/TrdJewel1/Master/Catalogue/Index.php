<?php

namespace App\Livewire\TrdJewel1\Master\Catalogue;

use App\Livewire\Component\BaseComponent;
use Livewire\WithPagination;
use Illuminate\Support\Facades\{Crypt, Auth, DB};
use App\Models\TrdJewel1\Master\{Material, GoldPriceLog};
use App\Models\TrdJewel1\Transaction\{CartHdr, CartDtl};
use App\Models\SysConfig1\ConfigConst;
use Illuminate\Support\Carbon;
use Exception;

class Index extends BaseComponent
{
    use WithPagination;
    #region Constant Variables
    public $currencyRate;
    public $categories1 = [];
    public $categories2 = [];

    public $inputs = [
        'name' => '',
        'description' => '',
        'selling_price1' => '',
        'selling_price2' => '',
        'selling_price1_idr' => '',
        'selling_price2_idr' => '',
        'code' => '',
        'category1' => '',
        'category2' => ''
    ];

    #endregion

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue);

        // Load categories for dropdowns
        $this->loadCategories();
    }

    public function loadCategories()
    {
        // Get categories from ConfigConst for category1
        $categories1 = ConfigConst::where('const_group', 'MMATL_CATEGL1')
            ->whereNull('deleted_at')
            ->orderBy('str2')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->str1 => $item->str1 . ' - ' . $item->str2];
            })
            ->toArray();

        // If no data found, try without deleted_at filter for category1
        if (empty($categories1)) {
            $categories1 = ConfigConst::where('const_group', 'MMATL_CATEGL1')
                ->orderBy('str2')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->str1 => $item->str1 . ' - ' . $item->str2];
                })
                ->toArray();
        }

        // Convert to format expected by dropdown
        $this->categories1 = collect($categories1)->map(function($text, $value) {
            return [
                'value' => $value,
                'label' => $text
            ];
        })->values()->toArray();

        // Get categories from ConfigConst for category2
        $categories2 = ConfigConst::where('const_group', 'MMATL_CATEGL2')
            ->whereNull('deleted_at')
            ->orderBy('str2')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->str1 => $item->str1 . ' - ' . $item->str2];
            })
            ->toArray();

        // If no data found, try without deleted_at filter for category2
        if (empty($categories2)) {
            $categories2 = ConfigConst::where('const_group', 'MMATL_CATEGL2')
                ->orderBy('str2')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->str1 => $item->str1 . ' - ' . $item->str2];
                })
                ->toArray();
        }

        // Convert to format expected by dropdown
        $this->categories2 = collect($categories2)->map(function($text, $value) {
            return [
                'value' => $value,
                'label' => $text
            ];
        })->values()->toArray();
    }

    #region Populate Data methods
    public function render()
    {
        $this->currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($this->currencyRate == 0) {
            abort(422, __('generic.string.currency_needed'));
        }

        $query = Material::getAvailableMaterials();

        if (!empty($this->inputs['name'])) {
            $query->whereRaw('UPPER(name) LIKE ?', ['%' . strtoupper($this->inputs['name']) . '%']);
        }
        if (!empty($this->inputs['description'])) {
            $query->whereRaw('UPPER(descr) LIKE ?', ['%' . strtoupper($this->inputs['description']) . '%']);
        }
        if (!empty($this->inputs['selling_price1']) && !empty($this->inputs['selling_price2'])) {
            $query->whereBetween('jwl_selling_price_usd', [$this->inputs['selling_price1'], $this->inputs['selling_price2']]);
        }
        if (!empty($this->inputs['selling_price1_idr']) && !empty($this->inputs['selling_price2_idr'])) {
            // Filter by IDR price range - convert USD to IDR for comparison
            $minUsd = floatval($this->inputs['selling_price1_idr']) / $this->currencyRate;
            $maxUsd = floatval($this->inputs['selling_price2_idr']) / $this->currencyRate;
            $query->whereBetween('jwl_selling_price_usd', [$minUsd, $maxUsd]);
        }
        if (!empty($this->inputs['code'])) {
            $query->whereRaw('UPPER(code) = ?', [strtoupper($this->inputs['code'])]);
        }
        if (!empty($this->inputs['category1'])) {
            $query->where('jwl_category1', $this->inputs['category1']);
        }
        if (!empty($this->inputs['category2'])) {
            $query->where('jwl_category2', $this->inputs['category2']);
        }

        $materials = $query->orderBy('created_at', 'desc')->paginate(12);
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute, ['materials' => $materials]);
    }

    protected function onPreRender()
    {
    }

    #endregion

    #region CRUD Methods


    #endregion

    #region Component Events

    public function View($id)
    {
        return redirect()->route('materials.detail', ['action' => encryptWithSessionKey('View'), 'objectId' => encryptWithSessionKey($id)]);
    }

    public function Edit($id)
    {
        return redirect()->route('materials.detail', ['action' => encryptWithSessionKey('Edit'), 'objectId' => encryptWithSessionKey($id)]);
    }

    public function search()
    {
        // Validate that both USD and IDR price filters are not used at the same time
        if ((!empty($this->inputs['selling_price1']) || !empty($this->inputs['selling_price2'])) &&
            (!empty($this->inputs['selling_price1_idr']) || !empty($this->inputs['selling_price2_idr']))) {
            $this->dispatch('warning', 'Gunakan filter harga USD atau IDR, tidak keduanya bersamaan.');
            return;
        }

        // Validate USD price range
        if (!empty($this->inputs['selling_price1']) && !empty($this->inputs['selling_price2'])) {
            if ($this->inputs['selling_price1'] > $this->inputs['selling_price2']) {
                $this->dispatch('warning', 'Harga USD minimum harus lebih kecil dari harga maksimum.');
                return;
            }
        }

        // Validate IDR price range
        if (!empty($this->inputs['selling_price1_idr']) && !empty($this->inputs['selling_price2_idr'])) {
            if ($this->inputs['selling_price1_idr'] > $this->inputs['selling_price2_idr']) {
                $this->dispatch('warning', 'Harga IDR minimum harus lebih kecil dari harga maksimum.');
                return;
            }
        }

        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->inputs = [
            'name' => '',
            'description' => '',
            'selling_price1' => '',
            'selling_price2' => '',
            'selling_price1_idr' => '',
            'selling_price2_idr' => '',
            'code' => '',
            'category1' => '',
            'category2' => ''
        ];
        $this->dispatch('resetSelect2Dropdowns');
        $this->resetPage();
    }

    public function addToCart($material_id, $material_code)
    {
        if ($this->currencyRate == 0) {
            $this->dispatch('warning', __('generic.string.currency_needed'));
            return;
        }
        $usercode = Auth::check() ? Auth::user()->code : '';

        DB::beginTransaction();

        try {
            // Find the material by ID
            $material = Material::find($material_id);
            if (!$material) {
                throw new Exception('Material not found.');
            }

            // Check if the material has quantity
            if (!$material->hasQuantity()) {
                throw new Exception('Material out of stock.');
            }
            // Get the cartHdr by user code and tr_type = cart
            $cartHdr = CartHdr::where('created_by', $usercode)
                ->where('tr_type', 'C')
                ->first();

            // If cartHdr doesn't exist, create a new one
            if (!$cartHdr) {
                $cartHdr = CartHdr::create([
                    'tr_type' => 'C',
                    'tr_date' => Carbon::now(),
                    'created_by' => $usercode,
                ]);
            }

            // Get the maximum tr_seq from the current order detail for this order header
            $maxTrSeq = $cartHdr->CartDtl()->max('tr_seq');

            // If there are no existing order details, set the maxTrSeq to 1
            if (!$maxTrSeq) {
                $maxTrSeq = 1;
            } else {
                // Increment the maxTrSeq by 1
                $maxTrSeq++;
            }

            // Check if the material is already added to the OrderDtl
            $existingOrderDtl = $cartHdr->CartDtl()->where('matl_id', $material_id)->first();

            // If OrderDtl doesn't exist for the material, create a new one
            if (!$existingOrderDtl) {
                $cartHdr->CartDtl()->create([
                    'trhdr_id' => $cartHdr->id,
                    'qty_reff' => 1,
                    'matl_id' => $material_id,
                    'matl_code' => $material_code,
                    'qty' => 1,
                    'qty_reff' => 1,
                    'tr_type' => 'C',
                    'tr_seq' => $maxTrSeq,
                    'price' => $material->jwl_selling_price,
                ]);

                DB::commit();

                $this->dispatch('success', 'Berhasil menambahkan item ke cart');
                $this->dispatch('updateCartCount');
            } else {
                throw new Exception('Item sudah dimasukkan ke cart');
            }
        } catch (\Exception $e) {
            DB::rollback();
            $this->dispatch('error', 'Terjadi kesalahan saat menambahkan item ke cart. ' . $e->getMessage());
        }
    }
    #endregion





}
