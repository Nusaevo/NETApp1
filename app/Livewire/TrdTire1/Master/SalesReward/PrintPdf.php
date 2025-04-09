<?php

namespace App\Livewire\TrdTire1\Master\SalesReward;

use App\Enums\TrdTire1\Status;
use App\Models\TrdTire1\Master\SalesReward;
use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Livewire\Component\BaseComponent;
use Exception;

class PrintPdf extends BaseComponent
{
    public $object;
    public $objectId;
    public $returnIds; // Added to store the IDs


    protected function onPreRender()
    {
        if (empty($this->objectIdValue) && !empty($this->objectId)) {
            try {
                $this->objectIdValue = decryptWithSessionKey($this->objectId);
            } catch (Exception $e) {
                $this->dispatch('error', 'Invalid object ID format.');
                return;
            }
        }
        // if (empty($this->objectIdValue)) {
        //     $this->dispatch('error', 'Invalid object ID');
        //     return;
        // }

        // Ambil record berdasarkan code
        $salesReward = SalesReward::where('code', $this->objectIdValue)->first();
        if (!$salesReward) {
            $this->dispatch('error', 'Object not found');
            return;
        }
        // Set header object
        $this->object = $salesReward;
        // Ambil semua detail berdasarkan code yang sama
        $details = SalesReward::where('code', $this->object->code)->get();
        $this->returnIds = $details->pluck('id')->toArray();
        $this->object->beg_date = date('Y-m-d', strtotime($this->object->beg_date));
        $this->object->end_date = date('Y-m-d', strtotime($this->object->end_date));

        SalesReward::where('code', $this->object->code)->update(['status_code' => Status::PRINT]);
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
