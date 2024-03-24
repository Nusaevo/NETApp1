<?php

namespace App\Http\Livewire\TrdJewel1\Master\Catalogue;

use App\Http\Livewire\Component\BaseComponent;
use Livewire\WithPagination;
use App\Models\TrdJewel1\Master\Material;
use App\Models\Transactions\OrderHdr;
use App\Models\Transactions\OrderDtl;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class Index extends BaseComponent
{
    use WithPagination;

    public $inputs = [
        'description' => '',
        'selling_price1' => '',
        'selling_price2' => '',
        'code' => ''
    ];

    public function render()
    {
        $query = Material::query();

        if (!empty($this->inputs['description'])) {
            $query->where('descr', 'like', '%' . $this->inputs['description'] . '%');
        }
        if (!empty($this->inputs['selling_price1']) && !empty($this->inputs['selling_price2'])) {
            $query->whereBetween('selling_price', [$this->inputs['selling_price1'], $this->inputs['selling_price2']]);
        }
        if (!empty($this->inputs['code'])) {
            $query->where('code', 'like', '%' . $this->inputs['code'] . '%');
        }

        $materials = $query->paginate(9);

        return view('livewire.trd-jewel1.master.catalogue.index', ['materials' => $materials]);
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

    public function addToCart($material_id,$material_code)
    {
        $usercode = Auth::check() ? Auth::user()->code : '';

        // Get the OrderHdr by user code and tr_type = cart
        $orderHdr = OrderHdr::where('created_by', $usercode)
                            ->where('tr_type', 'CART')
                            ->first();

        // If OrderHdr doesn't exist, create a new one
        if (!$orderHdr) {
            $orderHdr = OrderHdr::create([
                'tr_type' => 'CART',
                'tr_date' => Carbon::now(),
                'partner_id' => 0
            ]);
        }

        // Get the maximum tr_seq from the current order detail for this order header
        $maxTrSeq = $orderHdr->OrderDtl()->max('tr_seq');

        // If there are no existing order details, set the maxTrSeq to 1
        if (!$maxTrSeq) {
            $maxTrSeq = 1;
        } else {
            // Increment the maxTrSeq by 1
            $maxTrSeq++;
        }

        // Check if the material is already added to the OrderDtl
        $existingOrderDtl = $orderHdr->OrderDtl()->where('matl_id', $material_id)->first();

        // If OrderDtl doesn't exist for the material, create a new one
        if (!$existingOrderDtl) {
            $orderHdr->OrderDtl()->create([
                'trhdr_id' => $orderHdr->id,
                'qty_reff' => 1,
                'matl_id' => $material_id,
                'matl_code' => $material_code,
                'qty' => 1,
                'qty_reff' => 1,
                'tr_type' => 'SO',
                'tr_seq' => $maxTrSeq
            ]);
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => 'Berhasil menambahkan item ke cart'
            ]);
        } else {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => 'Item sudah dimasukkan ke cart'
            ]);
        }
    }

}
