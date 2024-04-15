<?php

namespace App\Http\Livewire\TrdJewel1\Master\Partner;

use App\Http\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Master\Partner;
use App\Models\SysConfig1\ConfigConst;
use Illuminate\Validation\Rule;

class Detail extends BaseComponent
{
    public $inputs = [];
    public $partnerTypes = [];

    protected function onPreRender()
    {

    }

    protected function onLoadForEdit()
    {
        $this->object = Partner::withTrashed()->find($this->objectIdValue);
        $this->inputs = populateArrayFromModel($this->object);
    }

    public function render()
    {
        return view($this->renderRoute);
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    protected function rules()
    {
        $rules = [
            'inputs.name' => 'required|string|min:1|max:50',
            'inputs.address' => 'string|min:1|max:50',
            'inputs.city' => 'string|min:1|max:20',
            'inputs.country' => 'string|min:1|max:20',
            'inputs.postal_code' => 'string|min:1|max:10',
            'inputs.contact_person' => 'string|min:1|max:255',
            'inputs.code' => [
                'required',
                'string',
                'min:1',
                'max:50',
                Rule::unique('partners', 'code')->ignore($this->object ? $this->object->id : null),
            ],
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input Menu',
        'inputs.*'              => 'Input Menu',
        'inputs.code'           => 'Customer Code',
        'inputs.name'      => 'Name',
        'inputs.address'      => 'Address',
        'inputs.city'      => 'City',
        'inputs.country'      => 'Country',
        'inputs.postal_code'      => 'Postal Code',
        'inputs.contact_person'      => 'Contact',
    ];

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new Partner();
    }

    public function refreshPartnerTypes()
    {
        $data = ConfigConst::where('app_code', $this->appCode)
            ->where('const_group', 'PARTNERS_TYPE')
            ->orderBy('seq')
            ->get();
        $this->partnerTypes = $data->map(function ($data) {
            return [
                'label' => $data->str2,
                'value' => $data->str1,
            ];
        })->toArray();
        $this->inputs['grp'] = null;
    }

    protected function onPopulateDropdowns()
    {
        $this->refreshPartnerTypes();
    }

    public function onValidateAndSave()
    {
        $this->object->fill($this->inputs);
        $this->object->save();
    }

    public function changeStatus()
    {
        $this->change();
    }
}
