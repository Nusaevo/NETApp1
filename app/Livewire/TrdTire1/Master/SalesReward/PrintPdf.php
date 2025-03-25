<?php

namespace App\Livewire\TrdTire1\Master\SalesReward;

use App\Enums\TrdTire1\Status;
use App\Models\TrdTire1\Master\SalesReward;
use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Livewire\Component\BaseComponent;

class PrintPdf extends BaseComponent
{
    public $object;
    public $objectId;
    public $returnIds; // Added to store the IDs

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        $this->objectIdValue = decryptWithSessionKey($objectId);
    }

    protected function onPreRender()
    {
        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }

            // Ambil record yang dipilih berdasarkan objectIdValue
            $selectedSalesReward = SalesReward::findOrFail($this->objectIdValue);
            $code = $selectedSalesReward->code;

            // Ambil semua record dengan nilai kolom code yang sama
            $salesRewards = SalesReward::where('code', $code)->get();
            $this->returnIds = $salesRewards->pluck('id')->toArray();

            // Update status_code setiap record menjadi PRINT
            foreach ($salesRewards as $salesReward) {
                $salesReward->status_code = Status::PRINT;
                $salesReward->save();
            }

            // Jika perlu, set object ke record yang dipilih
            $this->object = $selectedSalesReward;
        }
    }


    protected function onLoadForEdit()
    {
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    protected function onPopulateDropdowns()
    {

    }

    protected function onReset()
    {
    }

    public function onValidateAndSave()
    {
    }
}
