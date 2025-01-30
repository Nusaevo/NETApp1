<?php

namespace App\Livewire\TrdRetail1\Master\Partner;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdRetail1\Master\Partner;
use App\Services\TrdRetail1\Master\MasterService as MasterMasterService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Services\TrdRetail1\Master\MasterService;
use Exception;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $inputs = [];
    public $partnerTypes = [];
    protected $masterService;

    public $rules  = [
        'inputs.grp' => 'required|string|min:1|max:50',
        'inputs.name' => 'required|string|min:1|max:50',
        // 'inputs.code' => [
        //     'required',
        //     'string',
        //     'min:1',
        //     'max:50'
        // ],
    ];
    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];


    #endregion

    #region Populate Data methods

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs'                => 'Input',
            'inputs.grp'           => $this->trans('partner_type'),
            'inputs.code'           => $this->trans('partner_code'),
            'inputs.name'      => $this->trans('name'),
            'inputs.address'      => $this->trans('address'),
            'inputs.city'      => $this->trans('city'),
            'inputs.country'      => $this->trans('country'),
            'inputs.postal_code'      => $this->trans('postal_code'),
            'inputs.contact_person'      => $this->trans('contact_person'),
            'inputs.ring_size'      => $this->trans('ring_size'),
            'inputs.partner_ring_size'      => $this->trans('partner_ring_size'),
        ];

        $this->masterService = new MasterService();
        $this->partnerTypes = $this->masterService->getPartnerTypes();
        if($this->isEditOrView())
        {
            $this->object = Partner::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
        }

        $this->inputs['option'] = "";
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new Partner();
        $this->inputs = populateArrayFromModel($this->object);
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
        // if (isset($this->inputs['code'])) {
        //     $existingPartner = Partner::where('code', $this->inputs['code'])
        //                               ->where('id', '!=', $this->object->id ?? null)
        //                               ->exists();

        //     if ($existingPartner) {
        //         $this->addError('inputs.code', $this->trans('message.code_already_exists'));
        //         throw new Exception($this->trans('message.code_already_exists'));
        //     }
        // } else {
        //     $this->inputs['code'] = $this->generateNewCode($this->inputs['name']);
        // }
        $initialCode = strtoupper(substr($this->inputs['name'], 0, 1));
        if(!$this->object->isNew())
        {
            if (isset($this->inputs['code']) && $initialCode !== strtoupper(substr($this->inputs['code'], 0, 1))) {
                $errorMessage = 'Kode awal dari nama tidak sesuai dengan kode produk.';
                $this->addError('inputs.name', $errorMessage);
                throw new Exception($errorMessage);
            }
        }

        if (isNullOrEmptyString($this->inputs['code'])) {
            $this->inputs['code'] = Partner::generateNewCode($this->inputs['name']);
        }
        $this->object->fill($this->inputs);
        $this->object->save();
    }

    public function changeStatus()
    {
        $this->change();
    }

    #endregion

    #region Component Events

    #endregion

}
