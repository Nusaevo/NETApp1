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
                    'bank_code_advance' => null,
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

                    // Sinkronisasi array input_details dengan input_payments
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
        // Normalisasi tipe pembayaran jika diperlukan:
        // Misalnya, jika user memilih 'TUNAI', kita anggap sama dengan 'CASH'
        foreach ($this->input_payments as $key => $payment) {
            if (isset($payment['tr_type']) && $payment['tr_type'] === 'TUNAI') {
                $this->input_payments[$key]['tr_type'] = 'CASH';
                $this->input_payments[$key]['pay_type_code'] = 'CASH';
            }
            // Pastikan key 'tr_type' ada, jika tidak, set ke string kosong
            if (!isset($this->input_payments[$key]['tr_type'])) {
                $this->input_payments[$key]['tr_type'] = '';
            }
        }

        // Definisikan field yang aktif untuk tiap tipe pembayaran
        $fieldsByType = [
            'CASH' => ['bank_code_tunai', 'amt_tunai'],
            'GIRO' => ['amt_giro', 'bank_reff_giro', 'bank_reff_no_giro', 'bank_date_giro'],
            'TRF'  => ['bank_code_transfer', 'bank_id_transfer', 'bank_reff_transfer', 'bank_date_transfer'],
            'ADV'  => ['bank_code_advance', 'amt_advance'],
        ];

        // Bersihkan field–field yang tidak aktif berdasarkan tipe yang sedang dipilih
        foreach ($this->input_payments as $key => $payment) {
            $currentType = $payment['tr_type'] ?? null;
            foreach ($fieldsByType as $type => $fields) {
                if ($currentType !== $type) {
                    foreach ($fields as $field) {
                        $this->input_payments[$key][$field] = null;
                    }
                }
            }
        }

        // Lakukan validasi input
        $this->validate();

        // dd($this->input_payments);
        // Simpan input_payments dari dialog box
        foreach ($this->input_payments as $key => $payment) {
            $tr_seq = intval($key) + 1; // Ensure $key is an integer

            switch ($payment['pay_type_code']) {
                case 'CASH':
                    $data = [
                        'tr_type'       => $this->object->tr_type,
                        'pay_type_id'   => $payment['pay_type_id'],
                        'pay_type_code' => $payment['pay_type_code'],
                        'bank_id'       => $payment['pay_type_id'],
                        'bank_code'     => $payment['pay_type_code'],
                        'amt'           => $payment['amt_tunai'] ?? 0, // gunakan default 0 atau validasi input
                        'bank_reff'     => '',
                    ];
                    break;

                case 'GIRO':
                    $data = [
                        'tr_type'       => $this->object->tr_type,
                        'pay_type_id'   => $payment['pay_type_id'],
                        'pay_type_code' => $payment['pay_type_code'],
                        'bank_id'       => $payment['pay_type_id'],
                        'bank_code'     => $payment['pay_type_code'],
                        'amt'           => $payment['amt_giro'],
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
                        'amt'           => $payment['amt_trf'] ?? null,
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
                        'amt'           => $payment['amt_advance'],
                        'bank_reff'     => '',
                    ];
                    break;

                default:
                    // Lewati jika tipe tidak dikenali
                    continue 2;
            }

            PaymentSrc::updateOrCreate(
                [
                    'trhdr_id' => $this->objectIdValue,
                    'tr_seq'   => $tr_seq,
                ],
                $data
            );
        }
        // dd($this->input_payments);
        // Hapus item-item yang sudah ditandai untuk dihapus
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
