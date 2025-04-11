<?php

namespace App\Livewire\TrdRetail1\Transaction\PurchaseOrder;

use App\Livewire\Component\DetailComponent;
use App\Models\TrdRetail1\Transaction\{OrderHdr, OrderDtl, ReturnHdr, ReturnDtl};
use App\Models\TrdRetail1\Master\Material;
use App\Models\SysConfig1\ConfigConst;
use Exception;

class ReturnListComponent extends DetailComponent
{
    public $object_detail = [];
    public $returnHdr;
    public $return_details = [];
    public $selectedReturnMaterials = [];

    protected $rules = [
        'return_details.*.qty' => 'required|numeric|min:1',
        'return_details.*.matl_id' => 'required',
        'return_details.*.matl_uom' => 'required',
    ];

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }

    protected function onPreRender()
    {
        if (!empty($this->objectIdValue)) {
            // 1. Cari OrderHdr yang sedang dibuka
            $orderHdr = OrderHdr::withTrashed()->find($this->objectIdValue);
            if (!$orderHdr) return;

            $this->inputs = populateArrayFromModel($orderHdr);
            $this->loadOriginalOrderDetails($orderHdr);

            // 2. Cari ReturnHdr berdasarkan reff_code = tr_id OrderHdr
            $this->returnHdr = ReturnHdr::where('reff_code', $orderHdr->tr_id)->first();

            if ($this->returnHdr) {
                $this->object = $this->returnHdr;
                $this->showReturn = true;
                $this->loadReturnDetails();
            } else {
                $this->showReturn = false;
            }
        }
    }


    protected function loadOriginalOrderDetails()
    {
        if ($this->object && $this->object->reff_code) {
            $orderHdr = OrderHdr::where('tr_id', $this->object->reff_code)->first();
            if ($orderHdr) {
                $this->object_detail = OrderDtl::GetByOrderHdr($orderHdr->id, $orderHdr->tr_type)->get();
            }
        }
    }

    protected function loadReturnDetails()
    {
        if (!empty($this->object)) {
            $details = ReturnDtl::where('trhdr_id', $this->object->id)->get();
            foreach ($details as $key => $detail) {
                $this->return_details[$key] = populateArrayFromModel($detail);
            }
        }
    }

    public function openReturnDialogBox()
    {
        if (empty($this->object_detail)) {
            $this->dispatch('error', 'Tidak ada data Order untuk retur.');
            return;
        }

        $this->selectedReturnMaterials = [];
        $this->dispatch('openReturnDialogBox');
    }

    public function selectReturnMaterial($materialId)
    {
        $key = array_search($materialId, $this->selectedReturnMaterials);
        if ($key !== false) {
            unset($this->selectedReturnMaterials[$key]);
        } else {
            $this->selectedReturnMaterials[] = $materialId;
        }

        $this->selectedReturnMaterials = array_values($this->selectedReturnMaterials);
    }

    public function confirmReturnSelection()
    {
        foreach ($this->selectedReturnMaterials as $matl_id) {
            $exists = collect($this->return_details)->contains('matl_id', $matl_id);
            if ($exists) {
                $this->dispatch('error', "Material sudah ada dalam retur.");
                continue;
            }

            $item = collect($this->object_detail)->firstWhere('matl_id', $matl_id);
            if (!$item) continue;

            $this->return_details[] = [
                'matl_id' => $item->matl_id,
                'matl_code' => $item->matl_code,
                'matl_descr' => $item->matl_descr,
                'qty' => $item->qty,
                'matl_uom' => $item->matl_uom,
                'amt' => $item->amt,
            ];
        }

        $this->dispatch('success', 'Item berhasil ditambahkan.');
        $this->dispatch('closeReturnDialogBox');
    }

    public function deleteReturnItem($index)
    {
        if (isset($this->return_details[$index])) {
            unset($this->return_details[$index]);
            $this->return_details = array_values($this->return_details);
            $this->dispatch('success', 'Item retur dihapus.');
        }
    }

    public function saveReturnItems()
    {
        if (empty($this->objectIdValue)) {
            $this->dispatch('error', 'Silakan simpan header Return terlebih dahulu.');
            return;
        }

        $this->validate();

        $existing = ReturnDtl::where('trhdr_id', $this->object->id)
            ->get()
            ->keyBy('tr_seq')
            ->toArray();

        $inputKeyed = collect($this->return_details)->keyBy('tr_seq')->toArray();
        $toDelete = array_diff_key($existing, $inputKeyed);

        foreach ($toDelete as $tr_seq => $detail) {
            ReturnDtl::find($detail['id'])?->forceDelete();
        }

        $toSave = [];
        foreach ($this->return_details as $index => $item) {
            $item['tr_seq'] = $index + 1;
            $item['trhdr_id'] = $this->object->id;
            $item['tr_type'] = $this->object->tr_type;
            $item['tr_id'] = $this->object->tr_id;

            $material = Material::find($item['matl_id']);
            $item['matl_code'] = $material?->code;
            $item['amt'] = ($item['qty'] ?? 0) * ($item['price'] ?? 0);

            $toSave[] = $item;
        }

        $this->object->saveReturnDetails($this->object->tr_type, $toSave);
        $this->dispatch('success', 'Return item berhasil disimpan.');
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
