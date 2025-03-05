<?php

namespace App\Livewire\TrdTire1\Transaction\DebtSettlement;

use App\Livewire\Component\DetailComponent;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdTire1\Master\Material;
use App\Services\TrdTire1\Master\MasterService;
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl, PaymentHdr, PaymentSrc};
use App\Models\TrdTire1\Master\MatlUom; // Add this import
use App\Models\TrdTire1\Master\Partner;
use Exception;

class MaterialListComponent extends DetailComponent
{
    public $materials;
    protected $masterService;
    public $PaymentType = [];
    public $bankOptions = [];
    public $object_detail;
    public $trhdr_id;
    public $tr_seq;
    public $tr_code;
    public $input_details = [];
    public $deletedItems = [];
    public $activePaymentItemKey = null;
    public $isCash = "false";
    public $isGiro = "false";
    public $isTrf = "false";
    public $isAdv = "false";

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
        foreach ($this->input_details as $key => $detail) {
            $this->input_details[$key]['bank_date'] = date('Y-m-d');
        }
    }

    protected function onPreRender()
    {
        $this->masterService = new MasterService();
        $this->PaymentType   = $this->masterService->getPaymentTypeData();
        $this->materials     = $this->masterService->getMaterials();

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
                $this->input_details[$key]               = populateArrayFromModel($detail);
                $this->input_details[$key]['tr_type']      = $detail->pay_type_code;
                $this->input_details[$key]['pay_type_code'] = $detail->pay_type_code;
                $this->input_details[$key]['pay_type_id']   = $detail->pay_type_id;
                $this->input_details[$key]['bank_id_transfer'] = null;
            }
            // Panggil fungsi untuk mengambil data bank berdasarkan partner_id PaymentHdr
            $this->loadBankOptions();
        }
    }


    public function addItem()
    {
        if (!empty($this->objectIdValue)) {
            try {
                $this->input_details[] = [
                    'tr_type' => null,
                    'bank_id_transfer' => null
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
    protected function loadBankOptions()
    {
        if (!empty($this->object->partner_id)) {
            // Ambil data partner sesuai partner_id pada PaymentHdr
            $partner = Partner::find($this->object->partner_id);
            if ($partner && $partner->partnerDetail && !empty($partner->partnerDetail->banks)) {
                $banks = $partner->partnerDetail->banks;
                // Jika data berupa string JSON, decode dulu
                if (is_string($banks)) {
                    $banks = json_decode($banks, true);
                }
                // Ubah data bank menjadi format yang sesuai untuk dropdown dengan tampilan "bank_acct - bank_name"
                $this->bankOptions = array_map(function ($bank) {
                    return [
                        'label' => ($bank['bank_acct'] ?? '') . ' - ' . ($bank['bank_name'] ?? ''),
                        'value' => $bank['id'] ?? ($bank['bank_id'] ?? null),
                    ];
                }, $banks);
            } else {
                $this->bankOptions = [];
            }
        }
        // dd($this->bankOptions);
    }


    public function openPaymentDialog($key)
    {
        $this->activePaymentItemKey = $key;

        // Jika data tr_type sudah ada (misalnya saat edit), langsung update flag status input
        if (isset($this->input_details[$key]['tr_type']) && !empty($this->input_details[$key]['tr_type'])) {
            $this->onPaymentTypeChange();
        } else {
            // Jika belum ada, pastikan semua flag dinonaktifkan
            $this->isCash = $this->isGiro = $this->isTrf = $this->isAdv = false;
        }

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

            // Jika item sudah tersimpan (mempunyai id), simpan id-nya untuk penghapusan nanti
            if (!empty($this->objectIdValue) && isset($this->input_details[$index]['id'])) {
                $this->deletedItems[] = $this->input_details[$index]['id'];
            }

            // Hapus item dari array input
            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            $this->dispatch('success', __('generic.string.delete_item'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    public function onPaymentTypeChange()
    {
        $key = $this->activePaymentItemKey;
        $trType = $this->input_details[$key]['tr_type'] ?? null;

        // Konfigurasi tiap tipe pembayaran dengan field yang harus direset jika tidak aktif
        $paymentConfig = [
            'CASH' => [
                'flag' => 'isCash',
                'clear' => ['bank_code_tunai', 'amt_tunai']
            ],
            'GIRO' => [
                'flag' => 'isGiro',
                'clear' => ['bank_code', 'bank_id', 'bank_reff', 'bank_date']
            ],
            'TRF' => [
                'flag' => 'isTrf',
                'clear' => ['bank_code_transfer', 'bank_id_transfer', 'bank_reff_transfer', 'bank_date_transfer']
            ],
            'ADV'  => [
                'flag' => 'isAdv',
                'clear' => ['bank_code_advance', 'amt_advance']
            ]
        ];

        foreach ($paymentConfig as $type => $config) {
            if ($trType === $type) {
                $this->{$config['flag']} = "true";
            } else {
                $this->{$config['flag']} = "false";
                foreach ($config['clear'] as $field) {
                    $this->input_details[$key][$field] = null;
                }
            }
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
                'bank_id' => !empty($detail['bank_id_transfer']) ? $detail['bank_id_transfer'] : null,
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
        // Hapus item-item yang sudah ditandai dari database
        if (!empty($this->deletedItems)) {
            PaymentSrc::whereIn('id', $this->deletedItems)->forceDelete();
        }

        $this->dispatch('success', __('Data Payment berhasil disimpan.'));
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
