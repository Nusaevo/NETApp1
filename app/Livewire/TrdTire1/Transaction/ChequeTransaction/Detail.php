<?php

namespace App\Livewire\TrdTire1\Transaction\ChequeTransaction;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\{PartnertrHdr, PartnertrDtl, PaymentSrc};
use App\Models\TrdTire1\Master\{Partner};
use App\Services\TrdTire1\Master\MasterService;
use App\Services\TrdTire1\PartnerTrxService;
use Illuminate\Support\Facades\{DB};
use Exception;

class Detail extends BaseComponent
{
    // Header properties
    public $inputs = [];
    public $chequeType = [];
    public $PaymentType = [];
    public $giroOptions = [];
    public $total_amount = 0;
    public $isPanelEnabled = "false";
    public $object;
    public $object_detail;
    public $partnerOptions = [];

    // Detail (item) properties
    public $input_details = [];
    public $deletedItems = [];

    // Delivery status property - simplified
    public $isDeliv = false;

    protected $masterService;
    protected $partnerTrxService;

    // Validation rules for header and details
    public $rules = [
        'inputs.tr_code' => 'required',
        'inputs.partner_code' => 'required',
        'inputs.tr_type' => 'required',
        'inputs.tr_date' => 'required',
        'input_details.*.bank_reff' => 'required', // Ubah dari bank_code ke bank_reff
        'input_details.*.amt' => 'required|numeric|min:0',
    ];

    // Event listeners
    protected $listeners = [
        'changeStatus' => 'changeStatus',
        'delete' => 'delete',
    ];

    // Livewire lifecycle hooks
    public function updated($propertyName)
    {
        if ($propertyName === 'input_details') {
            $this->checkDeliveryStatus();
        }
    }

