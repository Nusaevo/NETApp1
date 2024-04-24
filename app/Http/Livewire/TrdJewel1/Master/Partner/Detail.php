<?php

namespace App\Http\Livewire\TrdJewel1\Master\Partner;

use App\Http\Livewire\Component\BaseComponent;
use App\Models\TrdJewel1\Master\Partner;
use Illuminate\Validation\Rule;
use DB;
use Exception;

class Detail extends BaseComponent
{
    public $inputs = [];
    public $partnerTypes = [];

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
        $this->customRules  = [
            'inputs.grp' => 'required|string|min:1|max:50',
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
                'max:50'
            ],
        ];
    }

    protected function onLoadForEdit()
    {
        $this->object = Partner::withTrashed()->find($this->objectIdValue);
        $this->inputs = populateArrayFromModel($this->object);
        $decodedData = json_decode($this->object->partner_chars, true);
        switch ($this->object->grp) {
            case Partner::CUSTOMER:
                $this->inputs['ring_size'] = $decodedData['ring_size'] ?? null;
                $this->inputs['partner_ring_size'] = $decodedData['partner_ring_size'] ?? null;
                break;
        }
    }

    public function render()
    {
        return view($this->renderRoute);
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
    ];

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new Partner();
    }

    public function refreshPartnerTypes()
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'PARTNERS_TYPE')
        ->where('app_code', $this->appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();
        $this->partnerTypes = $data->map(function ($data) {
            return [
                'label' =>  $data->str1.' - '.$data->str2,
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
        if (isset($this->inputs['code'])) {
            $existingPartner = Partner::where('code', $this->inputs['code'])
                                      ->where('id', '!=', $this->object->id ?? null)
                                      ->exists();

            if ($existingPartner) {
                $this->addError('inputs.code', $this->trans('message.code_already_exists'));
                throw new Exception($this->trans('message.code_already_exists'));
            }
        }

        $dataToSave = [];
        if (in_array($this->inputs['grp'], [Partner::CUSTOMER])) {
            $dataToSave['ring_size'] = $this->inputs['ring_size'] ?? null;
            $dataToSave['partner_ring_size'] = $this->inputs['partner_ring_size'] ?? null;
        }
        $this->inputs['partner_chars']= json_encode($dataToSave);
        $this->object->fill($this->inputs);
        $this->object->save();
    }

    public function changeStatus()
    {
        $this->change();
    }
}
