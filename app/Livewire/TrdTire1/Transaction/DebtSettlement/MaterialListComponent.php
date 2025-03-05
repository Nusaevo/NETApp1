<?php

namespace App\Livewire\TrdTire1\Transaction\DebtSettlement;

use App\Livewire\Component\DetailComponent;
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
    public $total_amount = 0;
    public $total_discount = 0;
    public $total_tax = 0; // New property for total tax
    public $total_dpp = 0; // New property for total tax
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

        if (!empty($this->objectIdValue)) {
            $this->object = PaymentHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->loadDetails();
        }
    }

    public function addItem()
    {
        if (!empty($this->objectIdValue)) {
            try {
                // Tambahkan item baru dengan placeholder untuk tr_type
                $this->input_details[] = [
                    'matl_id' => null,
                    'qty' => null,
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
        $this->dispatch('success', __('Payment type has been confirmed and updated for item ' . ($key + 1)));

        // Tutup dialog box setelah konfirmasi berhasil
        $this->dispatch('closePaymentDialogBox');

        // Reset activePaymentItemKey jika sudah selesai
        $this->activePaymentItemKey = null;
    }


    public function onMaterialChanged($key, $matl_id)
    {
        if ($matl_id) {
            $material = Material::find($matl_id);
            if ($material) {
                $matlUom = MatlUom::where('matl_id', $matl_id)->first(); // Fetch MatlUom using matl_id
                if ($matlUom) {
                    $this->input_details[$key]['matl_id'] = $material->id;
                    $this->input_details[$key]['price'] = $matlUom->selling_price; // Use selling_price from MatlUom
                    $this->input_details[$key]['matl_uom'] = $material->uom;
                    $this->input_details[$key]['matl_descr'] = $material->name;
                    $this->updateItemAmount($key);
                } else {
                    $this->dispatch('error', __('generic.error.material_uom_not_found'));
                }
            } else {
                $this->dispatch('error', __('generic.error.material_not_found'));
            }
        }
    }

    public function updateItemAmount($key)
    {
        if (!empty($this->input_details[$key]['qty']) && !empty($this->input_details[$key]['price'])) {
            $amount = $this->input_details[$key]['qty'] * $this->input_details[$key]['price'];
            $discountPercent = $this->input_details[$key]['disc_pct'] ?? 0;
            $discountAmount = $amount * ($discountPercent / 100);
            $this->input_details[$key]['amt'] = $amount - $discountAmount;
        } else {
            $this->input_details[$key]['amt'] = 0;
        }

        $this->input_details[$key]['amt_idr'] = rupiah($this->input_details[$key]['amt']);

        $this->recalculateTotals();
    }

    public function recalculateTotals()
    {
        $this->calculateTotalAmount();
        $this->calculateTotalDiscount();

        $this->dispatch('updateAmount', [
            'total_amount' => $this->total_amount,
            'total_discount' => $this->total_discount,
            'total_tax' => $this->total_tax,
            'total_dpp' => $this->total_dpp,
        ]);
    }

    private function calculateTotalAmount()
    {
        $this->total_amount = array_sum(array_map(function ($detail) {
            $qty = $detail['qty'] ?? 0;
            $price = $detail['price'] ?? 0;
            $discountPercent = $detail['disc_pct'] ?? 0;
            $amount = $qty * $price;
            $discountAmount = $amount * ($discountPercent / 100);
            return $amount - $discountAmount;
        }, $this->input_details));

        $this->total_amount = round($this->total_amount, 2);
    }

    private function calculateTotalDiscount()
    {
        $this->total_discount = array_sum(array_map(function ($detail) {
            $qty = $detail['qty'] ?? 0;
            $price = $detail['price'] ?? 0;
            $discountPercent = $detail['disc_pct'] ?? 0;
            $amount = $qty * $price;
            $discountAmount = $amount * ($discountPercent / 100);
            return $discountAmount;
        }, $this->input_details));

        $this->total_discount = round($this->total_discount, 2);
    }


    public function deleteItem($index)
    {
        try {
            if (!isset($this->input_details[$index])) {
                throw new Exception(__('generic.error.delete_item', ['message' => 'Item not found.']));
            }

            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            $this->dispatch('success', __('generic.string.delete_item'));
            $this->recalculateTotals();
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
                $this->updateItemAmount($key); // Ensure each input item is initialized and updated
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
                'tr_type'       => $this->object->tr_type,
                'pay_type_id'   => $detail['tr_type'],
                'pay_type_code' => isset($this->PaymentType[$detail['tr_type']])
                    ? $this->PaymentType[$detail['tr_type']]
                    : null,
            ];

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
