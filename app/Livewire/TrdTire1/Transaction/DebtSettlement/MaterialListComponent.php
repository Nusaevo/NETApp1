<?php

namespace App\Livewire\TrdTire1\Transaction\DebtSettlement;

use App\Livewire\Component\DetailComponent;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdTire1\Master\Material;
use App\Services\TrdTire1\Master\MasterService;
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl, PaymentHdr, PaymentSrc};
use App\Models\TrdTire1\Master\MatlUom; // Add this import
use Exception;

class MaterialListComponent extends DetailComponent
{
    public $materials;
    protected $masterService;
    public $PaymentType = [];
    public $object_detail;
    public $trhdr_id;
    public $tr_seq;
    public $tr_code;
    public $input_details = [];
    public $activePaymentItemKey = null;

    protected $rules = [
        'input_details.*.pay_type_code' => 'required',
    ];

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }

    public function onReset()
    {
        $this->reset('input_details'); // Reset input_details instead of inputs
        $this->object = new PaymentHdr();
        $this->object = new PaymentSrc();
    }

    protected function onPreRender()
    {
        $this->masterService = new MasterService();
        $this->PaymentType = $this->masterService->getPaymentTypeData();
        $this->materials = $this->masterService->getMaterials();

        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }
            $this->object = PaymentHdr::with(['details'])->withTrashed()->find($this->objectIdValue);
            if (!$this->object) {
                $this->dispatch('error', 'Object not found');
                return;
            }
            $this->inputs = populateArrayFromModel($this->object);
            foreach ($this->object->details as $key => $detail) {
                $this->input_details[$key] = populateArrayFromModel($detail);
                $this->input_details[$key]['pay_type_code'] = $detail->pay_type_code;
                $this->input_details[$key]['pay_type_id'] = $detail->pay_type_id;
            }
        }
    }

    public function addItem()
    {
        if (!empty($this->objectIdValue)) {
            try {
                // Tambahkan item baru dengan placeholder untuk tr_type
                $this->input_details[] = [
                    'tr_type' => null, // field untuk tipe pembayaran per item
                ];
                // Set indeks item baru sebagai aktif (opsional: jika ingin langsung atur payment)
                $newKey = array_key_last($this->input_details);
                $this->activePaymentItemKey = $newKey;
                $this->dispatch('success', __('generic.string.add_item'));
            } catch (Exception $e) {
                $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
            }
        } else {
            $this->dispatch('error', __('generic.error.save', ['message' => 'Tolong save Header terlebih dahulu']));
        }
    }

    public function openPaymentDialog($key)
    {
        $this->activePaymentItemKey = $key;
        $this->dispatch('openPaymentDialogBox');
    }
    public function confirmPayment()
    {
        if ($this->activePaymentItemKey === null) {
            $this->dispatch('error', __('No active item selected.'));
            return;
        }
        $key = $this->activePaymentItemKey;
        $this->validate([
            "input_details.$key.tr_type" => 'required',
        ]);
        $trType = $this->input_details[$key]['tr_type'];
        $this->input_details[$key]['pay_type_code'] = $trType;

        // Set pay_type_id based on pay_type_code
        $payType = ConfigConst::where('str1', $trType)->first();
        $this->input_details[$key]['pay_type_id'] = $payType ? $payType->id : null;

        $this->dispatch('success', __('Payment type has been confirmed and updated for item ' . ($key + 1)));

        // Tutup dialog box setelah konfirmasi berhasil
        $this->dispatch('closePaymentDialogBox');

        // Reset activePaymentItemKey jika sudah selesai
        $this->activePaymentItemKey = null;
    }

    public function deleteItem($index)
    {
        try {
            if (!isset($this->input_details[$index])) {
                throw new Exception(__('generic.error.delete_item', ['message' => 'Item not found.']));
            }

            // Permanently delete the item from the database if it exists
            if (!empty($this->objectIdValue) && isset($this->input_details[$index]['id'])) {
                PaymentSrc::where('id', $this->input_details[$index]['id'])->forceDelete();
            }

            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            $this->dispatch('success', __('generic.string.delete_item'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = PaymentHdr::where('id', $this->object->id) // Ensure 'id' is used instead of 'tr_id'
                ->where('tr_type', $this->object->tr_type)
                ->get();

            foreach ($this->object_detail as $key => $detail) {
                $this->input_details[$key] = populateArrayFromModel($detail);
                $this->input_details[$key]['pay_type_code'] = $detail->pay_type_code;
                $this->input_details[$key]['pay_type_id'] = $detail->pay_type_id;
            }
        }
    }

    public function SaveItem()
    {
        $this->Save();
        return redirect()->route('TrdTire1.Transaction.DebtSettlement.Detail', [
            'action' => encryptWithSessionKey('Edit'),
            'objectId' => encryptWithSessionKey($this->object->id)
        ]);
    }

    public function onValidateAndSave()
    {
        $this->validate();

        foreach ($this->input_details as $key => $detail) {
            $tr_seq = $key + 1;

            $data = [
                'tr_type' => $this->object->tr_type,
                'pay_type_id' => $detail['pay_type_id'] ?? null,
                'pay_type_code' => $detail['pay_type_code'] ?? null,
            ];

            if (!empty($this->inputs['pay_type_id'])) {
                $PaymentType = ConfigConst::find($this->inputs['pay_type_id']);
                $this->inputs['pay_type_code'] = $PaymentType->str1;
            }

            PaymentSrc::updateOrCreate(
                [
                    'trhdr_id' => $this->objectIdValue,
                    'tr_seq'   => $tr_seq,
                ],
                $data
            );
        }

        $this->dispatch('success', __('Data Payment berhasil disimpan.'));
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