    // Constructor untuk menginisialisasi services
    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue, $additionalParam);

        $this->partnerTrxService = app(PartnerTrxService::class);
    }

    protected function onPreRender()
    {
        $this->customValidationAttributes = [
            'inputs.tr_code' => $this->trans('tr_code'),
            'inputs.partner_code' => $this->trans('partner_code'),
            'inputs.tr_type' => $this->trans('tr_type'),
            'inputs.tr_date' => $this->trans('tr_date'),
            'input_details.*.bank_reff' => $this->trans('giro'), // Ubah dari bank_code ke bank_reff
            'input_details.*.amt' => $this->trans('amount'),
        ];

        $this->masterService = new MasterService();
        $this->chequeType = $this->masterService->getChequeType();
        $this->PaymentType = $this->masterService->getPaymentTypeData();
        $this->giroOptions = $this->getGiroOptions();
        $this->partnerOptions = Partner::where('grp', Partner::BANK)
            ->orderBy('name')
            ->get()
            ->map(function($partner) {
                return [
                    'label' => $partner->name,
                    'value' => $partner->name,
                ];
            })->toArray();

        if ($this->isEditOrView()) {
            $this->object = PartnertrHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = $this->object->toArray();
            $this->inputs['tr_code'] = $this->object->tr_code;
            $this->inputs['tr_date'] = $this->object->tr_date;
            $this->inputs['tr_type'] = $this->object->tr_type;
            $this->inputs['note'] = $this->object->note ?? '';
            $this->loadDetails();
        } else {
            $this->isPanelEnabled = "true";
        }
    }

    public function onReset()
    {
        $this->reset('inputs', 'input_details');
        $this->object = new PartnertrHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_date'] = date('Y-m-d');
        // $this->inputs['tr_type'] = 'CQDEP';
        $this->inputs['curr_id'] = 1;
        $this->inputs['curr_rate'] = 1.00;
        $this->isDeliv = false;
    }

    public function onValidateAndSave()
    {
        // dd($this->input_details, $this->inputs);
        if (!$this->partnerTrxService) {
            $this->partnerTrxService = app(PartnerTrxService::class);
        }

        // Jika sudah ada delivery, hanya boleh update header
        if ($this->isDeliv) {
            $headerData = $this->preparePartnerHeaderData();
            $detailData = []; // Kosongkan detail agar tidak diubah

            try {
                $result = $this->partnerTrxService->savePartnerTrx($headerData, $detailData);
                if (!$result) {
                    throw new Exception('Gagal mengubah Cheque Transaction.');
                }
                return $this->redirectToEdit();
            } catch (Exception $e) {
                $this->dispatch('error', $e->getMessage());
                throw new Exception('Gagal memperbarui Cheque Transaction: ' . $e->getMessage());
            }
        }

        // Jika belum ada delivery, proses normal
        if ($this->actionValue === 'Edit' && $this->object->isOrderCompleted()) {
            $this->dispatch('warning', 'Nota ini tidak bisa di-edit karena status sudah Completed');
            return;
        }

        $headerData = $this->preparePartnerHeaderData();
        $detailData = $this->preparePartnerDetailData();
        $totals = $this->calcTotalFromDetails($detailData);
        $headerData['amt'] = $totals['amt'];
        $headerData['amt_base'] = $totals['amt'];

        $result = $this->partnerTrxService->savePartnerTrx($headerData, $detailData);

        $this->object = $result['header'];

        // Simpan data PaymentSrc
        $this->savePaymentSrcData();

        $this->redirectToEdit();
    }

    private function preparePartnerHeaderData()
    {
        $headerData = [
            'tr_date' => $this->inputs['tr_date'] ?? date('Y-m-d'),
            'tr_type' => $this->inputs['tr_type'],
            'tr_code' => $this->inputs['tr_code'] ?? '',
            'reff_code' => $this->inputs['reff_code'] ?? '',
            'curr_id' => $this->inputs['curr_id'] ?? 1,
            'curr_rate' => $this->inputs['curr_rate'] ?? 1.00,
            'amt' => 0, // akan diisi dari detail
            'amt_base' => 0, // akan diisi dari detail
        ];

        if ($this->actionValue === 'Create') {
            // Untuk create, tidak perlu set id
        } else {
            // Untuk edit, set id dari object yang ada
            $headerData['id'] = $this->object->id ?? null;
        }

        return $headerData;
    }

    private function preparePartnerDetailData()
    {
        $detailData = [];

        foreach ($this->input_details as $key => $detail) {
            // Ambil partner dari dropdown
            $partnerCode = $this->inputs['partner_code'] ?? '';
            $partner = Partner::where('name', $partnerCode)->first();

            // Ambil data giro dari PaymentSrc berdasarkan bank_reff yang dipilih
            $giroData = PaymentSrc::where('bank_reff', $detail['bank_reff'])
                ->where('amt', '>', 0)
                ->first();

            if ($giroData && $partner) {
                // Detail 1: Debit dari PaymentSrc (tr_seq negatif)
                $detailData[] = [
                    'trhdr_id' => $this->object->id ?? null,
                    'tr_type' => $this->inputs['tr_type'],
                    'tr_code' => $this->inputs['tr_code'] ?? '',
                    'tr_seq' => -($key + 1), // Negative sequence untuk debit
                    'partnerbal_id' => $giroData->partnerbal_id ?? null,
                    'partner_id' => $giroData->bank_id ?? null, // ID dari PaymentSrc
                    'partner_code' => $giroData->bank_code ?? '', // Gunakan bank_code dari PaymentSrc
                    'reff_id' => $giroData->id, // ID dari payment_srcs
                    'reff_type' => $giroData->reff_type ?? '',
                    'reff_code' => $giroData->reff_code ?? '',
                    'amt' => -$detail['amt'], // Negative karena debit
                    'tr_descr' => $giroData->bank_reff,
                ];

                // Detail 2: Kredit ke rekening bank yang dipilih (tr_seq positif)
                $detailData[] = [
                    'trhdr_id' => $this->object->id ?? null,
                    'tr_type' => $this->inputs['tr_type'],
                    'tr_code' => $this->inputs['tr_code'] ?? '',
                    'tr_seq' => $key + 1, // Positive sequence untuk kredit
                    'partnerbal_id' => null, // Akan diisi oleh service
                    'partner_id' => $partner->id ?? null,
                    'partner_code' => $partner->name ?? '',
                    'reff_id' => $giroData->id, // ID dari payment_srcs
                    'reff_type' => $giroData->reff_type ?? '',
                    'reff_code' => $giroData->reff_code ?? '',
                    'amt' => $detail['amt'], // Positive karena kredit
                    'tr_descr' => 'Setor ' . $giroData->bank_reff,
                ];
            }
        }

        return $detailData;
    }

    public function addItemOnClick()
    {
        try {
            // Check if can add new item
            if ($this->isDeliv) {
                $this->dispatch('error', 'Tidak dapat menambah item baru karena ada item yang sudah terkirim.');
                return;
            }

            // Gunakan struktur PartnertrDtl bukan PaymentSrc
            $this->input_details[] = [
                'id' => null,
                'trhdr_id' => null,
                'tr_type' => $this->inputs['tr_type'] ?? '',
                'tr_code' => $this->inputs['tr_code'] ?? '',
                'tr_seq' => 0,
                'partnerbal_id' => null,
                'partner_id' => null,
                'partner_code' => '',
                'reff_id' => null,
                'reff_type' => '',
                'reff_code' => '',
                'amt' => 0,
                'tr_descr' => '',
                // Tambahan field untuk referensi giro
                'bank_duedt' => date('Y-m-d'),
            ];

        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
        }
    }

    public function amtOnChanged($key)
    {
        $this->calcItemAmount($key);
    }

    public function giroOnChanged($key, $value)
    {
        // Handle perubahan giro dan set amount berdasarkan giro yang dipilih
        if (isset($this->input_details[$key])) {
            $this->input_details[$key]['bank_reff'] = $value; // Set bank_reff untuk referensi

            // Ambil data giro dari PaymentSrc
            $giroData = PaymentSrc::where('bank_reff', $value)
                ->where('amt', '>', 0)
                ->first();

            if ($giroData) {
                // Set data dari PaymentSrc ke input_details
                $this->input_details[$key]['partner_id'] = $giroData->bank_id ?? null;
                $this->input_details[$key]['partner_code'] = $giroData->bank_code ?? '';
                $this->input_details[$key]['reff_id'] = $giroData->id; // ID dari PaymentSrc asal
                $this->input_details[$key]['reff_type'] = $giroData->tr_type ?? ''; // tr_type dari PaymentSrc asal
                $this->input_details[$key]['reff_code'] = $giroData->tr_code ?? ''; // tr_code dari PaymentSrc asal
                $this->input_details[$key]['amt'] = $giroData->amt;
                $this->input_details[$key]['tr_descr'] = $giroData->bank_reff;
            }

            // Recalculate total
            $this->calcItemAmount($key);
        }
    }

    private function getGiroOptions()
    {
        // Ambil semua data dari PaymentSrc yang memiliki amount > 0
        $giros = PaymentSrc::where('amt', '>', 0)
            ->select('bank_code', 'bank_reff', 'amt')
            ->get()
            ->map(function($giro) {
                return [
                    'label' => $giro->bank_reff . ' (Rp ' . number_format($giro->amt, 0, ',', '.') . ')',
                    'value' => $giro->bank_reff, // Gunakan bank_reff sebagai value
                    'amount' => $giro->amt,
                    'bank_code' => $giro->bank_code // Simpan bank_code untuk referensi
                ];
            })
            ->toArray();

        return $giros;
    }

    public function calcItemAmount($key)
    {
        // Untuk cheque transaction, amount diinput langsung
        if (isset($this->input_details[$key]['amt'])) {
            $this->total_amount = 0;
            foreach ($this->input_details as $detail) {
                $this->total_amount += $detail['amt'] ?? 0;
            }
            // Format as Rupiah
            $this->total_amount = rupiah($this->total_amount);
        }
    }

    public function deleteItem($index)
    {
        try {
            if (!isset($this->input_details[$index])) {
                throw new Exception(__('generic.error.delete_item', ['message' => 'Item tidak ditemukan.']));
            }
            // Check if item is editable
            if ($this->isDeliv) {
                $this->dispatch('error', 'Tidak dapat menghapus item karena sudah memiliki delivery.');
                return;
            }
            // Track deleted items with IDs
            if (isset($this->input_details[$index]['id'])) {
                $this->deletedItems[] = $this->input_details[$index]['id'];
            }

            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = PaymentSrc::where('trhdr_id', $this->object->id)
                ->where('tr_type', $this->object->tr_type)
                ->orderBy('tr_seq')
                ->get();

            $this->input_details = $this->object_detail->toArray();
            foreach ($this->input_details as $key => &$detail) {
                if (!isset($detail['amt'])) $detail['amt'] = 0;
                $this->calcItemAmount($key);
            }
            unset($detail);

            // Check delivery status after loading details
            $this->checkDeliveryStatus();
        }
    }

    private function calcTotalFromDetails($detailData)
    {
        $amt = 0;

        foreach ($detailData as $detail) {
            $amt += $detail['amt'] ?? 0;
        }

        return [
            'amt' => $amt
        ];
    }

    private function redirectToEdit()
    {
        $objectId = $this->actionValue === 'Create' ? $this->object->id : $this->object->id;

        return redirect()->route(
            $this->appCode . '.Transaction.ChequeTransaction.Detail',
            [
                'action'   => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($objectId),
            ]
        );
    }

    public function deleteTransaction()
    {
        try {
            // 1) Pastikan object ada dan memang tercatat di DB
            if (!$this->object || is_null($this->object->id) ||
                !PartnertrHdr::where('id', $this->object->id)->exists()) {
                throw new \Exception(__('Data header tidak ditemukan'));
            }

            // 3) Pastikan PartnerTrxService sudah diinisialisasi
            if (!$this->partnerTrxService) {
                $this->partnerTrxService = app(PartnerTrxService::class);
            }

            // 4) Gunakan PartnerTrxService untuk menghapus transaction
            $this->partnerTrxService->delPartnerTrx($this->object->id);

            $this->dispatch('success', __('Data berhasil terhapus'));
            return redirect()->route(str_replace('.Detail', '', $this->baseRoute));

        } catch (\Exception $e) {
            $this->dispatch('error', __('generic.error.delete', [
                'message' => $e->getMessage()
            ]));
        }
    }

    public function checkDeliveryStatus()
    {
        $this->isDeliv = false; // Default: field aktif (bisa diedit)
        // Untuk cheque transaction, belum ada implementasi delivery status
        // Bisa ditambahkan sesuai kebutuhan bisnis
    }

    private function savePaymentSrcData()
    {
        if (empty($this->object) || empty($this->object->id)) {
            return;
        }

        // Simpan data PaymentSrc berdasarkan input_details
        foreach ($this->input_details as $key => $detail) {
            $tr_seq = intval($key) + 1;

            $data = [
                'trhdr_id' => $this->object->id,
                'tr_type' => $this->object->tr_type,
                'tr_code' => $this->object->tr_code,
                'tr_seq' => $tr_seq,
                'pay_type_id' => null,
                'pay_type_code' => 'GIRO', // Default untuk giro
                'bank_id' => $detail['partner_id'] ?? null,
                'bank_code' => $detail['partner_code'] ?? '',
                'bank_reff' => $detail['bank_reff'] ?? '',
                'bank_duedt' => $detail['bank_duedt'] ?? date('Y-m-d'),
                'bank_note' => '',
                'amt' => $detail['amt'] ?? 0,
                'amt_base' => $detail['amt'] ?? 0,
                // Tambahkan field referensi
                'reff_id' => $detail['reff_id'], // ID dari PaymentSrc asal
                'reff_type' => $detail['reff_type'], // tr_type dari PaymentSrc asal atau current tr_type
                'reff_code' => $detail['reff_code'], // tr_code dari PaymentSrc asal atau current tr_code
            ];

            PaymentSrc::updateOrCreate(
                ['trhdr_id' => $this->object->id, 'tr_seq' => $tr_seq],
                $data
            );
        }

        // Hapus item yang dihapus
        if (!empty($this->deletedItems)) {
            PaymentSrc::whereIn('id', $this->deletedItems)->delete();
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
