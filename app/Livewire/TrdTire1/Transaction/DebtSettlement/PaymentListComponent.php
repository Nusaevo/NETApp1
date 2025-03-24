<?php

namespace App\Livewire\TrdTire1\Transaction\DebtSettlement;

use App\Livewire\Component\DetailComponent;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdTire1\Master\Material;
use App\Services\TrdTire1\Master\MasterService;
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl, PaymentDtl, PaymentHdr, PaymentSrc};
use App\Models\TrdTire1\Master\MatlUom; // Add this import
use App\Models\TrdTire1\Master\Partner;
use Exception;

class PaymentListComponent extends DetailComponent
{
    public $materials;
    protected $masterService;
    public $PaymentType = [];
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
    public $input_payments = [];

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
        // $this->object = new PaymentSrc();
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
            if (!empty($this->objectIdValue)) {
                $this->object = PaymentHdr::withTrashed()->find($this->objectIdValue);
                if (!$this->object) {
                    $this->dispatch('error', 'Object not found');
                    return;
                }
                $this->inputs = populateArrayFromModel($this->object);
                $this->loadDetails();
            }
        }
    }


    public function addItem()
    {
        if (!empty($this->objectIdValue)) {
            try {
                $newItem = [
                    'tr_type' => '',
                    'pay_type_code' => '',
                    'pay_type_id' => null,
                    'amt_tunai' => null,
                    'amt_giro' => null,
                    'bank_reff_giro' => null,
                    'bank_reff_no_giro' => null,
                    'bank_date_giro' => null,
                    'amt_trf' => null,
                    'bank_reff_transfer' => null,
                    'bank_reff_no_transfer' => null,
                    'bank_date_transfer' => null,
                    'amt_advance' => null,
                    'bank_note' => null,
                ];
                $this->input_details[] = $newItem;
                $this->input_payments[] = $newItem;

                $newKey = array_key_last($this->input_payments);
                $this->activePaymentItemKey = $newKey;
                $this->dispatch('openPaymentDialogBox');
            } catch (Exception $e) {
                $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
            }
        } else {
            $this->dispatch('error', __('generic.error.save', ['message' => 'Tolong save Header terlebih dahulu']));
        }
    }

    public function confirmPayment()
    {
        if ($this->activePaymentItemKey === null) {
            $this->dispatch('error', __('No active item selected.'));
            return;
        }
        $key = $this->activePaymentItemKey;
        $this->validate([
            "input_payments.$key.pay_type_code" => 'required',
        ]);
        $payTypeCode = $this->input_payments[$key]['pay_type_code'] ?? '';

        // Set pay_type_id based on pay_type_code
        $payType = ConfigConst::where('str1', $payTypeCode)->first();
        $this->input_payments[$key]['pay_type_id'] = $payType ? $payType->id : null;
        $this->input_payments[$key]['pay_type_code'] = $payTypeCode;

        // Update input_details with payment details
        $this->input_details[$key] = $this->input_payments[$key];

        // Update the table fields directly
        $this->input_details[$key]['amt'] = $this->input_payments[$key]['amt_tunai'] ?? $this->input_payments[$key]['amt_giro'] ?? $this->input_payments[$key]['amt_trf'] ?? $this->input_payments[$key]['amt_advance'] ?? 0;

        // Handle bank_reff concatenation
        $bankReffGiro = trim(($this->input_payments[$key]['bank_reff_giro'] ?? '') . ' - ' . ($this->input_payments[$key]['bank_reff_no_giro'] ?? ''));
        $bankReffTransfer = trim(($this->input_payments[$key]['bank_reff_transfer'] ?? '') . ' - ' . ($this->input_payments[$key]['bank_reff_no_transfer'] ?? ''));
        $bankNote = $this->input_payments[$key]['bank_note'] ?? '';
        $this->input_details[$key]['bank_reff'] = $bankReffGiro !== ' - ' ? $bankReffGiro : ($bankReffTransfer !== ' - ' ? $bankReffTransfer : ($bankNote !== '' ? $bankNote : ''));

        // Ensure bank_reff is set to bank_note for CASH and ADV
        if ($payTypeCode === 'CASH' || $payTypeCode === 'ADV') {
            $this->input_details[$key]['bank_reff'] = $bankNote;
        }

        $this->dispatch('success', __('Payment type has been confirmed and updated for item ' . ($key + 1)));
        $this->dispatch('closePaymentDialogBox');
        $this->activePaymentItemKey = null;
    }

    public function openPaymentDialog($key)
    {
        if (!isset($this->input_payments[$key])) {
            $this->dispatch('error', 'Data payment tidak ditemukan.');
            return;
        }

        $this->activePaymentItemKey = $key;
        if (!empty($this->input_payments[$key]['pay_type_code'])) {
            $this->onPaymentTypeChange();
        } else {
            $this->isCash = $this->isGiro = $this->isTrf = $this->isAdv = "false";
        }

        $this->dispatch('openPaymentDialogBox');
    }

    public function deleteItem($index)
    {
        try {
            if (!isset($this->input_payments[$index])) {
                throw new Exception(__('generic.error.delete_item', ['message' => 'Item not found.']));
            }

            if (!empty($this->objectIdValue) && isset($this->input_payments[$index]['id'])) {
                $this->deletedItems[] = $this->input_payments[$index]['id'];
            }

            unset($this->input_details[$index]);
            unset($this->input_payments[$index]);
            $this->input_details = array_values($this->input_details);
            $this->input_payments = array_values($this->input_payments);

            $this->dispatch('success', __('generic.string.delete_item'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    public function onPaymentTypeChange()
    {
        $key = $this->activePaymentItemKey;
        $newType = $this->input_payments[$key]['pay_type_code'] ?? null;
        $oldType = $this->input_payments[$key]['current_type'] ?? null;
        $this->input_payments[$key]['tr_type'] = $newType;

        // Konfigurasi tiap pay_type_code dengan field, nama flag, dan key backup-nya
        $paymentTypes = [
            'CASH' => [
                'fields'    => ['bank_code_tunai', 'amt_tunai'],
                'flag'      => 'isCash',
                'backupKey' => 'cash_backup'
            ],
            'GIRO' => [
                'fields'    => ['amt_giro', 'bank_reff_giro', 'bank_reff_no_giro', 'bank_date_giro'],
                'flag'      => 'isGiro',
                'backupKey' => 'giro_backup'
            ],
            'TRF' => [
                'fields'    => ['bank_code_transfer', 'bank_id_transfer', 'bank_reff_transfer', 'bank_date_transfer'],
                'flag'      => 'isTrf',
                'backupKey' => 'trf_backup'
            ],
            'ADV' => [
                'fields'    => ['bank_code_advance', 'amt_advance'],
                'flag'      => 'isAdv',
                'backupKey' => 'adv_backup'
            ]
        ];

        // Lakukan backup data untuk tipe lama jika pay_type_code berubah
        foreach ($paymentTypes as $type => $config) {
            if ($oldType === $type && $newType !== $type) {
                $backup = [];
                foreach ($config['fields'] as $field) {
                    $backup[$field] = $this->input_payments[$key][$field] ?? null;
                }
                $this->input_payments[$key][$config['backupKey']] = $backup;
            }
        }

        // Set flag dan restore data jika perlu, atau clear field jika bukan tipe yang aktif
        foreach ($paymentTypes as $type => $config) {
            if ($newType === $type) {
                $this->{$config['flag']} = "true";
                if (isset($this->input_payments[$key][$config['backupKey']])) {
                    foreach ($config['fields'] as $field) {
                        $this->input_payments[$key][$field] = $this->input_payments[$key][$config['backupKey']][$field]
                            ?? $this->input_payments[$key][$field];
                    }
                }
            } else {
                $this->{$config['flag']} = "false";
                foreach ($config['fields'] as $field) {
                    $this->input_payments[$key][$field] = null;
                }
            }
        }

        // Perbarui current_type dengan tipe baru yang dipilih
        $this->input_payments[$key]['current_type'] = $newType;
    }

    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = PaymentSrc::where('trhdr_id', $this->object->id)
                ->where('tr_type', $this->object->tr_type)
                ->get();

            if (!empty($this->object_detail)) {
                foreach ($this->object_detail as $key => $detail) {
                    // Memastikan bahwa populateArrayFromModel mengembalikan array yang lengkap
                    $this->input_payments[$key] = populateArrayFromModel($detail);

                    // Jika key 'tr_type' tidak ada, maka tambahkan dari field 'pay_type_code'
                    if (!isset($this->input_payments[$key]['tr_type'])) {
                        $this->input_payments[$key]['tr_type'] = $detail->pay_type_code;
                    }

                    // Set nilai lain yang diperlukan
                    $this->input_payments[$key]['pay_type_code'] = $detail->pay_type_code;
                    $this->input_payments[$key]['pay_type_id'] = $detail->pay_type_id;

                    // Pisahkan bank_reff berdasarkan jenis pembayaran dan set amt dan bank_date
                    switch ($detail->pay_type_code) {
                        case 'TRF':
                            $parts = explode(' - ', $detail->bank_reff);
                            $this->input_payments[$key]['bank_reff_transfer'] = $parts[0] ?? '';
                            $this->input_payments[$key]['bank_reff_no_transfer'] = $parts[1] ?? '';
                            $this->input_payments[$key]['amt_trf'] = $detail->amt;
                            $this->input_payments[$key]['bank_date_transfer'] = $detail->bank_date;
                            break;
                        case 'GIRO':
                            $parts = explode(' - ', $detail->bank_reff);
                            $this->input_payments[$key]['bank_reff_giro'] = $parts[0] ?? '';
                            $this->input_payments[$key]['bank_reff_no_giro'] = $parts[1] ?? '';
                            $this->input_payments[$key]['amt_giro'] = $detail->amt;
                            $this->input_payments[$key]['bank_date_giro'] = $detail->bank_date;
                            break;
                        case 'CASH':
                            $this->input_payments[$key]['amt_tunai'] = $detail->amt;
                            break;
                        case 'ADV':
                            $this->input_payments[$key]['amt_advance'] = $detail->amt;
                            break;
                    }
                    $this->input_details[$key] = $this->input_payments[$key];
                }
            } else {
                // Jika tidak ada detail, inisialisasi array kosong atau nilai default
                $this->input_payments = [];
                $this->input_details = [];
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
        // Normalisasi tipe pembayaran
        foreach ($this->input_payments as $key => $payment) {
            if (isset($payment['tr_type']) && $payment['tr_type'] === 'TUNAI') {
                $this->input_payments[$key]['tr_type'] = 'CASH';
                $this->input_payments[$key]['pay_type_code'] = 'CASH';
            }
        }

        // Bersihkan field yang tidak aktif
        $fieldsByType = [
            'CASH' => ['bank_code_tunai', 'amt_tunai'],
            'GIRO' => ['amt_giro', 'bank_reff_giro', 'bank_reff_no_giro', 'bank_date_giro'],
            'TRF'  => ['bank_code_transfer', 'bank_id_transfer', 'bank_reff_transfer', 'bank_reff_no_transfer', 'bank_date_transfer'],
            'ADV'  => ['bank_code_advance', 'amt_advance'],
        ];

        foreach ($this->input_payments as $key => $payment) {
            $currentType = $payment['pay_type_code'] ?? null;
            foreach ($fieldsByType as $type => $fields) {
                if ($currentType !== $type) {
                    foreach ($fields as $field) {
                        $this->input_payments[$key][$field] = null;
                    }
                }
            }
        }

        // Validasi input
        $this->validate();

        // Simpan data
        foreach ($this->input_payments as $key => $payment) {
            $tr_seq = intval($key) + 1;

            switch ($payment['pay_type_code']) {
                    case 'CASH':
                        $data = [
                            'tr_type'       => $this->object->tr_type,
                            'pay_type_id'   => $payment['pay_type_id'],
                            'pay_type_code' => $payment['pay_type_code'],
                            'bank_id'       => $payment['pay_type_id'],
                            'bank_code'     => $payment['pay_type_code'],
                            'bank_note'     => $payment['bank_note'],
                            'amt'           => $payment['amt_tunai'] ?? 0, // gunakan default 0 atau validasi input
                            'bank_reff'     => '',
                            'bank_date'     => '1900-01-01',
                        ];
                        break;

                    case 'GIRO':
                        $data = [
                            'tr_type'       => $this->object->tr_type,
                            'pay_type_id'   => $payment['pay_type_id'],
                            'pay_type_code' => $payment['pay_type_code'],
                            'bank_id'       => $payment['pay_type_id'],
                            'bank_code'     => $payment['pay_type_code'],
                            'bank_date'     => $payment['bank_date_giro'],
                            'amt'           => $payment['amt_giro'] ?? 0, // gunakan default 0 atau validasi input
                            'bank_reff'     => trim($payment['bank_reff_giro'] . ' - ' . $payment['bank_reff_no_giro']),
                        ];
                        break;

                    case 'TRF':
                        $data = [
                            'tr_type'       => $this->object->tr_type,
                            'pay_type_id'   => $payment['pay_type_id'],
                            'pay_type_code' => $payment['pay_type_code'],
                            'bank_id'       => $payment['pay_type_id'],
                            'bank_code'     => $payment['pay_type_code'],
                            'bank_date'     => $payment['bank_date_transfer'],
                            'amt'           => $payment['amt_trf'] ?? 0, // gunakan default 0 atau validasi input
                            'bank_reff'     => trim($payment['bank_reff_transfer'] . ' - ' . $payment['bank_reff_no_transfer']),
                        ];
                        break;

                    case 'ADV':
                        $data = [
                            'tr_type'       => $this->object->tr_type,
                            'pay_type_id'   => $payment['pay_type_id'],
                            'pay_type_code' => $payment['pay_type_code'],
                            'bank_id'       => $payment['pay_type_id'],
                            'bank_code'     => $payment['pay_type_code'],
                            'bank_note'     => $payment['bank_note'],
                            'amt'           => $payment['amt_advance'] ?? 0, // gunakan default 0 atau validasi input
                            'bank_reff'     => '',
                            'bank_date'     => $payment['bank_date'] ?? '1900-01-01',
                        ];
                        break;
                }

            PaymentSrc::updateOrCreate(
                ['trhdr_id' => $this->objectIdValue, 'tr_seq' => $tr_seq],
                $data
            );
        }

        // Hapus item yang dihapus
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
