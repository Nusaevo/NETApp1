<?php

namespace App\Livewire\TrdTire1\Transaction\ProsesGt;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\OrderDtl;
use App\Models\TrdTire1\Transaction\OrderHdr;

class PrintPdf extends BaseComponent
{
    public $orderIds;
    public $selectedProcessDate;
    public $orders = [];

    protected function onPreRender()
    {
        // --- HILANGKAN pengecekan isEditOrView() ---
        if (empty($this->objectIdValue)) {
            $this->dispatch('error', 'Invalid object ID');
            return;
        }

        // Ambil tanggal proses dari parameter
        $this->selectedProcessDate = $this->additionalParam;

        // Ambil OrderHdr beserta OrderDtl yang gt_process_date = selectedProcessDate
        $this->orders = OrderHdr::with([
                'OrderDtl' => fn($q) => $q->where('gt_process_date', $this->selectedProcessDate),
                'Partner'
            ])
            ->whereHas('OrderDtl', fn($q) => $q->where('gt_process_date', $this->selectedProcessDate))
            ->get();
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}

