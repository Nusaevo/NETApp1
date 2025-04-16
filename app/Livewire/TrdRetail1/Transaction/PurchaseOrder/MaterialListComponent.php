<?php

namespace App\Livewire\TrdRetail1\Transaction\PurchaseOrder;

use App\Livewire\Component\DetailComponent;
use App\Models\TrdJewel1\Master\MatlUom;
use App\Models\TrdRetail1\Master\Material;
use App\Services\TrdRetail1\Master\MasterService;
use App\Models\TrdRetail1\Transaction\{OrderHdr, OrderDtl};
use App\Models\SysConfig1\ConfigConst;
use Exception;
use Illuminate\Foundation\Exceptions\Renderer\Listener;
use Livewire\Attributes\Modelable;

class MaterialListComponent extends DetailComponent
{
    protected $masterService;
    public $object_detail;
    public $trhdr_id;
    public $tr_seq;
    public $tr_code;
    public $input_details = [];
    protected $listeners = [
        'saveMaterialList'  => 'saveMaterialList'
    ];
    protected $rules = [
        'input_details.*.qty' => 'required',
        'input_details.*.matl_id' => 'required',
        'wh_code' => 'required',
        'input_details.*.matl_uom' => 'required',
    ];

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }

    public function onReset()
    {
        $this->reset('input_details');
        $this->object = new OrderHdr();
        $this->object = new OrderDtl();
    }

    protected function onPreRender()
    {
        $this->customValidationAttributes = [
            'input_details.*.matl_id' => $this->trans('code'),
            'input_details.*.qty' => $this->trans('qty'),
        ];
        if (!empty($this->objectIdValue)) {
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->loadDetails();
        }
    }
    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

}
