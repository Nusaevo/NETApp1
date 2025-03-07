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
                $this->input_details[$key] = populateArrayFromModel($detail);
                $this->input_details[$key]['tr_type']      = $detail->pay_type_code;
                $this->input_details[$key]['pay_type_code'] = $detail->pay_type_code;
                $this->input_details[$key]['pay_type_id']  = $detail->pay_type_id;

                // Tampilkan data sesuai dengan tipe pembayaran yang tersimpan
                switch ($detail->pay_type_code) {
                    case 'CASH': // Jika tipe tunai tersimpan sebagai CASH atau TUNAI
                    case 'TUNAI':
                        $this->input_details[$key]['amt_tunai'] = $detail->amt;
                        // Jika ada field lain untuk tunai, misalnya bank_code_tunai, isi di sini
                        break;

                    case 'GIRO':
                        $this->input_details[$key]['amt_giro'] = $detail->amt;
                        if (!empty($detail->bank_reff)) {
                            $split = explode(' - ', $detail->bank_reff);
                            $this->input_details[$key]['bank_reff_giro'] = $split[0] ?? '';
                            $this->input_details[$key]['bank_reff_no_giro'] = $split[1] ?? '';
                        } else {
                            $this->input_details[$key]['bank_reff_giro'] = '';
                            $this->input_details[$key]['bank_reff_no_giro'] = '';
                        }
                        break;

                    case 'TRF':
                        // Sesuaikan field untuk tipe Transfer (TRF)
                        $this->input_details[$key]['amt_trf'] = $detail->amt;
                        if (!empty($detail->bank_reff)) {
                            $split = explode(' - ', $detail->bank_reff);
                            $this->input_details[$key]['bank_reff_transfer'] = $split[0] ?? '';
                            $this->input_details[$key]['bank_reff_no_transfer'] = $split[1] ?? '';
                        } else {
                            $this->input_details[$key]['bank_reff_transfer'] = '';
                            $this->input_details[$key]['bank_reff_no_transfer'] = '';
                        }
                        break;

                    case 'ADV':
                        // Sesuaikan field untuk tipe Advance (ADV)
                        $this->input_details[$key]['amt_advance'] = $detail->amt;
                        // Misal, bank_code_advance, dll.
                        break;

                    default:
                        // Jika tipe tidak dikenali, biarkan field–fieldnya default atau kosong
                        break;
                }
            }
        }
    }


    public function addItem()
    {
        if (!empty($this->objectIdValue)) {
            try {
                $this->input_details[] = [
                    'tr_type' => null,
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
        $newType = $this->input_details[$key]['tr_type'] ?? null;
        $oldType = $this->input_details[$key]['current_type'] ?? null;

        // Konfigurasi tiap tr_type dengan field, nama flag, dan key backup-nya
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

        // Lakukan backup data untuk tipe lama jika tr_type berubah
        foreach ($paymentTypes as $type => $config) {
            if ($oldType === $type && $newType !== $type) {
                $backup = [];
                foreach ($config['fields'] as $field) {
                    $backup[$field] = $this->input_details[$key][$field] ?? null;
                }
                $this->input_details[$key][$config['backupKey']] = $backup;
            }
        }

        // Set flag dan restore data jika perlu, atau clear field jika bukan tipe yang aktif
        foreach ($paymentTypes as $type => $config) {
            if ($newType === $type) {
                $this->{$config['flag']} = "true";
                if (isset($this->input_details[$key][$config['backupKey']])) {
                    foreach ($config['fields'] as $field) {
                        $this->input_details[$key][$field] = $this->input_details[$key][$config['backupKey']][$field]
                            ?? $this->input_details[$key][$field];
                    }
                }
            } else {
                $this->{$config['flag']} = "false";
                foreach ($config['fields'] as $field) {
                    $this->input_details[$key][$field] = null;
                }
            }
        }

        // Perbarui current_type dengan tipe baru yang dipilih
        $this->input_details[$key]['current_type'] = $newType;
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
        // Normalisasi tipe pembayaran jika diperlukan:
        // Misalnya, jika user memilih 'TUNAI', kita anggap sama dengan 'CASH'
        foreach ($this->input_details as $key => $detail) {
            if (isset($detail['tr_type']) && $detail['tr_type'] === 'TUNAI') {
                $this->input_details[$key]['tr_type'] = 'CASH';
                $this->input_details[$key]['pay_type_code'] = 'CASH';
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
        foreach ($this->input_details as $key => $detail) {
            $currentType = $detail['tr_type'] ?? null;
            foreach ($fieldsByType as $type => $fields) {
                if ($currentType !== $type) {
                    foreach ($fields as $field) {
                        $this->input_details[$key][$field] = null;
                    }
                }
            }
        }

        // Lakukan validasi input
        $this->validate();

        // Simpan tiap input_detail ke database sesuai tipe pembayaran aktif
        foreach ($this->input_details as $key => $detail) {
            $tr_seq = $key + 1;

            switch ($detail['tr_type']) {
                case 'CASH':
                    $data = [
                        'tr_type'       => $this->object->tr_type,
                        'pay_type_id'   => $detail['pay_type_id'],
                        'pay_type_code' => $detail['pay_type_code'],
                        'bank_id'       => $detail['pay_type_id'],
                        'bank_code'     => $detail['pay_type_code'],
                        'amt'           => $detail['amt_tunai'],
                        'bank_reff'     => '',
                    ];
                    break;

                case 'GIRO':
                    $data = [
                        'tr_type'       => $this->object->tr_type,
                        'pay_type_id'   => $detail['pay_type_id'],
                        'pay_type_code' => $detail['pay_type_code'],
                        'bank_id'       => $detail['pay_type_id'],
                        'bank_code'     => $detail['pay_type_code'],
                        'amt'           => $detail['amt_giro'],
                        'bank_reff'     => trim($detail['bank_reff_giro'] . ' - ' . $detail['bank_reff_no_giro']),
                        // 'bank_date'     => $detail['bank_date_giro'],
                    ];
                    break;

                case 'TRF':
                    $data = [
                        'tr_type'       => $this->object->tr_type,
                        'pay_type_id'   => $detail['pay_type_id'],
                        'pay_type_code' => $detail['pay_type_code'],
                        'bank_id'       => $detail['pay_type_id'],
                        'bank_code'     => $detail['pay_type_code'],
                        'amt'           => $detail['amt_trf'] ?? null,
                        'bank_reff'     => trim($detail['bank_reff_transfer'] . ' - ' . $detail['bank_reff_no_transfer']),
                        // 'bank_date'     => $detail['bank_date_transfer'],
                    ];
                    break;

                case 'ADV':
                    $data = [
                        'tr_type'       => $this->object->tr_type,
                        'pay_type_id'   => $detail['pay_type_id'],
                        'pay_type_code' => $detail['pay_type_code'],
                        'bank_id'       => $detail['pay_type_id'],
                        'bank_code'     => $detail['pay_type_code'],
                        'amt'           => $detail['amt_advance'],
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
