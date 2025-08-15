<?php

namespace App\Livewire\TrdTire1\Transaction\ReceivablesSettlement;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\{PaymentHdr, OrderDtl, PaymentDtl, PaymentSrc, BillingDtl, BillingHdr, PaymentAdv};
use App\Models\TrdTire1\Master\{Partner, Material, PartnerBal};
use App\Models\SysConfig1\{ConfigConst, ConfigSnum};
use App\Enums\Status;
use App\Services\SysConfig1\ConfigService;
use App\Services\TrdTire1\PaymentService;
use App\Services\TrdTire1\Master\MasterService;
use Illuminate\Support\Facades\{Session, Log, DB};
use Exception;
use Carbon\Carbon;
use App\Services\TrdTire1\BillingService;

class Detail extends BaseComponent
{
    #region Constant Variables
    protected $paymentService;
    public $inputs = [];
    public $suppliers = [];
    public $selectedPartners = [];
    public $warehouses;
    public $partners;
    public $sales_type;
    public $tax_doc_flag;
    public $transaction_id;
    public $payments;
    public $deletedItems = [];
    public $newItems = [];
    public $total_amount;
    public $total_tax;
    public $total_dpp;
    public $total_discount;
    public $trType = "ARP";
    public $versionNumber = "0.0";
    public $object_detail;
    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];
    public $returnIds = [];
    public $currencyRate = 0;
    public $npwpOptions = [];
    public $shipOptions = [];
    protected $masterService;
    public $isPanelEnabled = "false";
    public $notaCount = 0;
    public $suratJalanCount = 0;
    public $partnerSearchText = '';
    public $partnerOptions = [];
    public $advanceBalance = 0;
    public $totalPaymentAmount = 0;
    public $totalNotaAmount = 0;
    public $advanceOptions = [];

    // Properties untuk Payment List
    public $PaymentType = [];
    public $input_payments = [];
    public $activePaymentItemKey = null;
    public $isCash = "false";
    public $isGiro = "false";
    public $isTrf = "false";
    public $isAdv = "false";

    // Properties untuk Debt List
    public $codeBill;
    public $input_details = [];
    public $input_advance = [];

    public $rules  = [
        'inputs.partner_id' => 'required',
        'input_details.*.amt' => 'required',
        'input_payments.*.pay_type_code' => 'required',
    ];

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'delete' => 'delete',
        'updateAmount' => 'updateAmount',
        'updateDiscount' => 'updateDiscount',
        'updateDPP' => 'updateDPP',
        'updatePPN' => 'updatePPN',
        'updateTotalTax' => 'updateTotalTax',
    ];

    #endregion

    #region Populate Data methods

    public function boot()
    {
        $this->paymentService = app(PaymentService::class);
    }    public function getTransactionCode()
    {
        $tax_doc_flag = !empty($this->inputs['tax_doc_flag']);
        $tr_type = $this->trType;

        // Generate tr_code using ConfigSnum with ARP_LASTID
        $configSnum = ConfigSnum::where('code', 'ARP_LASTID')->first();

        if ($configSnum) {
            $stepCnt = $configSnum->step_cnt;
            $proposedTrId = $configSnum->last_cnt + $stepCnt;

            // Check if the proposed ID exceeds wrap_high
            if ($proposedTrId > $configSnum->wrap_high) {
                $proposedTrId = $configSnum->wrap_low;
            }

            // Ensure the proposed ID is not below wrap_low
            $proposedTrId = max($proposedTrId, $configSnum->wrap_low);

            // Update the last_cnt in ConfigSnum
            $configSnum->last_cnt = $proposedTrId;
            $configSnum->save();

            // Generate the transaction code with the new sequence (8 digits)
            $this->inputs['tr_code'] = sprintf('%08d', $proposedTrId);
        } else {
            // Fallback to the original method if ConfigSnum not found
            $this->inputs['tr_code'] = PaymentHdr::generateTransactionId($tr_type, $tax_doc_flag);
        }
    }

    public function onSOTaxChange()
    {
        try {
            // Ambil data konfigurasi berdasarkan konstanta pajak
            $configData = ConfigConst::select('num1', 'str1')
                ->where('const_group', 'TRX_SO_TAX')
                ->where('str1', $this->inputs['tax_flag'])
                ->first();

            $this->inputs['tax_value'] = $configData->num1 ?? 0; // Nilai pajak default 0 jika tidak ditemukan
            $taxType = $configData->str1 ?? ''; // Tipe pajak (str1)

            // Simpan tax_pct
            $this->inputs['tax_pct'] = $this->inputs['tax_value'];

            // Hitung DPP dan PPN berdasarkan tipe pajak
            $this->calculateDPPandPPN($taxType);
        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    public function calculateDPPandPPN($taxType)
    {
        try {
            $taxValue = (float)($this->inputs['tax_value'] ?? 0); // Nilai pajak (persentase)
            $totalAmount = (float)$this->total_amount; // Total amount dari input

            if ($taxType === 'I') {
                $dpp = $totalAmount / (1 + $taxValue / 100); // Rumus DPP
                $ppn = $totalAmount - $dpp; // Rumus PPN
            } elseif ($taxType === 'E') {
                $dpp = $totalAmount; // DPP sama dengan total amount
                $ppn = ($taxValue / 100) * $totalAmount; // Rumus PPN
            } else {
                $dpp = $totalAmount; // DPP sama dengan total amount
                $ppn = 0; // PPN nol
            }

            // Simpan hasil perhitungan
            $this->total_dpp = rupiah(round($dpp, 2));
            $this->total_tax = rupiah(round($ppn, 2));

            // Dispatch event untuk memperbarui UI
            $this->dispatch('updateDPP', $this->total_dpp);
            // $this->dispatch('updateTotalTax', $this->total_tax);
        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    public function onPartnerChanged()
    {
        $partner = Partner::find($this->inputs['partner_id']);
        $this->npwpOptions = $partner ? $this->listNpwp($partner) : null;
        $this->shipOptions = $partner ? $this->listShip($partner) : null;
    }

    private function listNpwp($partner)
    {
        if (!$partner->PartnerDetail || empty($partner->PartnerDetail->wp_details)) {
            return [];
        }
        $wpDetails = $partner->PartnerDetail->wp_details;

        if (is_string($wpDetails)) {
            $wpDetails = json_decode($wpDetails, true);
        }
        // Jika gagal decode atau bukan array, return array kosong untuk mencegah error
        if (!is_array($wpDetails)) {
            return [];
        }

        return array_map(function ($item) {
            return [
                'label' => ($item['npwp']),
                'value' => $item['npwp'],
            ];
        }, $wpDetails);
    }
    private function listShip($partner)
    {
        if (!$partner->PartnerDetail || empty($partner->PartnerDetail->shipping_address)) {
            return [];
        }
        $shipDetail = $partner->PartnerDetail->shipping_address;

        if (is_string($shipDetail)) {
            $shipDetail = json_decode($shipDetail, true);
        }
        // Jika gagal decode atau bukan array, return array kosong untuk mencegah error
        if (!is_array($shipDetail)) {
            return [];
        }

        return array_map(function ($item) {
            return [
                'label' => ($item['name']),
                'value' => $item['name'],
            ];
        }, $shipDetail);
    }

    protected function onPreRender()
    {

        $this->customValidationAttributes  = [
            'inputs.tax'      => $this->trans('tax'),
            'inputs.tr_code'      => $this->trans('tr_code'),
            'inputs.partner_id'      => $this->trans('partner_id'),
            'inputs.send_to_name'      => $this->trans('send_to_name'),
        ];

        $this->masterService = new MasterService();
        $this->partners = $this->masterService->getCustomers();
        $this->warehouses = $this->masterService->getWarehouse();
        $this->PaymentType = $this->masterService->getPaymentTypeData();
        $this->codeBill = $this->masterService->getBillCode();

        // Tidak otomatis populate advance items saat Create mode
        // Advance items akan muncul setelah partner dipilih melalui confirmSelection()

        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }
            $this->object = PaymentHdr::withTrashed()->find($this->objectIdValue);
            if (!$this->object) {
                $this->dispatch('error', 'Object not found');
                return;
            }
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['status_code_text'] = $this->object->status_Code_text;
            $this->inputs['tax_doc_flag'] = $this->object->tax_doc_flag;
            $this->inputs['partner_name'] = $this->object->partner->code . ' - ' . $this->object->partner->name;
            $this->inputs['tr_code'] = $this->object->tr_code;
            $trDate = $this->object->tr_date ? \Carbon\Carbon::parse($this->object->tr_date) : null;


            // Load details
            $this->loadDetails();
            $this->loadPaymentDetails();

            // Hitung ulang total pembayaran dan total nota
            $this->totalPaymentAmount = 0;
            $totalFromPayments = 0;
            foreach ($this->input_payments as $payment) {
                $amtValue = str_replace('.', '', $payment['amt'] ?? 0);
                $totalFromPayments += is_numeric($amtValue) ? (float)$amtValue : 0;
            }
            $totalAdvanceUsed = 0.0;
            foreach ($this->input_advance as $advance) {
                $totalAdvanceUsed += is_numeric($advance['amt']) ? (float)$advance['amt'] : 0.0;
            }
            $this->totalPaymentAmount = $totalAdvanceUsed + $totalFromPayments;

            $this->totalNotaAmount = 0;
            foreach ($this->input_details as $detail) {
                $this->totalNotaAmount += is_numeric($detail['amt']) ? (float)$detail['amt'] : 0;
            }
            // Jangan ambil advanceBalance dari PaymentAdv, tapi hitung dari selisih total pembayaran dan total nota
            $this->advanceBalance = round($this->totalPaymentAmount - $this->totalNotaAmount, 2);
            if (abs($this->advanceBalance) < 0.01) {
                $this->advanceBalance = 0;
            }
        }
        if (!$this->isEditOrView()) {
            $this->isPanelEnabled = "true";
            // Set default tr_date for Create mode if not already set
            if (empty($this->inputs['tr_date'])) {
                $this->inputs['tr_date'] = date('Y-m-d');
            }
        }
        if (!empty($this->inputs['tax_flag'])) {
            $this->onSOTaxChange();
        }

        // Tambahkan partnerOptions untuk dropdown bank_reff (hanya partner grup B - Bank)
        $this->partnerOptions = Partner::where('grp', Partner::BANK)
            ->orderBy('name')
            ->get()
            ->map(function($partner) {
                return [
                    'label' => $partner->name,
                    'value' => $partner->name,
                ];
            })->toArray();

        // Ambil daftar partner_bals yang amt_adv != 0 untuk dropdown Advance
        $this->advanceOptions = PartnerBal::where('amt_adv', '!=', 0)->get()
            ->map(function($bal) {
                return [
                    'label' => $bal->partner_code,
                    'value' => $bal->id,
                    'amt_adv' => $bal->amt_adv,
                ];
            })
            ->toArray();

        // Pastikan advanceOptions juga mengandung partnerbal_id yang sudah pernah dipakai (input_advance)
        $existingAdvanceIds = array_column($this->advanceOptions, 'value');
        foreach ($this->input_advance as $adv) {
            if (!in_array($adv['partnerbal_id'], $existingAdvanceIds) && !empty($adv['partnerbal_id'])) {
                $partnerBal = PartnerBal::find($adv['partnerbal_id']);
                $reffCode = $partnerBal ? $partnerBal->reff_code : ('ID ' . $adv['partnerbal_id']);
                $this->advanceOptions[] = [
                    'label' => $reffCode,
                    'value' => $adv['partnerbal_id'],
                    'amt_adv' => $adv['amtAdvBal'],
                ];
            }
        }

        // Inisialisasi input_advance jika belum ada
        if (!isset($this->input_advance) || !is_array($this->input_advance)) {
            $this->input_advance = [];
        }

    }

    public function onReset()
    {
        $this->reset('inputs', 'input_details', 'input_payments', 'input_advance');
        $this->object = new PaymentHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_type'] = $this->trType;
        $this->inputs['curr_code'] = "IDR";
        $this->inputs['tr_date'] = date('Y-m-d');
        $this->inputs['curr_id'] = app(ConfigService::class)->getConstIdByStr1('BASE_CURRENCY', $this->inputs['curr_code']);
        $this->inputs['curr_rate'] = 1.00;
        $this->inputs['wh_code'] = 18;
        $this->inputs['partner_id'] = 0;
    }

    public function deleteItem($index)
    {
        try {
            if (!isset($this->input_details[$index])) {
                throw new Exception(__('generic.error.delete_item', ['message' => 'Item not found.']));
            }

            array_splice($this->input_details, $index, 1);

            $this->dispatch('success', __('generic.string.delete_item'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    public function onCodeChanged($key, $billHdrId)
    {        $billHdr = BillingHdr::find($billHdrId); // Cari berdasarkan id

        if ($billHdr) {
            $this->input_details[$key]['tr_date'] = $billHdr->tr_date;
            $this->input_details[$key]['billhdrtr_code'] = $billHdr->id; // Simpan id, bukan tr_code

            // Ambil langsung dari amt pada BillingHdr
            $this->input_details[$key]['amtbill'] = $billHdr->amt ?? 0;
        } else {
            $this->dispatch('error', __('Bill not found.'));
        }
    }

    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = PaymentDtl::where('trhdr_id', $this->object->id)
                ->where('tr_type', $this->object->tr_type)
                ->orderBy('tr_seq')
                ->get();

            foreach ($this->object_detail as $key => $detail) {
                $amtbill = 0;
                $billhdr_id = null;
                $billhdrtr_code = null;
                $tr_date  = Carbon::now()->format('d-m-Y'); // Default to current date (d-m-Y)

                // Ambil BillingHdr id dari billdtl_id jika ada
                if ($detail->billdtl_id) {
                    $billingDtl = BillingDtl::find($detail->billdtl_id);
                    if ($billingDtl) {
                        $billingHdr = BillingHdr::find($billingDtl->trhdr_id);                        if ($billingHdr) {
                            $amtbill = $billingHdr->amt ?? 0;
                            $billhdr_id = $billingHdr->id;
                            $billhdrtr_code = $billingHdr->tr_code;
                            $tr_date = $billingHdr->tr_date ? Carbon::parse($billingHdr->tr_date)->format('d-m-Y') : Carbon::now()->format('d-m-Y');
                        }
                    }
                }

                // Jika billhdr_id masih kosong, coba ambil dari billhdrtr_code (tr_code) di detail
                if (!$billhdr_id && !empty($detail->billhdrtr_code)) {
                    $billingHdr = BillingHdr::where('tr_code', $detail->billhdrtr_code)->first();
                    if ($billingHdr) {
                        $billhdr_id = $billingHdr->id;
                        $billhdrtr_code = $billingHdr->tr_code;
                        $tr_date = $billingHdr->tr_date ? Carbon::parse($billingHdr->tr_date)->format('d-m-Y') : Carbon::now()->format('d-m-Y');
                        $amtbill = $billingHdr->amt ?? 0;
                    }
                }

                // Jika masih kosong, coba ambil dari billhdr_id langsung
                if (!$billhdr_id && !empty($detail->billhdr_id)) {
                    $billingHdr = BillingHdr::find($detail->billhdr_id);
                    if ($billingHdr) {
                        $billhdr_id = $billingHdr->id;
                        $billhdrtr_code = $billingHdr->tr_code;
                        $tr_date = $billingHdr->tr_date ? Carbon::parse($billingHdr->tr_date)->format('d-m-Y') : Carbon::now()->format('d-m-Y');
                        $amtbill = $billingHdr->amt ?? 0;
                    }
                }

                $amt_reff = 0;
                if ($billhdr_id && isset($billingHdr)) {
                    $amt_reff = $billingHdr->amt_reff ?? 0;
                }
                // Untuk mode edit, tambahkan kembali amt yang sudah dibayar agar menunjukkan outstanding awal
                $currentPaymentAmt = $detail->amt ?? 0;
                $outstanding_amt = ($amtbill ?? 0) - $amt_reff + $currentPaymentAmt;

                // Hitung due_date dari tr_date + payment_due_days
                if (isset($billingHdr) && $billingHdr) {
                    $tr_date = $billingHdr->tr_date ? \Carbon\Carbon::parse($billingHdr->tr_date) : \Carbon\Carbon::now();
                    $payment_due_days = (int)($billingHdr->payment_due_days ?? 0);
                    $due_date = $tr_date->copy()->addDays($payment_due_days)->format('Y-m-d');
                } else {
                    // Jika tidak ada billingHdr, tetap pakai Carbon
                    $tr_date = isset($tr_date) ? \Carbon\Carbon::parse($tr_date) : \Carbon\Carbon::now();
                    $due_date = $tr_date->format('Y-m-d');
                }

                $this->input_details[] = [
                    'billhdr_id'      => $billhdr_id, // id BillingHdr untuk proses simpan
                    'billhdrtr_code'  => $billhdrtr_code, // kode nota untuk tampilan
                    'due_date'        => $due_date, // tanggal jatuh tempo (Y-m-d agar cocok input type="date")
                    'amtbill'         => $amtbill,
                    'outstanding_amt' => $outstanding_amt,
                    'amt'             => $detail->amt ?? null,
                ];
            }

        }
    }
    #endregion

    #region Payment List Methods
    // Handler untuk hapus row kosong jika modal ditutup tanpa klik Save
    public function closePaymentDialogBox()
    {
        if ($this->activePaymentItemKey !== null && isset($this->input_payments[$this->activePaymentItemKey]) && empty($this->input_payments[$this->activePaymentItemKey]['pay_type_code'])) {
            unset($this->input_payments[$this->activePaymentItemKey]);
            $this->input_payments = array_values($this->input_payments);
        }
        $this->activePaymentItemKey = null;
    }
    public function addPaymentItem()
    {
        try {
            $newItem = [
                'tr_type' => '',
                'pay_type_code' => '',
                'pay_type_id' => null,
                'amt_tunai' => null,
                'amt_giro' => null,
                'bank_reff_giro' => null,
                'bank_reff_no_giro' => null,
                'bank_duedt_giro' => null,
                'amt_trf' => null,
                'bank_reff_transfer' => null,
                'bank_reff_no_transfer' => null,
                'bank_duedt_transfer' => null,
                'amt_advance' => null,
                'bank_note' => null,
                'amt' => 0,
            ];
            $this->input_payments[] = $newItem;

            $newKey = array_key_last($this->input_payments);
            $this->activePaymentItemKey = $newKey;
            $this->dispatch('openPaymentDialogBox');
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
        }
    }

    public function deletePaymentItem($index)
    {
        try {
            if (!isset($this->input_payments[$index])) {
                throw new Exception(__('generic.error.delete_item', ['message' => 'Item not found.']));
            }

            if (!empty($this->objectIdValue) && isset($this->input_payments[$index]['id'])) {
                $this->deletedItems[] = $this->input_payments[$index]['id'];
            }

            unset($this->input_payments[$index]);
            $this->input_payments = array_values($this->input_payments);

            $this->dispatch('success', __('generic.string.delete_item'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
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

        $payType = ConfigConst::where('str1', $payTypeCode)->first();
        $this->input_payments[$key]['pay_type_id'] = $payType ? $payType->id : null;
        $this->input_payments[$key]['pay_type_code'] = $payTypeCode;

        // Update the table fields directly
        $this->input_payments[$key]['amt'] = $this->input_payments[$key]['amt_tunai'] ??
            $this->input_payments[$key]['amt_giro'] ??
            $this->input_payments[$key]['amt_trf'] ??
            $this->input_payments[$key]['amt_advance'] ?? 0;

        // Handle bank_reff concatenation
        $bankReffGiro = trim(($this->input_payments[$key]['bank_reff_giro'] ?? '') . ' - ' . ($this->input_payments[$key]['bank_reff_no_giro'] ?? ''));
        $bankReffTransfer = trim(($this->input_payments[$key]['bank_reff_transfer'] ?? '') . ' - ' . ($this->input_payments[$key]['bank_reff_no_transfer'] ?? ''));
        $bankNote = $this->input_payments[$key]['bank_note'] ?? '';
        $this->input_payments[$key]['bank_reff'] = $bankReffGiro !== ' - ' ? $bankReffGiro : ($bankReffTransfer !== ' - ' ? $bankReffTransfer : ($bankNote !== '' ? $bankNote : ''));

        // Ensure bank_reff is set to bank_note for CASH and ADV
        if ($payTypeCode === 'CASH' || $payTypeCode === 'ADV') {
            $this->input_payments[$key]['bank_reff'] = $bankNote;
        }
        $this->dispatch('closePaymentDialogBox');
        // $this->activePaymentItemKey = null;
    }

    public function onPaymentTypeChange()
    {
        $key = $this->activePaymentItemKey;
        $newType = $this->input_payments[$key]['pay_type_code'] ?? null;
        $oldType = $this->input_payments[$key]['current_type'] ?? null;
        $this->input_payments[$key]['tr_type'] = $newType;

        $paymentTypes = [
            'CASH' => [
                'fields'    => ['bank_code_tunai', 'amt_tunai'],
                'flag'      => 'isCash',
                'backupKey' => 'cash_backup'
            ],
            'GIRO' => [
                'fields'    => ['amt_giro', 'bank_reff_giro', 'bank_reff_no_giro', 'bank_duedt_giro'],
                'flag'      => 'isGiro',
                'backupKey' => 'giro_backup'
            ],
            'TRF' => [
                'fields'    => ['bank_code_transfer', 'bank_id_transfer', 'bank_reff_transfer', 'bank_duedt_transfer'],
                'flag'      => 'isTrf',
                'backupKey' => 'trf_backup'
            ],
            'ADV' => [
                'fields'    => ['bank_code_advance', 'amt_advance'],
                'flag'      => 'isAdv',
                'backupKey' => 'adv_backup'
            ]
        ];

        foreach ($paymentTypes as $type => $config) {
            if ($oldType === $type && $newType !== $type) {
                $backup = [];
                foreach ($config['fields'] as $field) {
                    $backup[$field] = $this->input_payments[$key][$field] ?? null;
                }
                $this->input_payments[$key][$config['backupKey']] = $backup;
            }
        }

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

        $this->input_payments[$key]['current_type'] = $newType;
    }

    protected function loadPaymentDetails()
    {
        if (!empty($this->object)) {
            // Load payment sources
            $this->object_detail = PaymentSrc::where('trhdr_id', $this->object->id)
                ->whereIn('tr_type', [$this->object->tr_type, 'ARPS'])
                ->get();

            if (!empty($this->object_detail)) {
                foreach ($this->object_detail as $key => $detail) {
                    $this->input_payments[$key] = populateArrayFromModel($detail);

                    if (!isset($this->input_payments[$key]['tr_type'])) {
                        $this->input_payments[$key]['tr_type'] = $detail->pay_type_code;
                    }

                    $this->input_payments[$key]['pay_type_code'] = $detail->pay_type_code;
                    $this->input_payments[$key]['pay_type_id'] = $detail->pay_type_id;

                    switch ($detail->pay_type_code) {
                        case 'TRF':
                            $parts = explode(' - ', $detail->bank_reff);
                            $this->input_payments[$key]['bank_reff_transfer'] = $parts[0] ?? '';
                            $this->input_payments[$key]['bank_reff_no_transfer'] = $parts[1] ?? '';
                            $this->input_payments[$key]['amt_trf'] = $detail->amt;
                            $this->input_payments[$key]['bank_duedt_transfer'] = $detail->bank_duedt;
                            break;
                        case 'GIRO':
                            $parts = explode(' - ', $detail->bank_reff);
                            $this->input_payments[$key]['bank_reff_giro'] = $parts[0] ?? '';
                            $this->input_payments[$key]['bank_reff_no_giro'] = $parts[1] ?? '';
                            $this->input_payments[$key]['amt_giro'] = $detail->amt;
                            $this->input_payments[$key]['bank_duedt_giro'] = $detail->bank_duedt;
                            break;
                        case 'CASH':
                            $this->input_payments[$key]['amt_tunai'] = $detail->amt;
                            $this->input_payments[$key]['bank_reff'] = $detail->bank_note ?? '';
                            break;
                        case 'ADV':
                            $this->input_payments[$key]['amt_advance'] = $detail->amt;
                            $this->input_payments[$key]['bank_reff'] = $detail->bank_note ?? '';
                            break;
                    }
                }
            } else {
                $this->input_payments = [];
            }

            // Load advance (lebih bayar) yang sudah pernah digunakan
            $advances = PaymentAdv::where('trhdr_id', $this->object->id)->get();


            $this->input_advance = [];
            // Tampilkan PaymentAdv yang hasil pemakaian advance (amt negatif) dan bukan overpayment (amt positif)
            foreach ($advances as $adv) {
                // Hanya load PaymentAdv dengan amt negatif (penggunaan advance), bukan amt positif (overpayment)
                if (!empty($adv->partnerbal_id) && $adv->amt < 0) {
                    $this->input_advance[] = [
                        'partnerbal_id' => $adv->partnerbal_id,
                        'partner_code' => $adv->partner_code ?? '',
                        'amtAdvBal' => abs($adv->amt),
                        'amt' => abs($adv->amt),
                    ];
                }
            }
            // Perbaiki perhitungan advanceBalance:
            // Setelah save (edit mode), advanceBalance harus 0 jika seluruh advance sudah digunakan (sudah masuk ke total pembayaran)
            $this->advanceBalance = round($this->totalPaymentAmount - $this->totalNotaAmount, 2);
            if ($this->isEditOrView()) {
                // Jika total pembayaran (termasuk advance) sudah sama dengan total nota, lebih bayar = 0
                if (abs($this->totalPaymentAmount - $this->totalNotaAmount) < 0.01) {
                    $this->advanceBalance = 0;
                } else {
                    // Jika masih ada sisa advance (misal, advance > total nota), tampilkan sisa lebih bayar
                    // Namun, jika advance sudah dipakai semua, advanceBalance tetap 0
                    // (advanceBalance tidak boleh menampilkan amt advance yang sudah dipakai)
                    if (empty($this->input_advance) || array_sum(array_column($this->input_advance, 'amt')) == 0) {
                        $this->advanceBalance = 0;
                    }
                }
                // Hilangkan sisa kecil akibat pembulatan
                if (abs($this->advanceBalance) < 0.01) {
                    $this->advanceBalance = 0;
                }
            } else {
                // Mode create: advanceBalance = sisa advance jika ada, atau selisih pembayaran-nota
                if (abs($this->advanceBalance) < 0.01) {
                    $this->advanceBalance = 0;
                }
            }
        }
    }
    #endregion

    #region CRUD Methods
    public function onValidateAndSave()
    {
        if ($this->actionValue == 'Edit') {
            if ($this->object->isOrderCompleted()) {
                $this->dispatch('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
                return;
            }
        }

        if (!isNullOrEmptyNumber($this->inputs['partner_id'])) {
            $partner = Partner::find($this->inputs['partner_id']);
            $this->inputs['partner_code'] = $partner->code;
        }

        // Ensure payment_term is set
        if (!empty($this->inputs['payment_term_id'])) {
            $paymentTerm = ConfigConst::find($this->inputs['payment_term_id']);
            $this->inputs['payment_term'] = $paymentTerm->str1;
            $this->inputs['payment_due_days'] = $paymentTerm->num1;
        }

        $this->object->saveOrderHeader($this->appCode, $this->trType, $this->inputs, 'ReceivablesSettlement_LASTID');

        // Jika dalam mode 'Create', perbarui objectIdValue agar detail bisa disimpan
        if ($this->actionValue == 'Create') {
            $this->objectIdValue = $this->object->id;
            $this->actionValue = 'Edit'; // Ubah actionValue ke Edit setelah membuat header
        }
    }

    public function deleteTransaction()
    {
        try {
            $this->paymentService->delPayment($this->object->id);

            $this->dispatch('success', __('Data berhasil terhapus'));
            return redirect()->route(str_replace('.Detail', '', $this->baseRoute));

        } catch (\Exception $e) {
            $this->dispatch('error', __('generic.error.delete', [
                'message' => $e->getMessage()
            ]));
        }
    }
    public function delete()
    {
        try {
            if ($this->object->isOrderCompleted()) {
                $this->dispatch('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
                return;
            }

            if (!$this->object->isOrderEnableToDelete()) {
                $this->dispatch('warning', 'Nota ini tidak bisa delete, karena memiliki material yang sudah dijual.');
                return;
            }

            if (isset($this->object->status_code)) {
                $this->object->status_code =  Status::NONACTIVE;
            }
            $this->object->save();
            $this->object->delete();
            $messageKey = 'generic.string.disable';
            $this->dispatch('success', __($messageKey));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
    }
    #endregion

    public function SaveAll()
    {
        try {
            // Generate tr_code jika dalam mode create
            if ($this->actionValue == 'Create') {
                $this->getTransactionCode();
            }
            $headerData = [
                'tr_code' => $this->inputs['tr_code'],
                'tr_type' => $this->trType,
                'tr_date' => $this->inputs['tr_date'],
                'partner_id' => $this->inputs['partner_id'],
                'partner_code' => $this->inputs['partner_code'],
                'status_code' => Status::OPEN,
                'process_flag' => 'N',
                'wh_id' => $this->inputs['wh_id'] ?? 0, // berikan default value
                'wh_code' => $this->inputs['wh_code'] ?? '', // berikan default value
                'amt' => $this->normalizeAmount(array_sum(array_column($this->input_details, 'amt'))), // tambahkan total amount
                'curr_id' => $this->inputs['curr_id'],
                'curr_code' => $this->inputs['curr_code'],
                'curr_rate' => $this->inputs['curr_rate'],
            ];
            // dd($headerData, $this->inputs);

            // Validasi partner wajib dipilih
            if (empty($headerData['partner_id']) || empty($headerData['partner_code'])) {
                $this->dispatch('error', 'Partner wajib dipilih!');
                return;
            }

            // Validasi input detail
            $hasValidDetails = false;
            foreach ($this->input_details as $detail) {
                if (!empty($detail['billhdr_id']) && !empty($detail['amt'])) {
                    $hasValidDetails = true;
                    break;
                }
            }

            if (!$hasValidDetails) {
                $this->dispatch('error', 'Silakan tambahkan dan pilih setidaknya satu nota yang valid dengan jumlah yang tidak kosong.');
                return;
            }

            // Siapkan data detail
            $detailData = [];
            foreach ($this->input_details as $key => $detail) {
                if (empty($detail['billhdr_id']) || empty($detail['amt'])) {
                    continue; // Skip detail yang tidak lengkap
                }
                $billingHdr = BillingHdr::find($detail['billhdr_id']); // Ambil berdasarkan id
                if ($billingHdr) {
                    // Normalisasi format angka untuk amt
                    $amt = $this->normalizeAmount($detail['amt']);

                    $detailData[] = [
                        'tr_seq' => $key + 1,
                        'amt' => $amt,
                        'billhdrtr_code' => $billingHdr->tr_code, // Simpan tr_code ke database
                        'billhdrtr_type' => $billingHdr->tr_type,
                        'billhdr_id' => $billingHdr->id, // Simpan juga id BillingHdr
                        'tr_code' => $headerData['tr_code'], // Tambahkan tr_code dari header
                    ];
                } else {
                    // Jika tidak ditemukan, skip
                    continue;
                }
            }




            // Siapkan data pembayaran
            $paymentData = [];
            foreach ($this->input_payments as $key => $payment) {
                $payType = ConfigConst::where('str1', $payment['pay_type_code'])->first();

                // Normalisasi format angka untuk amt
                $amt = $payment['amt_tunai'] ?? $payment['amt_giro'] ?? $payment['amt_trf'] ?? $payment['amt_advance'] ?? 0;
                $amt = $this->normalizeAmount($amt);

                $paymentData[] = [
                    'tr_seq' => $key + 1,
                    'pay_type_id' => $payType ? $payType->id : null,
                    'pay_type_code' => $payment['pay_type_code'],
                    'bank_id' => $payType ? $payType->id : null,
                    'bank_code' => $payment['pay_type_code'],
                    'bank_note' => $payment['bank_note'] ?? '',
                    'amt' => $amt,
                    'bank_reff' => $this->getBankReff($payment),
                    'bank_duedt' => $this->getBankDate($payment),
                ];
            }

            // Siapkan data advance
            $advanceData = [];
            foreach ($this->input_advance as $key => $advance) {

                if (!empty($advance['partnerbal_id']) && !empty($advance['amt'])) {
                    $advanceData[] = [
                        'tr_seq' => $key + 1,
                        'partnerbal_id' => $advance['partnerbal_id'],
                        'amt' => $this->normalizeAmount($advance['amt']),
                        'adv_type_code' => 'ARADVPAY',
                        'adv_type_id' => app(ConfigService::class)->getConstIdByStr1('TRX_PAYMENT_TYPE_ADVS', 'ARADVPAY'),
                        'reff_id' => null, // akan diisi di service
                        'reff_type' => $this->trType,
                        'reff_code' => $headerData['tr_code'],
                    ];
                } else {
                }
            }
            
            // Check if detailData is empty after all processing
            if (empty($detailData)) {
                $this->dispatch('error', 'Tidak ada detail pembayaran yang valid untuk disimpan. Pastikan Anda telah memilih nota yang valid.');
                return;
            }

            try {
                // Untuk mode Edit, set ID ke headerData
                if ($this->actionValue == 'Edit') {
                    $headerData['id'] = $this->objectIdValue;
                }

                $result = $this->paymentService->addPayment($headerData, $detailData, $paymentData, $advanceData, $this->advanceBalance);

                if (!$result || !isset($result->id)) {
                    throw new Exception('Failed to save payment: Invalid result returned from addPayment service');
                }

                $this->objectIdValue = $result->id;
                $this->actionValue = 'Edit';
            } catch (\Exception $e) {
                Log::error('Error saving payment: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                    'detailData' => $detailData
                ]);
                $this->dispatch('error', 'Error saving payment: ' . $e->getMessage());
                return;
            }

            $this->dispatch('disable-onbeforeunload');
            $this->dispatch('success', __('Data berhasil disimpan.'));
            return redirect()->route('TrdTire1.Transaction.ReceivablesSettlement.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->objectIdValue)
            ]);
        } catch (Exception $e) {
            $this->dispatch('error', __('Error: ' . $e->getMessage()));
        }
    }

    private function getBankReff($payment)
    {
        switch ($payment['pay_type_code']) {
            case 'GIRO':
                return trim($payment['bank_reff_giro'] . ' - ' . $payment['bank_reff_no_giro']);
            case 'TRF':
                return trim($payment['bank_reff_transfer'] . ' - ' . $payment['bank_reff_no_transfer']);
            case 'CASH':
            case 'ADV':
                return $payment['bank_note'] ?? '';
            default:
                return '';
        }
    }

    private function getBankDate($payment)
    {
        switch ($payment['pay_type_code']) {
            case 'GIRO':
                return $payment['bank_duedt_giro'] ?? '1900-01-01';
            case 'TRF':
                return $payment['bank_duedt_transfer'] ?? '1900-01-01';
            default:
                return '1900-01-01';
        }
    }

    /**
     * Normalisasi format angka dari format Indonesia (dengan titik sebagai pemisah ribuan)
     * ke format numerik yang bisa disimpan ke database
     */
    private function normalizeAmount($amount)
    {
        if (empty($amount)) {
            return 0;
        }

        // Jika sudah berupa angka, return langsung
        if (is_numeric($amount)) {
            return (float)$amount;
        }

        // Jika berupa string, hapus semua karakter non-digit kecuali titik dan koma
        $cleaned = preg_replace('/[^0-9.,]/', '', $amount);

        // Jika ada koma sebagai pemisah desimal, ganti dengan titik
        $cleaned = str_replace(',', '.', $cleaned);

        // Hapus semua titik kecuali yang terakhir (untuk desimal)
        $parts = explode('.', $cleaned);
        if (count($parts) > 2) {
            // Ada lebih dari satu titik, berarti ada pemisah ribuan
            $decimal = array_pop($parts); // Ambil bagian terakhir sebagai desimal
            $integer = implode('', $parts); // Gabungkan semua bagian lain
            $cleaned = $integer . '.' . $decimal;
        }

        return (float)$cleaned;
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    public function isEditOrView()
    {
        return in_array($this->actionValue, ['Edit', 'View']);
    }

    private function updateVersionNumber2()
    {
        $this->versionNumber = "{$this->notaCount}.{$this->suratJalanCount}";
    }

    public function openPartnerDialogBox()
    {
        $this->partnerSearchText = '';
        $this->suppliers = [];
        $this->selectedPartners = [];
        $this->dispatch('openPartnerDialogBox');
    }
    public function searchPartners()
    {
        if (!empty($this->partnerSearchText)) {
            $searchTerm = strtoupper($this->partnerSearchText);
            $this->suppliers = Partner::where('grp', Partner::CUSTOMER)
                ->where(function ($query) use ($searchTerm) {
                    $query->whereRaw("UPPER(code) LIKE ?", ["%{$searchTerm}%"])
                        ->orWhereRaw("UPPER(name) LIKE ?", ["%{$searchTerm}%"]);
                })
                ->get();
        } else {
            $this->dispatch('error', "Mohon isi kode atau nama supplier");
        }
    }

    public function selectPartner($partnerId)
    {
        $key = array_search($partnerId, $this->selectedPartners);

        if ($key !== false) {
            unset($this->selectedPartners[$key]);
            $this->selectedPartners = array_values($this->selectedPartners);
        } else {
            $this->selectedPartners[] = $partnerId;
        }
    }


    public function onPartnerChange()
    {
        $partner = Partner::find($this->inputs['partner_id']);

        if ($partner) {
            $this->inputs['partner_id'] = $partner->id;
            $this->inputs['partner_code'] = $partner->code; // Set partner_code
            $this->inputs['partner_name'] = $partner->code . ' - ' . $partner->name;

            $billingService = app(BillingService::class);
            $outstandingBills = collect($billingService->getOutstandingBillsByPartner($partner->id));

            // Filter hanya piutang yang outstanding_amt > 0
            $validOutstandingBills = $outstandingBills->filter(function($item) {
                return $item->outstanding_amt > 0;
            });


            // Jika tidak ada nota outstanding, beri warning, tapi tetap lanjutkan proses advance
            if ($validOutstandingBills->isEmpty()) {
                $this->input_details = [];
                $this->dispatch('warning', "Tidak ada nota yang dilunasi untuk customer ini, namun saldo advance tetap bisa digunakan.");
            } else {
                // Hanya buat input_details jika ada piutang yang outstanding_amt > 0
                $this->input_details = $validOutstandingBills
                    ->map(function($item) {
                        return [
                            'billhdr_id'      => $item->billhdr_id,
                            'billhdrtr_code'  => $item->billhdrtr_code,
                            'due_date'        => Carbon::parse($item->due_date)->format('Y-m-d'), // pastikan formatnya sesuai
                            'amtbill'         => $item->outstanding_amt, // atau field lain sesuai kebutuhan
                            'outstanding_amt' => $item->outstanding_amt,
                            'amt'             => 0,
                        ];
                    })->toArray();
            }

            // Pada mode Create, populate advance dari PartnerBal jika ada (hanya saat partner dipilih)
            if (!$this->isEditOrView()) {
                $this->input_advance = [];
                $partnerAdvances = PartnerBal::where('partner_id', $partner->id)
                    ->where('amt_adv', '!=', 0)
                    ->get();
                foreach ($partnerAdvances as $partnerBal) {
                    $this->input_advance[] = [
                        'partnerbal_id' => $partnerBal->id,
                        'partner_code' => $partnerBal->partner_code,
                        'amtAdvBal' => (int)round($partnerBal->amt_adv),
                        'amt' => 0,
                    ];
                }
            }

            $this->dispatch('success', "Custommer berhasil dipilih.");
            $this->dispatch('closePartnerDialogBox');
        }
    }

    public function onTaxPayerChanged()
    {
        $partner = Partner::find($this->inputs['partner_id']);
        if ($partner && $partner->PartnerDetail && !empty($partner->PartnerDetail->wp_details)) {
            $wpDetails = $partner->PartnerDetail->wp_details;
            if (is_string($wpDetails)) {
                $wpDetails = json_decode($wpDetails, true);
            }
            if (is_array($wpDetails)) {
                foreach ($wpDetails as $detail) {
                    if ($detail['npwp'] == $this->inputs['npwp_code']) {
                        $this->inputs['textarea_npwp'] = $detail['wp_name'] . "\n" . $detail['wp_location'];
                        $this->inputs['npwp_code'] = $detail['npwp'];
                        $this->inputs['npwp_name'] = $detail['wp_name'];
                        $this->inputs['npwp_addr'] = $detail['wp_location'];
                        break;
                    }
                }
            }
        }
    }

    public function onShipToChanged()
    {
        $partner = Partner::find($this->inputs['partner_id']);
        if ($partner && $partner->PartnerDetail && !empty($partner->PartnerDetail->shipping_address)) {
            $shipDetails = $partner->PartnerDetail->shipping_address;
            if (is_string($shipDetails)) {
                $shipDetails = json_decode($shipDetails, true);
            }
            if (is_array($shipDetails)) {
                foreach ($shipDetails as $detail) {
                    if ($detail['name'] == $this->inputs['ship_to_name']) {
                        $this->inputs['textareasend_to'] = $detail['address'];
                        $this->inputs['ship_to_name'] = $detail['name'];
                        $this->inputs['ship_to_addr'] = $detail['address'];
                        break;
                    }
                }
            }
        }
    }

    // Membagi amt pembayaran ke setiap nota secara merata
    public function payItem()
    {
        // 1. Hitung total advance yang tersedia
        $totalAdvance = 0;
        foreach ($this->input_advance as $advance) {
            if (!empty($advance['amtAdvBal']) && is_numeric($advance['amtAdvBal'])) {
                $totalAdvance += (float)$advance['amtAdvBal'];
            }
        }

        // 2. Hitung total pembayaran yang diinput user pada payment
        $totalPayment = 0;
        foreach ($this->input_payments as $payment) {
            if (!empty($payment['amt'])) {
                $amtValue = str_replace('.', '', $payment['amt']);
                $totalPayment += is_numeric($amtValue) ? (float)$amtValue : 0;
            }
        }

        // 3. Total yang tersedia untuk pelunasan = advance + payment
        $totalAvailable = $totalAdvance + $totalPayment;

        // 4. Urutkan nota berdasarkan due_date (jika ada)
        $details = [];
        foreach ($this->input_details as $key => $detail) {
            $details[] = [
                'key' => $key,
                'due_date' => isset($detail['due_date']) ? strtotime($detail['due_date']) : 0,
                'outstanding_amt' => (isset($detail['outstanding_amt']) && is_numeric($detail['outstanding_amt'])) ? (float)$detail['outstanding_amt'] : 0,
            ];
        }
        usort($details, function($a, $b) {
            return $a['due_date'] <=> $b['due_date'];
        });

        // 5. Reset amt semua nota dan advance
        foreach ($this->input_details as $key => $detail) {
            $this->input_details[$key]['amt'] = 0;
        }
        foreach ($this->input_advance as $key => $advance) {
            $this->input_advance[$key]['amt'] = 0;
        }

        // 6. Distribusi pembayaran: prioritas advance dulu, lalu payment
        $remaining = $totalAvailable;
        $advanceUsed = 0;

        // Bagi pembayaran ke nota satu per satu sesuai urutan jatuh tempo
        foreach ($details as $item) {
            $key = $item['key'];
            $outstanding = $item['outstanding_amt'];
            if ($remaining <= 0) {
                $this->input_details[$key]['amt'] = 0;
            } else {
                $toPay = min($outstanding, $remaining);
                $this->input_details[$key]['amt'] = round($toPay, 2);
                $remaining -= $toPay;

                // Track berapa advance yang sudah digunakan
                if ($advanceUsed < $totalAdvance) {
                    $advanceForThisNote = min($toPay, $totalAdvance - $advanceUsed);
                    $advanceUsed += $advanceForThisNote;
                }
            }
        }

        // 7. Update advance amt sesuai yang sudah digunakan
        $remainingAdvanceUsed = $advanceUsed;
        foreach ($this->input_advance as $key => $advance) {
            if ($remainingAdvanceUsed <= 0) break;

            $amtAdvBal = !empty($advance['amtAdvBal']) && is_numeric($advance['amtAdvBal'])
                ? (float)$advance['amtAdvBal'] : 0;

            if ($amtAdvBal > 0) {
                $useFromThisAdvance = min($amtAdvBal, $remainingAdvanceUsed);
                $this->input_advance[$key]['amt'] = $useFromThisAdvance;
                $remainingAdvanceUsed -= $useFromThisAdvance;
            }
        }

        // 8. Update summary footer
        // Total advance yang digunakan
        $totalAdvanceUsed = 0.0;
        $totalAmtAdvBal = 0.0;
        foreach ($this->input_advance as $advance) {
            $totalAdvanceUsed += is_numeric($advance['amt']) ? (float)$advance['amt'] : 0.0;
            $totalAmtAdvBal += is_numeric($advance['amtAdvBal']) ? (float)$advance['amtAdvBal'] : 0.0;
        }

        // Total dari input_payments
        $totalFromPayments = 0;
        foreach ($this->input_payments as $payment) {
            $amtValue = str_replace('.', '', $payment['amt'] ?? 0);
            $totalFromPayments += is_numeric($amtValue) ? (float)$amtValue : 0;
        }

        // Total pembayaran = advance yang digunakan + payment amounts
        $this->totalPaymentAmount = $totalAdvanceUsed + $totalFromPayments;

        // Total nota yang dibayar
        $this->totalNotaAmount = 0;
        foreach ($this->input_details as $detail) {
            $this->totalNotaAmount += is_numeric($detail['amt']) ? (float)$detail['amt'] : 0;
        }

        // Sisa advance setelah digunakan
        // Jika tidak ada pembayaran lain, advanceBalance = sisa amtAdvBal
        // Jika ada pembayaran lain, advanceBalance = totalPaymentAmount - totalNotaAmount
        if ($totalFromPayments == 0) {
            $this->advanceBalance = $totalAmtAdvBal - $totalAdvanceUsed;
        } else {
            $this->advanceBalance = $this->totalPaymentAmount - $this->totalNotaAmount;
        }

        $this->dispatch('success', 'Pembayaran berhasil dibagi ke semua nota menggunakan advance dan payment sesuai outstanding dan urutan jatuh tempo.');
    }

    public function addAdvanceItem()
    {
        $this->input_advance[] = [
            'partnerbal_id' => null,
            'amtAdvBal' => 0,
            'amt' => 0,
        ];
    }

    public function deleteAdvanceItem($index)
    {
        try {
            if (!isset($this->input_advance[$index])) {
                throw new Exception('Advance item not found.');
            }

            unset($this->input_advance[$index]);
            $this->input_advance = array_values($this->input_advance);

            $this->dispatch('success', 'Advance item berhasil dihapus.');
        } catch (Exception $e) {
            $this->dispatch('error', 'Error menghapus advance item: ' . $e->getMessage());
        }
    }

    public function onAdvanceChanged($key, $partnerbal_id)
    {
        $selected = collect($this->advanceOptions)->firstWhere('value', $partnerbal_id);

        if ($selected) {
            $this->input_advance[$key]['amtAdvBal'] = number_format((float)$selected['amt_adv'], 0);
            $this->input_advance[$key]['partnerbal_id'] = $selected['value'];
            $this->input_advance[$key]['amt'] = $selected['amt_adv']; // Gunakan amt_adv bukan value
            $this->dispatch('success', 'Advance amount berhasil diambil: ' . number_format((float)$selected['amt_adv'], 0));
        } else {
            $this->input_advance[$key]['amtAdvBal'] = 0;
            $this->input_advance[$key]['partnerbal_id'] = null;
            $this->input_advance[$key]['amt'] = 0;
            $this->dispatch('error', 'Partner balance tidak ditemukan.');
        }
        $this->input_advance = array_values($this->input_advance);
    }
}
