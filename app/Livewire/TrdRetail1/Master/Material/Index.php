<?php

namespace App\Livewire\TrdRetail1\Master\Material;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdRetail1\Master\MatlUom;

class Index extends BaseComponent
{
    // Modal properties
    public $newBuyingPrice = '';
    public $newSellingPrice = '';
    public $selectedIds = [];

    protected $listeners = [
        'openBuyingPriceModal' => 'openBuyingPriceModal',
        'openSellingPriceModal' => 'openSellingPriceModal'
    ];

    protected function onPreRender()
    {
    }

    public function openBuyingPriceModal($selectedIds)
    {
        $this->selectedIds = $selectedIds;
        $this->newBuyingPrice = '';
        $this->dispatch('openBuyingPriceDialog');
    }

    public function openSellingPriceModal($selectedIds)
    {
        $this->selectedIds = $selectedIds;
        $this->newSellingPrice = '';
        $this->dispatch('openSellingPriceDialog');
    }

    public function openBuyingPriceDialog()
    {
        // Dialog opened event
    }

    public function closeBuyingPriceDialog()
    {
        $this->newBuyingPrice = '';
        $this->selectedIds = [];
    }

    public function openSellingPriceDialog()
    {
        // Dialog opened event
    }

    public function closeSellingPriceDialog()
    {
        $this->newSellingPrice = '';
        $this->selectedIds = [];
    }

    public function confirmBuyingPriceUpdate()
    {
        if (empty($this->newBuyingPrice) || !is_numeric($this->newBuyingPrice)) {
            $this->dispatch('error', 'Invalid price value');
            return;
        }

        $updatedCount = 0;
        foreach ($this->selectedIds as $uomId) {
            $uom = MatlUom::find($uomId);
            if ($uom) {
                $uom->update(['buying_price' => $this->newBuyingPrice]);
                $updatedCount++;
            }
        }

        $this->closeBuyingPriceDialog();
        $this->dispatch('closeBuyingPriceDialog');
        $this->dispatch('refreshTable');
        $message = $updatedCount > 1
            ? "Successfully updated buying price for {$updatedCount} materials"
            : "Successfully updated buying price for 1 material";
        $this->dispatch('success', $message);
    }

    public function confirmSellingPriceUpdate()
    {
        if (empty($this->newSellingPrice) || !is_numeric($this->newSellingPrice)) {
            $this->dispatch('error', 'Invalid price value');
            return;
        }

        $updatedCount = 0;
        foreach ($this->selectedIds as $uomId) {
            $uom = MatlUom::find($uomId);
            if ($uom) {
                $uom->update(['selling_price' => $this->newSellingPrice]);
                $updatedCount++;
            }
        }

        $this->closeSellingPriceDialog();
        $this->dispatch('closeSellingPriceDialog');
        $this->dispatch('refreshTable');
        $message = $updatedCount > 1
            ? "Successfully updated selling price for {$updatedCount} materials"
            : "Successfully updated selling price for 1 material";
        $this->dispatch('success', $message);
    }

    public function render()
    {
        return view(getViewPath(__NAMESPACE__, class_basename($this)));
    }
}
