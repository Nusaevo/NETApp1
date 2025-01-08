<?php

namespace App\Livewire\TrdRetail1\Procurement\PurchaseOrder;

use Livewire\Component;
use App\Models\TrdRetail1\Transaction\OrderHdr;
use App\Models\TrdRetail1\Transaction\OrderDtl;
use App\Models\TrdRetail1\Master\Material;
use Exception;

class MaterialListComponent extends Component
{
    public $input_details = [];
    public $total_amount = 0;
    public $trType = "PO";
    public $isPanelEnabled = "true";

    protected $listeners = [
        'materialSaved' => 'materialSaved',
        'deleteDetails' => 'deleteDetails',
        'changePrice' => 'changePrice',
    ];

    public function mount($input_details = [])
    {
        $this->input_details = $input_details;
        $this->countTotalAmount();
    }

    public function materialSaved($material_id)
    {
        try {
            $material = Material::find($material_id);

            if (!$material) {
                $this->dispatch('error', 'Material tidak ditemukan.');
                return;
            }

            $detail = [
                'tr_type' => $this->trType,
                'matl_id' => $material->id,
                'matl_code' => $material->code,
                'matl_descr' => $material->descr ?? "",
                'name' => $material->name ?? "",
                'matl_uom' => $material->MatlUom[0]->id,
                'image_path' => $material->Attachment->first() ? $material->Attachment->first()->getUrl() : null,
                'barcode' => $material->MatlUom[0]->barcode,
                'isOrderedMaterial' => $material->isOrderedMaterial(),
                'price' => $material->isOrderedMaterial() ? $material->jwl_buying_price_idr : $material->jwl_buying_price_usd,
                'selling_price' => $material->isOrderedMaterial() ? $material->jwl_selling_price_idr : $material->jwl_selling_price_usd,
                'qty' => 1,
                'tr_seq' => count($this->input_details) + 1,
            ];

            $this->input_details[] = $detail;
            $this->countTotalAmount();

            $this->dispatch('success', 'Material berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menambahkan material: ' . $e->getMessage());
        }
    }

    public function deleteDetails($index)
    {
        unset($this->input_details[$index]);
        $this->input_details = array_values($this->input_details);
        $this->countTotalAmount();
    }

    public function changePrice($id, $value)
    {
        if (isset($this->input_details[$id]['qty'])) {
            $total = $this->input_details[$id]['qty'] * $value;
            $this->input_details[$id]['amt'] = $total;
            $this->input_details[$id]['price'] = $value;
            $this->countTotalAmount();
        }
    }

    public function countTotalAmount()
    {
        $this->total_amount = array_sum(array_column($this->input_details, 'price'));
    }

    public function render()
    {
        return view('livewire.trd-retail1.procurement.purchase-order.material-list', [
            'input_details' => $this->input_details,
            'total_amount' => $this->total_amount,
        ]);
    }
}
