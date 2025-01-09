<?php

namespace App\Livewire\TrdRetail1\Procurement\PurchaseOrder;

use App\Livewire\Component\BaseListViewComponent;
use App\Models\TrdRetail1\Master\Material;
use Exception;

class MaterialListComponent extends BaseListViewComponent
{
    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }
    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
