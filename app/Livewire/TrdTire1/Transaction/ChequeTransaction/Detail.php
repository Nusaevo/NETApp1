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
        'input_details.*.bank_code' => 'required',
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
        // Pastikan services sudah diinisialisasi
        // $this->initializeServices();

        $this->customValidationAttributes = [
            'inputs.tr_code' => $this->trans('tr_code'),
            'inputs.partner_code' => $this->trans('partner_code'),
            'inputs.tr_type' => $this->trans('tr_type'),
            'inputs.tr_date' => $this->trans('tr_date'),
            'input_details.*.bank_code' => $this->trans('giro'),
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
            // dd($this->objectIdValue);
            $this->object = PartnertrHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = $this->object->toArray();
            $this->inputs['tr_code'] = $this->object->tr_code;
            $this->inputs['tr_date'] = $this->object->tr_date;
            $this->inputs['tr_type'] = $this->object->tr_type;
            $this->inputs['note'] = $this->object->note ?? '';
            // dd($this->inputs);
            $this->loadDetails();
            // dd($this->input_details);
        } else {
            $this->isPanelEnabled = "true";
        }

        // dd($this->input_details);
    }

    public function onReset()
    {
        $this->reset('inputs', 'input_details');
        $this->object = new PartnertrHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_date'] = date('Y-m-d');
        $this->inputs['tr_type'] = 'CQDEP';
        $this->inputs['curr_id'] = 1;
        $this->inputs['curr_rate'] = 1.00;
        $this->isDeliv = false;
    }

     public function onValidateAndSave()
    {
        // dd($this->input_details,$this->inputs);
        // throw new Exception('Gagal menyimpan detail pesanan. Periksa data yang diberikan.');
        if (!$this->partnerTrxService) {
            $this->partnerTrxService = app(PartnerTrxService::class);
        }

        // Jika sudah ada delivery, hanya boleh update header
        if ($this->isDeliv) {
            // Prepare data header saja
            $headerData = $this->preparePartnerHeaderData();
            $detailData = []; // Kosongkan detail agar tidak diubah

            // Simpan hanya header (tanpa update detail)
            try {
                $result = $this->partnerTrxService->savePartnerTrx($headerData, $detailData);
                if (!$result) {
                    throw new Exception('Gagal mengubah Cheque Transaction.');
                }
                // $this->dispatch('success', 'Header berhasil diperbarui. Detail tidak diubah karena sudah ada delivery.');
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
            'tr_type' => $this->inputs['tr_type'] ?? 'CQDEP',
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

            $detailItem = [
                'trhdr_id' => $this->object->id ?? null,
                'tr_type' => $this->inputs['tr_type'] ?? 'CQDEP',
                'tr_code' => $this->inputs['tr_code'] ?? '',
                'tr_seq' => $key + 1,
                'pay_type_code' => 'GIRO', // Default untuk giro
                'bank_code' => $detail['bank_code'] ?? '',
                'amt' => $detail['amt'] ?? 0,
                'partnerbal_id' => null, // akan diisi oleh service
                'amt_base' => $detail['amt'] ?? 0,
            ];

            // Jika ada ID (untuk edit), set ID
            if (isset($detail['id']) && !empty($detail['id'])) {
                $detailItem['id'] = $detail['id'];
            }

            $detailData[] = $detailItem;
        }

        return $detailData;
    }

   public function addItemOnClick()
    {
        // Validasi: partner_code harus dipilih dulu
        // if (empty($this->inputs['partner_code'])) {
        //     $this->dispatch('error', 'Silakan pilih Rekening Bank terlebih dahulu.');
        //     return;
        // }

        try {
            // Check if can add new item
            if ($this->isDeliv) {
                $this->dispatch('error', 'Tidak dapat menambah item baru karena ada item yang sudah terkirim.');
                return;
            }
            $this->input_details[] = populateArrayFromModel(new PaymentSrc());
            $key = count($this->input_details) - 1;
            $this->input_details[$key]['amt'] = 0;
            $this->input_details[$key]['bank_code'] = '';
            $this->input_details[$key]['pay_type_code'] = 'GIRO'; // Default untuk giro
            $this->input_details[$key]['bank_reff'] = '';
            $this->input_details[$key]['bank_duedt'] = date('Y-m-d');
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
            $this->input_details[$key]['bank_code'] = $value;

            // Set amount berdasarkan giro yang dipilih
            // Anda bisa menyesuaikan logika ini sesuai dengan data giro yang tersedia
            $amount = $this->getAmountByGiro($value);
            $this->input_details[$key]['amt'] = $amount;

            // Recalculate total
            $this->calcItemAmount($key);
        }
    }

    private function getGiroOptions()
    {
        // Ambil data giro dari PaymentSrc atau sumber data lainnya
        // Contoh: ambil giro yang belum digunakan atau giro yang tersedia
        $giros = PaymentSrc::where('pay_type_code', 'GIRO')
            ->where('amt', '>', 0)
            ->select('bank_code', 'bank_reff', 'amt')
            ->get()
            ->map(function($giro) {
                return [
                    'label' => $giro->bank_code . ' - ' . $giro->bank_reff . ' (Rp ' . number_format($giro->amt, 0, ',', '.') . ')',
                    'value' => $giro->bank_code,
                    'amount' => $giro->amt
                ];
            })->toArray();

        // Jika tidak ada data dari database, gunakan data dummy untuk testing
        // if (empty($giros)) {
        //     $giros = [
        //         [
        //             'label' => 'BCA - Giro 001 (Rp 5.000.000)',
        //             'value' => 'BCA001',
        //             'amount' => 5000000
        //         ],
        //         [
        //             'label' => 'Mandiri - Giro 002 (Rp 3.500.000)',
        //             'value' => 'MDR002',
        //             'amount' => 3500000
        //         ],
        //         [
        //             'label' => 'BNI - Giro 003 (Rp 7.200.000)',
        //             'value' => 'BNI003',
        //             'amount' => 7200000
        //         ],
        //         [
        //             'label' => 'BRI - Giro 004 (Rp 2.800.000)',
        //             'value' => 'BRI004',
        //             'amount' => 2800000
        //         ]
        //     ];
        // }

        return $giros;
    }

    private function getAmountByGiro($bankCode)
    {
        // Ambil amount berdasarkan bank_code dari giro
        $giro = PaymentSrc::where('pay_type_code', 'GIRO')
            ->where('bank_code', $bankCode)
            ->where('amt', '>', 0)
            ->first();

        if ($giro) {
            return $giro->amt;
        }

        // Jika tidak ada data dari database, gunakan data dummy
        // $dummyGiros = [
        //     'BCA001' => 5000000,
        //     'MDR002' => 3500000,
        //     'BNI003' => 7200000,
        //     'BRI004' => 2800000
        // ];

        return $dummyGiros[$bankCode] ?? 0;
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

            // $this->dispatch('success', __('generic.string.delete_item'));
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
            // dd($this->input_details);
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

        // Simpan data PaymentSrc
        foreach ($this->input_details as $key => $detail) {
            $tr_seq = intval($key) + 1;

            $data = [
                'trhdr_id' => $this->object->id,
                'tr_type' => $this->object->tr_type,
                'tr_code' => $this->object->tr_code,
                'tr_seq' => $tr_seq,
                'pay_type_id' => $detail['pay_type_id'] ?? null,
                'pay_type_code' => $detail['pay_type_code'] ?? 'GIRO',
                'bank_id' => $detail['bank_id'] ?? null,
                'bank_code' => $detail['bank_code'] ?? '',
                'bank_reff' => $detail['bank_reff'] ?? '',
                'bank_duedt' => $detail['bank_duedt'] ?? date('Y-m-d'),
                'bank_note' => $detail['bank_note'] ?? '',
                'amt' => $detail['amt'] ?? 0,
                'amt_base' => $detail['amt'] ?? 0,
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
