<?php

namespace App\Livewire\TrdJewel1\Master\Catalogue;

use App\Livewire\Component\BaseComponent;
use Livewire\WithPagination;
use App\Models\TrdJewel1\Master\Material;
use App\Models\TrdJewel1\Transaction\CartHdr;
use App\Models\TrdJewel1\Transaction\CartDtl;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\TrdJewel1\Master\GoldPriceLog;
use Illuminate\Support\Carbon;

class Index extends BaseComponent
{
    use WithPagination;

    public $currencyRate;

    public $inputs = [
        'name' => '',
        'description' => '',
        'selling_price1' => '',
        'selling_price2' => '',
        'code' => ''
    ];

    public function render()
    {
        $this->currencyRate = GoldPriceLog::GetTodayCurrencyRate();

        if ($this->currencyRate == 0) {
            abort(431, __('generic.string.currency_needed'));
        }

        $query = Material::getAvailableMaterials();

        if (!empty($this->inputs['name'])) {
            $query->where('name', 'like', '%' . strtoupper($this->inputs['name']) . '%');
        }
        if (!empty($this->inputs['description'])) {
            $query->where('descr', 'like', '%' . strtoupper($this->inputs['description']) . '%');
        }
        if (!empty($this->inputs['selling_price1']) && !empty($this->inputs['selling_price2'])) {
            $query->whereBetween('jwl_selling_price', [$this->inputs['selling_price1'], $this->inputs['selling_price2']]);
        }
        if (!empty($this->inputs['code'])) {
            $query->where('code', 'like', '%' . strtoupper($this->inputs['code']) . '%');
        }

        $materials = $query->orderBy('created_at', 'desc')->paginate(18);

        return view('livewire.trd-jewel1.master.catalogue.index', ['materials' => $materials]);
    }

    protected function onPreRender()
    {
    }

    public function View($id)
    {
        return redirect()->route('materials.detail', ['action' => Crypt::encryptString('View'), 'objectId' => Crypt::encryptString($id)]);
    }

    public function Edit($id)
    {
        return redirect()->route('materials.detail', ['action' => Crypt::encryptString('Edit'), 'objectId' => Crypt::encryptString($id)]);
    }

    public function search()
    {
        $this->resetPage();
    }

    public function addToCart($material_id, $material_code)
    {
        if ($this->currencyRate == 0) {
            $this->notify('warning', __('generic.string.currency_needed'));
            return;
        }
        $usercode = Auth::check() ? Auth::user()->code : '';

        DB::beginTransaction();

        try {
            // Find the material by ID
            $material = Material::find($material_id);
            if (!$material) {
                DB::rollback();
                $this->dispatch('notify-swal', [
                    'type' => 'error',
                    'message' => 'Material not found'
                ]);
                return;
            }

            // Check if the material has quantity
            if (!$material->hasQuantity()) {
                DB::rollback();
                $this->dispatch('notify-swal', [
                    'type' => 'error',
                    'message' => 'Material out of stock'
                ]);
                return;
            }
            // Calculate the price
            $price = currencyToNumeric($material->jwl_selling_price) * $this->currencyRate;

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
                    'price' => $price,
                ]);

                DB::commit();

                $this->dispatch('notify-swal', [
                    'type' => 'success',
                    'message' => 'Berhasil menambahkan item ke cart'
                ]);
                $this->dispatch('updateCartCount');
            } else {
                DB::rollback();

                $this->dispatch('notify-swal', [
                    'type' => 'error',
                    'message' => 'Item sudah dimasukkan ke cart'
                ]);
            }
        } catch (\Exception $e) {
            DB::rollback();

            $this->dispatch('notify-swal', [
                'type' => 'error',
                'message' => 'Terjadi kesalahan saat menambahkan item ke cart'
            ]);
        }
    }
}
