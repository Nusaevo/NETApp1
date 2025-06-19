<?php

namespace App\Livewire\TrdTire1\Transaction\ReceivablesSettlement;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\{PaymentHdr, OrderDtl, PaymentDtl, PaymentSrc, BillingDtl, BillingHdr};
use App\Models\TrdTire1\Master\{Partner, Material};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdTire1\PaymentService;
use App\Services\TrdTire1\Master\MasterService;
use Illuminate\Support\Facades\{Session, Log, DB};
use Exception;
use Carbon\Carbon;

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
    public $trType = "APP";
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
    }

    public function getTransactionCode()
    {
        $tax_doc_flag = !empty($this->inputs['tax_doc_flag']);
        $tr_type = $this->trType;

        $this->inputs['tr_code'] = PaymentHdr::generateTransactionId($tr_type, $tax_doc_flag);
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
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        Log::debug('ReceivablesSettlement rendering view', [
            'viewPath' => $renderRoute,
            'actionValue' => $this->actionValue,
            'isEditOrView' => $this->isEditOrView()
        ]);

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

        // Debug $codeBill to see its structure
        Log::debug('codeBill options', [
            'codeBill_count' => count($this->codeBill),
            'codeBill_sample' => array_slice($this->codeBill, 0, 5)
        ]);

        // Debug $codeBill to see its structure
        Log::debug('codeBill options', [
            'codeBill_count' => count($this->codeBill),
            'codeBill_sample' => array_slice($this->codeBill, 0, 5)
        ]);

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
            $this->inputs['partner_name'] = $this->object->partner->code;
            $this->inputs['tr_code'] = $this->object->tr_code;

            // Load details
            $this->loadDetails();
            $this->loadPaymentDetails();

            // Debug the loaded data
            Log::debug('Data loaded for edit/view', [
                'object_id' => $this->object->id,
                'input_details_count' => count($this->input_details),
                'input_payments_count' => count($this->input_payments),
                'input_details_sample' => array_slice($this->input_details, 0, 3)
            ]);
        }
        if (!$this->isEditOrView()) {
            $this->isPanelEnabled = "true";
        }
        if (!empty($this->inputs['tax_flag'])) {
            $this->onSOTaxChange();
        }

        $this->inputs['tr_date'] = Carbon::now()->format('d-m-Y');

    }

    public function onReset()
    {
        $this->reset('inputs', 'input_details', 'input_payments');
        $this->object = new PaymentHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_type'] = $this->trType;
        $this->inputs['curr_id'] = ConfigConst::CURRENCY_DOLLAR_ID;
        $this->inputs['wh_code'] = 18;
        $this->inputs['partner_id'] = 0;
    }

    #region Debt List Methods
    public function addItem()
    {
        try {
            $this->input_details[] = [
                'billhdrtr_code' => null,
                'tr_date' => null,
                'amt' => null,
                'amtbill' => 0,
            ];

            $this->dispatch('success', __('generic.string.add_item'));
        } catch (Exception $e) {
            Log::error('Error adding item: ' . $e->getMessage());
            $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
        }
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
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    public function onCodeChanged($key, $billHdrId)
    {
        $billHdr = BillingHdr::find($billHdrId); // Cari berdasarkan id

        if ($billHdr) {
            $this->input_details[$key]['tr_date'] = $billHdr->tr_date;
            $this->input_details[$key]['billhdrtr_code'] = $billHdr->id; // Simpan id, bukan tr_code

            $billingDetails = BillingDtl::where('trhdr_id', $billHdr->id)->get();
            if ($billingDetails->isNotEmpty()) {
                $totalAmt = $billingDetails->sum('amt');
                $this->input_details[$key]['amtbill'] = $totalAmt;
            } else {
                $this->input_details[$key]['amtbill'] = 0;
            }
        } else {
            $this->dispatch('error', __('Bill not found.'));
        }
    }

    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = PaymentDtl::GetByOrderHdr($this->object->id, $this->object->tr_type)
                ->orderBy('tr_seq')
                ->get();

            foreach ($this->object_detail as $key => $detail) {
                $amtbill = 0;
                $billhdrtr_code = null;
                $tr_date  = Carbon::now()->format('d-m-Y'); // Default to current date (d-m-Y)

                // Ambil BillingHdr id dari billdtl_id jika ada
                if ($detail->billdtl_id) {
                    $billingDtl = BillingDtl::find($detail->billdtl_id);
                    if ($billingDtl) {
                        $amtbill = $billingDtl->amt;
                        $billingHdr = BillingHdr::find($billingDtl->trhdr_id);
                        if ($billingHdr) {
                            $billhdrtr_code = $billingHdr->id; // Simpan id BillingHdr
                            $tr_date = $billingHdr->tr_date ? Carbon::parse($billingHdr->tr_date)->format('d-m-Y') : Carbon::now()->format('d-m-Y');
                        }
                    }
                }

                // Jika billhdrtr_code masih kosong, coba ambil dari billhdrtr_code (tr_code) di detail
                if (!$billhdrtr_code && !empty($detail->billhdrtr_code)) {
                    $billingHdr = BillingHdr::where('tr_code', $detail->billhdrtr_code)->first();
                    if ($billingHdr) {
                        $billhdrtr_code = $billingHdr->id;
                        $tr_date = $billingHdr->tr_date ? Carbon::parse($billingHdr->tr_date)->format('d-m-Y') : Carbon::now()->format('d-m-Y');
                        // Optionally, ambil amtbill juga
                        $billingDetails = BillingDtl::where('trhdr_id', $billingHdr->id)->get();
                        if ($billingDetails->isNotEmpty()) {
                            $amtbill = $billingDetails->sum('amt');
                        }
                    }
                }

                $this->input_details[$key] = [
                    'billhdrtr_code' => $billhdrtr_code, // Selalu id BillingHdr
                    'tr_date'        => $tr_date,
                    'amtbill'        => $amtbill,
                    'amt'            => $detail->amt ?? null,
                ];
            }
        }
    }
    #endregion

    #region Payment List Methods
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
        $this->activePaymentItemKey = null;
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
            $this->object_detail = PaymentSrc::where('trhdr_id', $this->object->id)
                ->where('tr_type', $this->object->tr_type)
                ->get();

            Log::debug('Loading payment sources', [
                'payment_id' => $this->object->id,
                'tr_type' => $this->object->tr_type,
                'payment_sources_count' => count($this->object_detail)
            ]);

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

                    Log::debug('Processed payment item', [
                        'key' => $key,
                        'pay_type_code' => $detail->pay_type_code,
                        'amt' => $detail->amt
                    ]);
                }
            } else {
                $this->input_payments = [];
                // Don't clear input_details here - it should be handled by loadDetails()
                // $this->input_details = [];
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

            // Siapkan data header dengan default values
            $headerData = [
                'id' => $this->objectIdValue,
                'tr_code' => $this->inputs['tr_code'],
                'tr_type' => $this->trType,
                'tr_date' => $this->inputs['tr_date'],
                'partner_id' => $this->inputs['partner_id'],
                'partner_code' => $this->inputs['partner_code'],
                'status_code' => Status::OPEN,
                'process_flag' => 'N',
                'wh_id' => $this->inputs['wh_id'] ?? 0, // berikan default value
                'wh_code' => $this->inputs['wh_code'] ?? '', // berikan default value
                'total_amt' => array_sum(array_column($this->input_details, 'amt')), // tambahkan total amount
            ];

            // Validasi input detail
            $hasValidDetails = false;
            foreach ($this->input_details as $detail) {
                if (!empty($detail['billhdrtr_code']) && !empty($detail['amt'])) {
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
                if (empty($detail['billhdrtr_code']) || empty($detail['amt'])) {
                    continue; // Skip detail yang tidak lengkap
                }
                $billingHdr = BillingHdr::find($detail['billhdrtr_code']); // Ambil berdasarkan id
                if ($billingHdr) {
                    $detailData[] = [
                        'tr_seq' => $key + 1,
                        'amt' => $detail['amt'],
                        'billhdrtr_code' => $billingHdr->tr_code, // Simpan tr_code ke database
                        'billhdrtr_type' => $billingHdr->tr_type,
                        'billhdrtr_id' => $billingHdr->id, // Simpan juga id BillingHdr
                    ];
                } else {
                    // Jika tidak ditemukan, skip
                    continue;
                }
            }

            // Log detail yang akan disimpan
            Log::debug('Detail data prepared for saving', [
                'input_details' => $this->input_details,
                'processed_details' => $detailData,
                'detail_count' => count($detailData)
            ]);


            // Siapkan data pembayaran
            $paymentData = [];
            foreach ($this->input_payments as $key => $payment) {
                $payType = ConfigConst::where('str1', $payment['pay_type_code'])->first();
                $paymentData[] = [
                    'tr_seq' => $key + 1,
                    'pay_type_id' => $payType ? $payType->id : null,
                    'pay_type_code' => $payment['pay_type_code'],
                    'bank_id' => $payType ? $payType->id : null,
                    'bank_code' => $payment['pay_type_code'],
                    'bank_note' => $payment['bank_note'] ?? '',
                    'amt' => $payment['amt_tunai'] ?? $payment['amt_giro'] ?? $payment['amt_trf'] ?? $payment['amt_advance'] ?? 0,
                    'bank_reff' => $this->getBankReff($payment),
                    'bank_duedt' => $this->getBankDate($payment),
                ];
            }

            // Check if detailData is empty after all processing
            if (empty($detailData)) {
                $this->dispatch('error', 'Tidak ada detail pembayaran yang valid untuk disimpan. Pastikan Anda telah memilih nota yang valid.');
                return;
            }

            // Tambahkan debug untuk melihat apa yang akan disimpan
            try {
                // Debugging sebelum menyimpan
                Log::debug('About to save payment data', [
                    'actionValue' => $this->actionValue,
                    'detailCount' => count($detailData),
                    'paymentCount' => count($paymentData),
                    'detailData' => $detailData,
                    'headerData' => $headerData
                ]);

                // Force trace to see exactly what's happening
                Log::debug(json_encode($detailData, JSON_PRETTY_PRINT));

                // Gunakan service untuk menyimpan data
                if ($this->actionValue == 'Create') {
                    // Tambahkan parameter keempat untuk advanceData (array kosong jika tidak ada)
                    $advanceData = [];
                    $result = $this->paymentService->addPayment($headerData, $detailData, $paymentData, $advanceData);
                    $this->objectIdValue = $result['header']->id;
                    // Update headerData dengan ID baru juga
                    $headerData['id'] = $result['header']->id;
                    Log::debug('After addPayment, updated objectIdValue and headerData', [
                        'new_id' => $this->objectIdValue,
                        'headerData' => $headerData
                    ]);
                    $this->actionValue = 'Edit';
                } else {
                    // Tambahkan parameter advanceData yang sekarang diperlukan
                    $advanceData = [];
                    // Gunakan parameter dan nama yang sudah diubah
                    $this->paymentService->modPayment($this->objectIdValue, $headerData, $detailData, $paymentData, $advanceData);
                }
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

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));

        Log::debug('ReceivablesSettlement rendering view', [
            'viewPath' => $renderRoute,
            'actionValue' => $this->actionValue,
            'isEditOrView' => $this->isEditOrView(),
            'input_details' => $this->input_details,
            'payment_details_count' => count($this->input_details),
        ]);

        if (count($this->input_details) > 0) {
            Log::debug('Data loaded for edit/view', [
                'object_id' => $this->objectIdValue,
                'input_details_count' => count($this->input_details),
                'input_payments_count' => count($this->input_payments),
                'input_details_sample' => array_slice($this->input_details, 0, 3)
            ]);
        }

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


    public function confirmSelection()
    {
        if (empty($this->selectedPartners)) {
            $this->dispatch('error', "Silakan pilih satu supplier terlebih dahulu.");
            return;
        }
        if (count($this->selectedPartners) > 1) {
            $this->dispatch('error', "Hanya boleh memilih satu supplier.");
            return;
        }
        $partner = Partner::find($this->selectedPartners[0]);

        if ($partner) {
            $this->inputs['partner_id'] = $partner->id;
            $this->inputs['partner_code'] = $partner->code; // Set partner_code
            $this->inputs['partner_name'] = $partner->code;
            $this->inputs['textareacustommer'] = $partner->name . "\n" . $partner->address . "\n" . $partner->city;

            // Set npwpOptions with data from JSON wp_details
            if ($partner->PartnerDetail && !empty($partner->PartnerDetail->wp_details)) {
                $wpDetails = $partner->PartnerDetail->wp_details;
                if (is_string($wpDetails)) {
                    $wpDetails = json_decode($wpDetails, true);
                }
                if (is_array($wpDetails) && !empty($wpDetails)) {
                    $this->npwpOptions = array_map(function ($item) {
                        return [
                            'label' => $item['npwp'],
                            'value' => $item['npwp'],
                        ];
                    }, $wpDetails);
                    // Automatically select the first npwpOption
                    $firstNpwpOption = $this->npwpOptions[0] ?? null;
                    if ($firstNpwpOption) {
                        $this->inputs['npwp_code'] = $firstNpwpOption['value'];
                        $this->onTaxPayerChanged();
                    }
                }
            }
            // Set shipOptions with data from JSON shipping_address
            if ($partner->PartnerDetail && !empty($partner->PartnerDetail->shipping_address)) {
                $shipDetail = $partner->PartnerDetail->shipping_address;
                if (is_string($shipDetail)) {
                    $shipDetail = json_decode($shipDetail, true);
                }
                if (is_array($shipDetail) && !empty($shipDetail)) {
                    $this->shipOptions = array_map(function ($item) {
                        return [
                            'label' => $item['name'],
                            'value' => $item['name'],
                        ];
                    }, $shipDetail);
                    // Automatically select the first shipOption
                    $firstShipOption = $this->shipOptions[0] ?? null;
                    if ($firstShipOption) {
                        $this->inputs['ship_to_name'] = $firstShipOption['value'];
                        $this->onShipToChanged();
                    }
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
}
