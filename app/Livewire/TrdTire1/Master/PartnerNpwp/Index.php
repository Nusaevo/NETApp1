<?php

namespace App\Livewire\TrdTire1\Master\PartnerNpwp;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Master\Partner;
use App\Models\TrdTire1\Master\PartnerDetail;
use Illuminate\Support\Facades\DB;
use Exception;

class Index extends BaseComponent
{
    public $selectedPartnerId = null;
    public $selectedPartner = null;
    public $showNpwpList = false;
    public $npwpList = [];
    public $ddPartner = [];
    public $inputDetails = [];
    public $isComponent = true;

    public $rules = [
        'inputDetails.*.npwp' => 'required',
        'inputDetails.*.wp_name' => 'required',
        'inputDetails.*.wp_location' => 'required',
    ];
    protected $listeners = [
        'DropdownSelected' => 'DropdownSelected'
    ];
    protected function onPreRender()
    {
        $this->setupDropdownPartner();
        $this->customValidationAttributes = [
            'inputDetails.*.npwp'        => $this->trans('npwp'),
            'inputDetails.*.wp_name'     => $this->trans('wp_name'),
            'inputDetails.*.wp_location' => $this->trans('wp_location'),
        ];
    }

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue, $additionalParam);
    }

    public function setupDropdownPartner()
    {
        $this->ddPartner = [
            'placeHolder' => 'Ketik untuk mencari partner...',
            'optionLabel' => "name,code,type_code",
            'query' => "SELECT id, name, code, type_code
                       FROM partners
                       WHERE deleted_at IS NULL AND status_code = 'A'
                       "
        ];
    }

    public function onPartnerChanged($partnerId)
    {
        $this->selectedPartnerId = $partnerId;
        if ($partnerId) {
            $this->selectedPartner = Partner::find($partnerId);
            $this->loadNpwpList();
            $this->showNpwpList = true;
        } else {
            $this->selectedPartner = null;
            $this->showNpwpList = false;
            $this->npwpList = [];
            $this->inputDetails = [];
        }
    }

    public function loadNpwpList()
    {
        if ($this->selectedPartnerId) {
            $partnerDetail = PartnerDetail::where('partner_id', $this->selectedPartnerId)->first();
            $this->inputDetails = $partnerDetail ? ($partnerDetail->wp_details ?? []) : [];
        }
    }

    public function addItem()
    {
        if (!empty($this->selectedPartnerId)) {
            try {
                $this->inputDetails[] = [
                    'npwp' => '',
                    'wp_name' => '',
                    'wp_location' => '',
                ];
                $this->dispatch('success', __('generic.string.add_item'));
            } catch (Exception $e) {
                $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
            }
        } else {
            $this->dispatch('error', __('generic.error.save', ['message' => 'Tolong pilih partner terlebih dahulu']));
        }
    }

    public function deleteItem($index)
    {
        try {
            if (!isset($this->inputDetails[$index])) {
                throw new Exception(__('generic.error.delete_item', ['message' => 'Item not found.']));
            }

            unset($this->inputDetails[$index]);
            $this->inputDetails = array_values($this->inputDetails);
            $this->dispatch('success', __('generic.string.delete_item'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    public function saveNpwp()
    {
        if (!$this->selectedPartnerId) {
            $this->dispatch('error', 'Please select a partner first');
            return;
        }

        $this->validate();

        try {
            DB::beginTransaction();

            $wpDetailsArray = [];
            foreach ($this->inputDetails as $detail) {
                $wpDetailsArray[] = [
                    'npwp' => $detail['npwp'],
                    'wp_name' => $detail['wp_name'],
                    'wp_location' => $detail['wp_location'],
                ];
            }

            $partnerDetail = PartnerDetail::where('partner_id', $this->selectedPartnerId)->first();
            $partnerData = [
                'partner_grp' => $this->selectedPartner->grp,
                'partner_code' => $this->selectedPartner->code,
                'wp_details' => $wpDetailsArray,
            ];

            if ($partnerDetail) {
                $partnerDetail->update($partnerData);
            } else {
                PartnerDetail::create(array_merge(['partner_id' => $this->selectedPartnerId], $partnerData));
            }

            DB::commit();
            $this->dispatch('success', __('NPWP saved successfully!'));
            $this->loadNpwpList();

        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatch('error', __('generic.error.save', ['message' => $e->getMessage()]));
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
