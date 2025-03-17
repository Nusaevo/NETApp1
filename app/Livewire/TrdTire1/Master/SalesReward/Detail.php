<?php

namespace App\Livewire\TrdTire1\Master\SalesReward;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Master\SalesReward;
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl};
use App\Models\TrdTire1\Master\{Partner, Material, MatlUom}; // Add MatlUom import
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdTire1\Master\MasterService;
use Illuminate\Support\Facades\{Session};
use Exception;

class Detail extends BaseComponent
{
    #region Constant Variables
    protected $masterService;
    public $inputs = [];
    public $deletedItems = [];
    public $newItems = [];
    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];

    public $returnIds = [];
    public $currencyRate = 0;

    public $rules  = [
        'inputs.code' => 'required',
        'input_details.*.qty' => 'required', // Add validation rules for material details
        'input_details.*.matl_id' => 'required',
    ];

    public $materials;
    public $object_detail;
    public $trhdr_id;
    public $tr_seq;
    public $tr_code;
    public $input_details = [];
    public $total_amount = 0;
    public $total_discount = 0;
    public $total_tax = 0; // New property for total tax
    public $total_dpp = 0; // New property for total tax
    #endregion

    #region Populate Data methods

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.tax'      => $this->trans('tax'),
            'inputs.tr_code'      => $this->trans('tr_code'),
            'inputs.partner_id'      => $this->trans('partner_id'),
            'inputs.send_to_name'      => $this->trans('send_to_name'),
        ];

        $this->masterService = new MasterService();
        $this->materials = $this->masterService->getMaterials(); // Load materials

        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }
            $this->object = SalesReward::find($this->objectIdValue);
            if (!$this->object) {
                $this->dispatch('error', 'Object not found');
                return;
            }
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['status_code_text'] = $this->object->status_Code_text;
            $this->inputs['matl_id'] = $this->object->matl_id; // Set selected matl_id
            $this->object->code = $this->inputs['code'];
            $this->object->descrs = $this->inputs['descrs'];
            $this->object->beg_date = $this->inputs['beg_date'];
            $this->object->end_date = $this->inputs['end_date'];
            $this->object->grp = $this->inputs['grp'];
            $this->object->reward = $this->inputs['reward'];
            $this->object->qty = $this->inputs['qty'];
            $this->object->matl_id = $this->inputs['matl_id'];
        }
    }

    public function onReset()
    {
        $this->reset('inputs', 'input_details'); // Reset inputs and input_details
        $this->object = new SalesReward();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['beg_date']  = date('Y-m-d');
        $this->inputs['end_date']  = date('Y-m-d');
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
    #endregion

    #region CRUD Methods

    public function onValidateAndSave()
    {
        $this->validate();

        if ($this->actionValue == 'Create') {
            $this->object = new SalesReward();
        } else {
            $this->object = SalesReward::find($this->objectIdValue);
        }

        $this->object->code = $this->inputs['code'];
        $this->object->descrs = $this->inputs['descrs'];
        $this->object->beg_date = $this->inputs['beg_date'];
        $this->object->end_date = $this->inputs['end_date'];
        $this->object->grp = $this->inputs['grp'];
        $this->object->reward = $this->inputs['reward'];
        $this->object->qty = $this->inputs['qty'];
        $this->object->matl_id = $this->inputs['matl_id'];

        // Fetch and set matl_code based on matl_id
        $material = Material::find($this->inputs['matl_id']);
        if ($material) {
            $this->object->matl_code = $material->code;
        }

        // ...additional fields...

        $this->object->save();

        if ($this->actionValue == 'Create') {
            return redirect()->route($this->appCode . '.Master.SalesReward.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);
        }
    }

    public function delete()
    {
        try {
            if ($this->object->isOrderCompleted()) {
                $this->dispatch('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
                return;
            }

            if (!$this->object->isOrderEnableToDelete()) {
                $this->dispatch('warning', 'Nota ini tidak bisa delete, karena memiliki material yang sudah dijual.');
                return;
            }

            if (isset($this->object->status_code)) {
                $this->object->status_code =  Status::NONACTIVE;
            }
            $this->object->save();
            $this->object->delete();
            $messageKey = 'generic.string.disable';
            $this->dispatch('success', __($messageKey));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
    }
}
