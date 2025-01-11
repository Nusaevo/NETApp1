<?php

namespace App\Livewire\TrdTire1\Master\Partner;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Master\Partner;
use App\Services\TrdTire1\Master\MasterService as MasterMasterService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Services\TrdTire1\Master\MasterService;
use Exception;
use Illuminate\Support\Facades\Log;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $inputs = [];
    public $PartnerType = [];
    protected $masterService;
    public $generalFilled = false;

    protected $defaultPartnerChars = [
        'IRC' => false,
        'GT' => false,
        'ZN' => false,
    ];

    public $rules  = [
        'inputs.grp' => 'required|string|min:1|max:50',
        'inputs.name' => 'required|string|min:1|max:50',
        'inputs.address' => 'required|string|min:1|max:50',
        'inputs.country' => 'required|string|min:1|max:50',
        'inputs.province' => 'required|string|min:1|max:50',
        'inputs.city' => 'required|string|min:1|max:50',
    ];
    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    #endregion

    #region Populate Data methods

    protected function onPreRender()
    {
        $this->customValidationAttributes = [
            'inputs'             => 'Input',
            'inputs.grp'         => $this->trans('kategori'),
            'inputs.name'        => $this->trans('name'),
            'inputs.address'     => $this->trans('address'),
            'inputs.country'     => $this->trans('country'),
            'inputs.province'    => $this->trans('province'),
            'inputs.city'        => $this->trans('city'),
        ];


        $this->masterService = new MasterService();

        $this->PartnerType = $this->masterService->getPartnerTypeData();
        if ($this->isEditOrView()) {
            $this->object = Partner::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['partner_chars'] = array_replace(
                $this->defaultPartnerChars,
                $this->object->partner_chars ?? []
            );
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new Partner();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['partner_chars'] = $this->defaultPartnerChars;
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
            $initialCode = strtoupper(substr($this->inputs['name'], 0, 1));
            if (!$this->object->isNew()) {
                if (isset($this->inputs['code']) && $initialCode !== strtoupper(substr($this->inputs['code'], 0, 1))) {
                    $errorMessage = 'Kode awal dari nama tidak sesuai dengan kode partner.';
                    $this->addError('inputs.name', $errorMessage);
                    throw new Exception($errorMessage);
                }
            }
            if (isNullOrEmptyString($this->inputs['code'])) {
                $this->inputs['code'] = Partner::generateNewCode($this->inputs['name'], $this->inputs['grp']);
            }
            $this->object->fillAndSanitize($this->inputs);
            $this->object->save();

            if ($this->object->PartnerDetail == null) {
                $this->object->PartnerDetail()->create([
                    'partner_id' => $this->object->id
                ]);
            }

            if($this->actionValue == 'Create')
            {
                return redirect()->route($this->appCode.'.Master.Partner.Detail', [
                    'action' => encryptWithSessionKey('Edit'),
                    'objectId' => encryptWithSessionKey($this->object->id)
                ]);
            }
    }

    public function changeStatus()
    {
        $this->change();
    }

    #endregion

    #region Component Events

    #endregion

}
