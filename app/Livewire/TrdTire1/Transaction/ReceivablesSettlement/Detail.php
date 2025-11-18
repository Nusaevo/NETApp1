<?php

namespace App\Livewire\TrdTire1\Transaction\ReceivablesSettlement;

use Exception;
use App\Enums\TrdTire1\Status;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Models\SysConfig1\ConfigSnum;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdTire1\Master\Partner;
use App\Livewire\Component\BaseComponent;
use App\Services\TrdTire1\BillingService;
use App\Services\TrdTire1\PaymentService;
use App\Models\TrdTire1\Master\PartnerBal;
use App\Services\SysConfig1\ConfigService;
use App\Services\TrdTire1\PartnerTrxService;
use App\Models\TrdTire1\Transaction\BillingHdr;
use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Models\TrdTire1\Transaction\PaymentAdv;
use App\Models\TrdTire1\Transaction\PaymentDtl;
use App\Models\TrdTire1\Transaction\PaymentHdr;
use App\Models\TrdTire1\Transaction\PaymentSrc;
use App\Services\TrdTire1\Master\MasterService;
use App\Models\TrdTire1\Transaction\PartnertrDtl;
use App\Models\TrdTire1\Transaction\PartnertrHdr;
use Carbon\Doctrine\CarbonType;

class Detail extends BaseComponent
{
    #region Constant Variables
    protected $paymentService;
    protected $partnerTrxService;
    protected $masterService;
    public $notaCount = 0;
    public $unpaidInvoiceCount = 0;
    public $suppliers = [];
    public $selectedPartners = [];
    public $warehouses;
    public $partners;
    public $transaction_id;
    public $payments;
    public $deletedItems = [];
    public $newItems = [];
    public $trType = "ARP";
    public $versionNumber = "0.0";
    public $object_detail;
    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];
    public $returnIds = [];
    public $currencyRate = 0;
    public $isPanelEnabled = "false";
    public $suratJalanCount = 0;
    public $partnerSearchText = '';
    public $partnerOptions = [];
    public $overPayment = 0;
    public $totalAmtSource = 0;
    public $totalAmtBilling = 0;
    public $totalAmtBillingNota = 0;
    public $totalAmtAdvance = 0;
    public $totalPiutangCustomer = 0;
    public $advanceOptions = [];
    public $paytypeOptions = [];
    public $chequeDepositCode = null;
    public $cashDepositCode = null;
    public $codeBill;
    public $inputs = [];
    public $input_details = [];
    public $input_advance = [];
    public $input_payments = [];
    public $isDataLoaded = false;
    public $selectedNotaId = null;
    public $notaQuery = '';

    public $rules  = [
        'inputs.partner_id' => 'required',
        'input_details.*.amt' => 'required',
    ];

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'delete' => 'delete',
        'onTrCodeChanged' => 'onTrCodeChanged', // listener untuk perubahan tr_code
    ];

    #endregion

    #region Populate Data methods

    public function boot()
    {
        $this->paymentService = app(PaymentService::class);
        $this->partnerTrxService = app(\App\Services\TrdTire1\PartnerTrxService::class);
    }

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.tr_code' => $this->trans('tr_code'),
            'inputs.partner_id' => $this->trans('partner_id'),
        ];

        $this->masterService = new MasterService();
        // $this->partners = $this->masterService->getCustomers();
        // $this->codeBill = $this->masterService->getBillCode();

        // Tidak otomatis populate advance items saat Create mode
        // Advance items akan muncul setelah partner dipilih melalui confirmSelection()

        if ($this->isEditOrView() && !empty($this->objectIdValue)) {
            // Load object berdasarkan objectIdValue jika ada
            $this->object = PaymentHdr::withTrashed()->find($this->objectIdValue);
            if ($this->object) {
                $this->loadPaymentData();
            } else {
                // Jika object tidak ditemukan, buat instance baru dan tampilkan error
                $this->object = new PaymentHdr();
                $this->dispatch('error', 'Data tidak ditemukan');
            }
        } else if ($this->actionValue === 'Edit' && !empty($this->inputs['tr_code'])) {
            // Untuk mode edit tanpa objectIdValue, cari berdasarkan tr_code
            $existingPayment = PaymentHdr::where('tr_code', $this->inputs['tr_code'])
                ->where('tr_type', $this->trType)
                ->first();

            if ($existingPayment) {
                $this->object = $existingPayment;
                $this->objectIdValue = $existingPayment->id;
                $this->loadPaymentData();
            }
        } else {
            // Mode Create
            $this->object = new PaymentHdr();
            $this->isPanelEnabled = "true";
            // Set default tr_date for Create mode if not already set
            if (empty($this->inputs['tr_date'])) {
                $this->inputs['tr_date'] = date('Y-m-d');
            }
        }

        $this->paytypeOptions = ConfigConst::where('const_group', 'TRX_PAYMENT_SRCS')
            ->orderBy('seq')->get()
            ->map(function ($paytype) {
                return [
                    'label' => $paytype->str2,
                    'value' => $paytype->str1,
                    'id' => $paytype->id,
                ];
            })->toArray();

        $this->loadPartnerOptions();

        // Ambil daftar partner_bals yang amt_adv != 0 untuk dropdown Advance
        $this->advanceOptions = PartnerBal::where('amt_adv', '!=', 0)->get()
            ->map(function ($bal) {
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

    private function loadPaymentData()
    {
        // Method untuk load data payment yang dipanggil dari berbagai tempat
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['partner_name'] = $this->object->partner ? ($this->object->partner->code . ' - ' . $this->object->partner->name) : '';
        $this->inputs['tr_code'] = $this->object->tr_code;

        // Load details
        $this->loadDetails();
        $this->loadPaymentDetails();

        // Hitung total piutang customer untuk mode edit
        $this->calculateTotalPiutangCustomer($this->object->partner_id);

        // Set flag bahwa data sudah dimuat untuk mode edit
        $this->isDataLoaded = true;
        $this->isPanelEnabled = "false"; // Disable panel karena sudah ada data

        // Dispatch event to refresh partner dropdown with loaded partner_id
        $this->dispatch('resetSelect2Dropdowns', [
            'partner_id' => $this->inputs['partner_id'] ?? null
        ]);
    }

    public function getTransactionCode()
    {
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

            // Generate the transaction code with format R+YEAR+ARP_LASTID
            // $year = date('y'); // Menggunakan 2 digit tahun terakhir
            $this->inputs['tr_code'] = 'R' .  $proposedTrId;
        }
    }

    public function onReset()
    {
        $this->chequeDepositCode = ConfigConst::getAppEnv('CHEQUE_DEPOSIT');
        $this->cashDepositCode = ConfigConst::getAppEnv('CASH_DEPOSIT');

        $this->reset('inputs', 'input_details', 'input_payments', 'input_advance');
        $this->object = new PaymentHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_type'] = $this->trType;
        $this->inputs['tr_date'] = date('Y-m-d');
        $this->inputs['curr_code'] = "IDR";
        $this->inputs['curr_id'] = app(ConfigService::class)->getConstIdByStr1('BASE_CURRENCY', $this->inputs['curr_code']);
        $this->inputs['curr_rate'] = 1.00;
        $this->inputs['partner_id'] = 0;
        $this->totalPiutangCustomer = 0;
        // dd($this->inputs);
    }

    public function onTrCodeChanged()
    {
        $trCode = trim($this->inputs['tr_code'] ?? '');

        if (empty($trCode)) {
            // Jika tr_code kosong, reset ke mode create tanpa object
            $this->resetToCreateModeInternal();
            // Pastikan isPanelEnabled benar-benar "true" untuk enable semua field
            $this->isPanelEnabled = "true";
            $this->dispatch('info', 'Mode telah direset ke Create. Silakan input data baru.');
            return;
        }

        try {
            // Cari PaymentHdr berdasarkan tr_code dan tr_type
            $existingPayment = PaymentHdr::where('tr_code', $trCode)
                ->where('tr_type', $this->trType)
                ->first();

            if ($existingPayment) {
                // Jika pelunasan ditemukan, load data untuk edit tanpa redirect
                $this->loadPaymentByTrCode($existingPayment);
                $this->dispatch('info', "Pelunasan {$trCode} ditemukan. Data telah dimuat untuk edit.");
            } else {
                // Jika tidak ditemukan, biarkan tr_code tetap di field untuk koreksi user
                $this->dispatch('warning', "Nomor pelunasan '{$trCode}' tidak ditemukan. Silakan periksa kembali atau gunakan nomor lain.");
            }
        } catch (Exception $e) {
            $this->dispatch('error', 'Terjadi kesalahan saat mencari pelunasan: ' . $e->getMessage());
            // Jangan hapus tr_code, biarkan user bisa perbaiki
        }
    }

    private function loadPaymentByTrCode($payment)
    {
        // Set mode ke edit dan load object
        $this->actionValue = 'Edit';
        $this->objectIdValue = $payment->id;
        $this->object = $payment;

        // Load semua data payment menggunakan method yang sama
        $this->loadPaymentData();
    }

    private function resetToCreateModeInternal()
    {
        // Reset ke mode create murni
        $this->actionValue = 'Create';
        $this->objectIdValue = null;
        $this->object = new PaymentHdr();
        $this->resetInputsToDefault();
        $this->isPanelEnabled = "true";

        // Reset flag data loaded
        $this->isDataLoaded = false;

        // Dispatch event untuk refresh Select2 dropdowns
        $this->dispatch('resetSelect2Dropdowns');
        $this->isPanelEnabled = false;
    }

    private function resetInputsToDefault()
    {
        // Reset semua inputs ke nilai default
        $this->inputs = populateArrayFromModel(new PaymentHdr());
        $this->inputs['tr_type'] = $this->trType;
        $this->inputs['tr_date'] = date('Y-m-d');
        $this->inputs['curr_code'] = "IDR";
        $this->inputs['curr_id'] = app(ConfigService::class)->getConstIdByStr1('BASE_CURRENCY', $this->inputs['curr_code']);
        $this->inputs['curr_rate'] = 1.00;
        $this->inputs['partner_id'] = 0;
        $this->inputs['partner_code'] = '';
        $this->inputs['partner_name'] = '';
        $this->inputs['tr_code'] = '';

        // Reset detail items
        $this->input_details = [];
        $this->input_payments = [];
        $this->input_advance = [];

        // Reset totals
        $this->totalPiutangCustomer = 0;
        $this->totalAmtAdvance = 0;
        $this->totalAmtBillingNota = 0;
        $this->totalAmtBilling = 0;
        $this->totalAmtSource = 0;
        $this->overPayment = 0;

        $this->loadPartnerOptions();
        $this->notaQuery = '';
        $this->selectedNotaId = null;

        $this->dispatch('resetSelect2Dropdowns');
    }
    private function loadPartnerOptions()
    {
        $this->partnerOptions = Partner::where('grp', Partner::BANK)
            ->orderBy('name')->get()
            ->map(function ($partner) {
                return [
                    'label' => $partner->name,
                    'value' => $partner->name,
                    'id' => $partner->id,
                ];
            })->toArray();
    }

    // public function deleteItem($index)
    // {
    //     try {
    //         if (!isset($this->input_details[$index])) {
    //             throw new Exception(__('generic.error.delete_item', ['message' => 'Item not found.']));
    //         }

    //         // Ambil data nota yang akan dihapus
    //         $deletedItem = $this->input_details[$index];

    //         // Hapus nota dari array
    //         array_splice($this->input_details, $index, 1);

    //         // Jika ada amt_adjustment, hapus juga data partnertrx yang terkait
    //         if (!empty($deletedItem['amt_adjustment']) && $deletedItem['amt_adjustment'] > 0) {
    //             $this->deletePartnerTrxForAdjustment($deletedItem);
    //         }

    //         $this->dispatch('success', __('generic.string.delete_item'));
    //     } catch (Exception $e) {
    //         $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
    //     }
    // }

    // public function onCodeChanged($key, $billHdrId)
    // {
    //     $billHdr = BillingHdr::find($billHdrId); // Cari berdasarkan id

    //     if ($billHdr) {
    //         $this->input_details[$key]['tr_date'] = $billHdr->tr_date;
    //         $this->input_details[$key]['billhdrtr_code'] = $billHdr->id; // Simpan id, bukan tr_code

    //         // Ambil langsung dari amt pada BillingHdr
    //         $this->input_details[$key]['amtbill'] = $billHdr->amt ?? 0;
    //     } else {
    //         $this->dispatch('error', __('Bill not found.'));
    //     }
    // }

    private function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = PaymentDtl::where('trhdr_id', $this->object->id)
                ->orderBy('tr_seq')
                ->get();

            foreach ($this->object_detail as $key => $detail) {
                $this->input_details[] = $detail->toArray();

                $billingHdr = BillingHdr::find($detail->billhdr_id);
				if ($billingHdr) {
					// Get tr_date from order_hdrs instead of billing_hdrs
					$orderHdr = OrderHdr::where('tr_code', $billingHdr->tr_code)->where('tr_type', 'SO')->first();
					$trDate = $orderHdr ? $orderHdr->tr_date : $billingHdr->tr_date;
					$due_date = Carbon::parse($trDate)->addDays($billingHdr->payment_due_days)->format('Y-m-d');
					$this->input_details[$key]['due_date'] = $due_date;
					$baseAmt = ($billingHdr->amt + ($billingHdr->amt_shipcost ?? 0)) - ($billingHdr->amt_reff ?? 0);
					$this->input_details[$key]['amtbill'] =  $baseAmt;
					$this->input_details[$key]['outstanding_amt'] = $baseAmt + $detail->amt;
                }

                $this->input_details[$key]['is_selected'] = false;
                $cnData = PartnertrDtl::where('tr_type', '=', 'ARA')
                    ->where('tr_code', '=', $detail->tr_code)
                    ->where('partnerbal_id', '=', $detail->partnerbal_id)
                    ->first();
                // dd($detail, $cnData);

                if (!$cnData) {
                    $this->input_details[$key]['is_lunas'] = false;
                    $this->input_details[$key]['amt_adjustment'] = 0;
                } else {
                    $this->input_details[$key]['is_lunas'] = true;
                    $this->input_details[$key]['amt_adjustment'] = $cnData->amt;
                    $this->input_details[$key]['outstanding_amt'] -= $cnData->amt;
                }
            }

            // Set flag bahwa data sudah dimuat
            $this->isDataLoaded = true;
        }
    }

    private function loadPaymentDetails()
    {
        if (!empty($this->object)) {
            // Load payment sources
            $this->input_payments = PaymentSrc::where('trhdr_id', $this->object->id)
                ->orderBy('tr_seq')
                ->get()->toArray();

            // Load advance (lebih bayar) yang sudah pernah digunakan
            $this->input_advance = PaymentAdv::where('trhdr_id', $this->object->id)
                ->whereColumn('reff_id', '<>', 'id')
                ->orderBy('tr_seq')
                ->get()->toArray();
            foreach ($this->input_advance as $key => &$adv) {
                $adv['amt'] = abs($adv['amt']);
                $adv['amtAdvBal'] = abs($adv['amt']);
                $partnerBal = PartnerBal::find($adv['partnerbal_id']);
                $adv['descr'] = $partnerBal->descr;
            }
            unset($adv);

            // dd($this->input_advance,$this->input_payments);
            $this->updateTotalAmt();
        }
    }

    public function addPaymentItem()
    {
        $this->input_payments[] = populateArrayFromModel(new PaymentSrc());
        $key = count($this->input_payments) - 1;
        $this->input_payments[$key]['bank_code'] = '';
        $this->input_payments[$key]['bank_reff'] = '';
        $this->input_payments[$key]['amt'] = 0;
        $this->input_payments[$key]['bank_duedt'] = date('Y-m-d');
    }

    public function deletePaymentItem($index)
    {
        if (!isset($this->input_payments[$index])) {
            throw new Exception(__('generic.error.delete_item', ['message' => 'Item not found.']));
        }
        if (!empty($this->objectIdValue) && isset($this->input_payments[$index]['id'])) {
            $this->deletedItems[] = $this->input_payments[$index]['id'];
        }

        unset($this->input_payments[$index]);
        $this->input_payments = array_values($this->input_payments);
    }

    // public function delete()
    // {
    //     try {
    //         if ($this->object->isOrderCompleted()) {
    //             $this->dispatch('warning', 'Nota ini tidak bisa edit, karena status sudah Completed');
    //             return;
    //         }

    //         if (!$this->object->isOrderEnableToDelete()) {
    //             $this->dispatch('warning', 'Nota ini tidak bisa delete, karena memiliki material yang sudah dijual.');
    //             return;
    //         }

    //         if (isset($this->object->status_code)) {
    //             $this->object->status_code =  Status::NONACTIVE;
    //         }
    //         $this->object->save();
    //         $this->object->delete();
    //         $messageKey = 'generic.string.delete';
    //         $this->dispatch('success', __($messageKey));
    //     } catch (Exception $e) {
    //         $this->dispatch('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
    //     }

    //     return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
    // }

    public function delete()
    {
        try {
            if (!isset($this->object->id) || empty($this->object->id)) {
                throw new Exception('Nomor Pelunasan tidak ada');
            }

            $this->paymentService->delPayment($this->object->id);

            $partnertrHdr = PartnertrHdr::where('tr_type', '=', 'ARA')
                ->where('tr_code', '=', $this->object->tr_code)
                ->first();
            if ($partnertrHdr) {
                // dd($partnertrHdr);
                $this->partnerTrxService->delPartnerTrx($partnertrHdr['id']);
            }

            $this->dispatch('success', ('Data berhasil terhapus'));
            return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
        } catch (\Exception $e) {
            $this->dispatch('error', __('generic.error.delete', [
                'message' => $e->getMessage()
            ]));
        }
    }

    public function onValidateAndSave()
    {

        // Validate partner wajib dipilih
        if (empty($this->inputs['partner_id']) || empty($this->inputs['partner_code'])) {
            $this->dispatch('error', 'Partner wajib dipilih!');
            return;
        }

        // Generate transaction code untuk mode Create
        if ($this->actionValue == 'Create') {
            $this->getTransactionCode();
        }

        // Prepare header data
        $this->inputs['amt_srcs'] = array_sum(array_column($this->input_payments, 'amt')) ?? 0;
        $this->inputs['amt_dtls'] = array_sum(array_column($this->input_details, 'amt')) ?? 0;
        $this->inputs['amt_advs'] = array_sum(array_column($this->input_advance, 'amt')) ?? 0;
        $this->inputs['amt_advs'] += $this->overPayment; // Tambahkan overpayment jika ada
        $headerData = $this->inputs;
        // Set status_code untuk mode Create
        if ($this->actionValue === 'Create') {
            $headerData['status_code'] = Status::OPEN;
        }

        // dd($this->input_details);
        // Prepare detail data
        $detailData = [];
        foreach ($this->input_details as $key => &$detail) {
            if ($detail['amt'] == 0) {
                unset($detail);
                continue;
            }
            $detail['tr_type'] = $headerData['tr_type'];
            $detail['tr_code'] = $headerData['tr_code'];
            $detail['amt_base'] = $detail['amt'] / ($headerData['curr_rate'] ?? 1);
            $detailData[] = $detail;
            if ($this->actionValue === 'Create') {
                $detailData[$key]['status_code'] = Status::OPEN;
            }
        }
        unset($detail);
        // Validate detail data tidak boleh kosong
        if (empty($detailData)) {
            $this->dispatch('error', 'Tidak ada detail pembayaran yang valid untuk disimpan. Pastikan Anda telah memilih nota yang valid.');
            return;
        }

        // Prepare payment data
        $paymentData = [];
        foreach ($this->input_payments as $key => &$payment) {
            $payment['tr_type'] = $headerData['tr_type'] . 'S';
            $payment['tr_code'] = $headerData['tr_code'];
            $payment['amt_base'] = $payment['amt'] / ($headerData['curr_rate'] ?? 1);
            $paymentData[] = $payment;
            if ($this->actionValue === 'Create') {
                $paymentData[$key]['status_code'] = Status::OPEN;
            }
        }
        unset($payment);

        // Prepare advance data
        $advanceData = [];
        foreach ($this->input_advance as $key => &$advance) {
            if (empty($advance['partnerbal_id']) || empty($advance['amt'])) {
                unset($detail);
                continue;
            }
            $advance['tr_type'] = $headerData['tr_type'] . 'A';
            $advance['tr_code'] = $headerData['tr_code'];
            // $advance['amt'] = -abs($advance['amt']);
            // $advance['amt_base'] = $advance['amt'] / ($headerData['curr_rate'] ?? 1);
            $advance['adv_type_code'] = 'ARADVPAY';
            $advance['adv_type_id'] = app(ConfigService::class)->getConstIdByStr1('TRX_PAYMENT_TYPE_ADVS', 'ARADVPAY');
            $advanceData[] = $advance;
            if ($this->actionValue === 'Create') {
                $advanceData[$key]['status_code'] = Status::OPEN;
            }
        }
        unset($advance);

        // dd($this->inputs, $headerData,
        //     $this->input_payments,$paymentData,
        //     $this->input_details, $detailData,
        //     $this->input_advance, $advanceData);

        // Process save dengan error handling
        try {
            // Set ID untuk mode Edit
            if ($this->actionValue == 'Edit') {
                $headerData['id'] = $this->objectIdValue;
            }

            // Save payment
            $result = $this->paymentService->savePayment($headerData, $detailData, $paymentData, $advanceData, $this->overPayment);

            if (!$result || !isset($result->id)) {
                throw new Exception('Failed to save payment: Invalid result returned from addPayment service');
            }

            // Update state
            $this->objectIdValue = $result->id;
            $this->actionValue = 'Edit';

            // Set status_code langsung pada object jika baru dibuat
            if ($result && $this->actionValue === 'Edit') {
                // Pastikan status_code tidak null
                if (empty($result->status_code)) {
                    $result->status_code = Status::OPEN;
                    $result->save();
                }
            }

            // Handle adjustment
            $this->saveCreditNote($headerData, $detailData);

            // Success response
            $this->dispatch('disable-onbeforeunload');
            $this->dispatch('success', ('Data berhasil disimpan.'));

            return redirect()->route($this->appCode . '.Transaction.ReceivablesSettlement.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->objectIdValue)
            ]);
        } catch (Exception $e) {
            $this->dispatch('error', 'Error saving payment: ' . $e->getMessage());
            return;
        }
    }

    private function saveCreditNote($headerData, $detailData)
    {
        $partnerHdrData = [
            'tr_date' => $headerData['tr_date'],
            'tr_type' => 'ARA',
            'tr_code' => $headerData['tr_code'],
            'tr_desc' => 'Adjustment dari pelunasan ' . $headerData['tr_code'],
            'curr_id' => $headerData['curr_id'],
            'curr_code' => $headerData['curr_code'],
            'curr_rate' => $headerData['curr_rate'],
        ];
        if ($this->actionValue === 'Create') {
            $partnerHdrData['status_code'] = Status::OPEN;
        }

        $partnerDtlData = [];
        foreach ($detailData as $detail) {
            $partnerDtlData[] = [
                // 'trhdr_id' => $detail['partnertr_id'],
                'tr_type' => $partnerHdrData['tr_type'],
                'tr_code' => $partnerHdrData['tr_code'],
                'partnerbal_id' => $detail['partnerbal_id'],
                'partner_id' => $headerData['partner_id'],
                'partner_code' => $headerData['partner_code'],
                'reff_id' => $detail['billhdr_id'],
                'reff_type' => $detail['billhdrtr_type'],
                'reff_code' => $detail['billhdrtr_code'],
                'amt' => $detail['amt_adjustment'],
                'tr_descr' => 'Adjustment dari pelunasan ' . $headerData['tr_code'] . ' - invoice ' . $detail['billhdrtr_code'],
                // 'partnertr_id' => $detail['id'],
            ];
        }

        $partnerHdrData['amt'] = array_sum(array_column($partnerDtlData, 'amt'));
        $partnerHdrData['amt_base'] = $partnerHdrData['amt'] / $partnerHdrData['curr_rate'];

        // dd($partnerHdrData,$partnerDtlData);
        $this->partnerTrxService->saveAutoAraFromPayment($partnerHdrData, $partnerDtlData);

        Log::info('Data Credit Note pelunasan', [
            'header' => $partnerHdrData,
            'details' => $partnerDtlData
        ]);
    }

    public function onNotaSelectionChanged()
    {
        if (!$this->isDataLoaded || $this->actionValue === 'Edit') {
            return;
        }

        // Reset amount untuk semua nota
        foreach ($this->input_details as $key => $detail) {
            $this->input_details[$key]['amt'] = 0;
        }

        // Reset advance amount juga
        foreach ($this->input_advance as $key => $advance) {
            $this->input_advance[$key]['amt'] = 0;
        }

        // Update total amount
        $this->updateTotalAmt();

        // Tidak otomatis melakukan auto pelunasan
        // User harus klik "Auto Pelunasan" secara manual
    }

    public function payItem()
    {
        $selectedNotes = 0;
        foreach ($this->input_details as $key => $detail) {
            if (!empty($detail['is_selected'])) {
                $selectedNotes++;
            }
        }

        if ($selectedNotes > 0) {
            $this->paySelectedNotas();
        } else {
            $this->payAllNotasByDueDate();
        }
    }

    private function paySelectedNotas()
    {
        $totalAdvance = 0;
        foreach ($this->input_advance as $advance) {
            if (!empty($advance['amtAdvBal']) && is_numeric($advance['amtAdvBal'])) {
                $totalAdvance += (float)$advance['amtAdvBal'];
            }
        }

        $totalPayment = 0;
        foreach ($this->input_payments as $payment) {
            $totalPayment += is_numeric($payment['amt']) ? (float)$payment['amt'] : 0;
        }

        $totalAvailable = $totalAdvance + $totalPayment;

        $selectedDetails = [];
        foreach ($this->input_details as $key => $detail) {
            if (!empty($detail['is_selected'])) {
                $selectedDetails[] = [
                    'key' => $key,
                    'due_date' => isset($detail['due_date']) ? strtotime($detail['due_date']) : 0,
                    'outstanding_amt' => (isset($detail['outstanding_amt']) && is_numeric($detail['outstanding_amt'])) ? (float)$detail['outstanding_amt'] : 0,
                ];
            }
        }
        usort($selectedDetails, function ($a, $b) {
            return $a['due_date'] <=> $b['due_date'];
        });

        foreach ($this->input_details as $key => $detail) {
            $this->input_details[$key]['amt'] = 0;
        }
        foreach ($this->input_advance as $key => $advance) {
            $this->input_advance[$key]['amt'] = 0;
        }

        $remaining = $totalAvailable;
        $advanceUsed = 0;

        foreach ($selectedDetails as $item) {
            $key = $item['key'];
            $outstanding = $item['outstanding_amt'];
            if ($remaining <= 0) {
                $this->input_details[$key]['amt'] = 0;
            } else {
                $toPay = min($outstanding, $remaining);
                $this->input_details[$key]['amt'] = round($toPay, 2);
                $remaining -= $toPay;

                // Track berapa advance yang  digunakan
                if ($advanceUsed < $totalAdvance) {
                    $advanceForThisNote = min($toPay, $totalAdvance - $advanceUsed);
                    $advanceUsed += $advanceForThisNote;
                }
            }
        }

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

        // Pengecekan: jika total piutang = total bayar, set toggle adjustment menjadi false
        $totalPiutang = 0;
        $totalBayar = 0;

        foreach ($selectedDetails as $item) {
            $key = $item['key'];
            $totalPiutang += $item['outstanding_amt'];
            $totalBayar += $this->input_details[$key]['amt'];
        }

        // Jika total piutang sama dengan total bayar, set toggle adjustment menjadi false
        if (abs($totalPiutang - $totalBayar) < 0.01) { // Gunakan toleransi kecil untuk perbandingan float
            foreach ($selectedDetails as $item) {
                $key = $item['key'];
                $this->input_details[$key]['is_lunas'] = false;
                $this->input_details[$key]['amt_adjustment'] = 0;
            }
        }

        $this->updateTotalAmt();
        // $this->dispatch('success', 'Pembayaran berhasil dibagi ke nota yang dipilih menggunakan advance dan payment sesuai outstanding dan urutan jatuh tempo.');
    }

    // Method untuk membayar semua nota berdasarkan due date (logika original)
    private function payAllNotasByDueDate()
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
            $totalPayment += is_numeric($payment['amt']) ? (float)$payment['amt'] : 0;
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
        usort($details, function ($a, $b) {
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

        // Pengecekan: jika total piutang = total bayar, set toggle adjustment menjadi false
        $totalPiutang = 0;
        $totalBayar = 0;

        foreach ($details as $item) {
            $key = $item['key'];
            $totalPiutang += $item['outstanding_amt'];
            $totalBayar += $this->input_details[$key]['amt'];
        }

        // Jika total piutang sama dengan total bayar, set toggle adjustment menjadi false
        if (abs($totalPiutang - $totalBayar) < 0.01) { // Gunakan toleransi kecil untuk perbandingan float
            foreach ($details as $item) {
                $key = $item['key'];
                $this->input_details[$key]['is_lunas'] = false;
                $this->input_details[$key]['amt_adjustment'] = 0;
            }
        } else {
            $this->dispatch('warning', 'Total piutang dan total pembayaran tidak sama, jangan lupa lakukan adjustment.');
        }

        $this->updateTotalAmt();
        // $this->dispatch('success', 'Pembayaran berhasil dibagi ke semua nota sesuai urutan jatuh tempo yang paling dekat.');
    }

    public function isEditOrView()
    {
        return in_array($this->actionValue, ['Edit', 'View']);
    }

    // public function updating($property, $value)
    // {
    //     // Misalnya log field mana yang sedang diupdate
    //     logger("Sedang mengupdate field: {$property} dengan nilai: {$value}");
    // }

    // // Akan terpanggil setelah properti diupdate
    // public function updated($property, $value)
    // {
    //     logger("Field {$property} berhasil diupdate menjadi: {$value}");
    // }

    // // Khusus untuk field name
    // public function updatingName($value)
    // {
    //     logger("Sedang update NAME: {$value}");
    // }

    // public function updatedName($value)
    // {
    //     logger("NAME berhasil diupdate jadi: {$value}");
    // }

    public function onPartnerChanged()
    {
        $partner = Partner::find($this->inputs['partner_id']);

        if ($partner) {
            $this->inputs['partner_id'] = $partner->id;
            $this->inputs['partner_code'] = $partner->code; // Set partner_code
            $this->inputs['partner_name'] = $partner->code . ' - ' . $partner->name;

            $billingService = app(BillingService::class);
            $outstandingBills = collect($billingService->getOutstandingBillsByPartner($partner->id));
            $outstandingBills = $outstandingBills->sortBy('due_date')->values();

            // Set count of unpaid invoices
            $this->unpaidInvoiceCount = $outstandingBills->count();

            // Clear existing nota details - user will add manually via dropdown
            $this->input_details = [];

            if (empty($outstandingBills)) {
                $this->dispatch('warning', "Tidak ada nota yang dilunasi untuk customer ini, namun saldo advance tetap bisa digunakan.");
            } else {
                // Show notification about unpaid invoices count
                $this->dispatch('info', "Customer {$partner->name} memiliki {$this->unpaidInvoiceCount} nota yang belum dilunasi.");
            }

            // Pada mode Create, populate advance dari PartnerBal jika ada (hanya saat partner dipilih)
            if (!$this->isEditOrView()) {
                $this->input_advance = [];
                $partnerAdvances = PartnerBal::where('partner_id', $partner->id)
                    ->where('amt_adv', '!=', 0)
                    ->get();
                foreach ($partnerAdvances as $key => $partnerBal) {
                    $this->input_advance[] = populateArrayFromModel(new PaymentAdv());
                    $this->input_advance[$key]['partnerbal_id'] = $partnerBal->id;
                    $this->input_advance[$key]['partner_id'] = $partnerBal->partner_id;
                    $this->input_advance[$key]['partner_code'] = $partnerBal->partner_code;
                    $this->input_advance[$key]['reff_id'] = $partnerBal->reff_id;
                    $this->input_advance[$key]['reff_type'] = $partnerBal->reff_type;
                    $this->input_advance[$key]['reff_code'] = $partnerBal->reff_code;
                    $this->input_advance[$key]['descr'] = $partnerBal->descr;
                    $this->input_advance[$key]['amtAdvBal'] = (int)round($partnerBal->amt_adv);
                    $this->input_advance[$key]['amt'] = 0;
                }
            }

            // Hitung total piutang customer yang belum dibayar
            $this->calculateTotalPiutangCustomer($partner->id);

            // Set flag bahwa data sudah dimuat
            $this->isDataLoaded = true;

            // Update nota query untuk dropdown search
            $this->updateNotaQuery($partner->id);
        }
        // dd($this->input_details, $this->input_advance, $this->inputs);
    }

    private function updateNotaQuery($partnerId)
    {
        // Get already selected nota IDs
        $selectedNotaIds = collect($this->input_details)->pluck('billhdr_id')->filter()->toArray();
        $excludeCondition = '';

        if (!empty($selectedNotaIds)) {
            $excludeIds = implode(',', array_map('intval', $selectedNotaIds));
            $excludeCondition = "AND bh.id NOT IN ({$excludeIds})";
        }

        $this->notaQuery = "SELECT
                           sub.id,
                           sub.tr_code,
                           sub.tr_date,
                           sub.amt,
                           sub.amt_reff,
                           sub.outstanding_amt,
                           sub.outstanding_amt_formatted,
                           sub.due_date
                           FROM (
                               SELECT
                                   bh.id,
                                   bh.tr_code,
                                   COALESCE(oh.tr_date, bh.tr_date) as tr_date,
                                   bh.amt,
                                   bh.amt_reff,
                                   (bh.amt + COALESCE(bh.amt_shipcost, 0) - COALESCE(bh.amt_reff, 0)) outstanding_amt,
                                   TO_CHAR((bh.amt + COALESCE(bh.amt_shipcost, 0) - COALESCE(bh.amt_reff, 0)), 'FM999,999,999,999,990') outstanding_amt_formatted,
                                   TO_CHAR(COALESCE(oh.tr_date, bh.tr_date) + (COALESCE(bh.payment_due_days, 0) || ' days')::interval, 'DD-MM-YYYY') due_date
                               FROM billing_hdrs bh
                               LEFT JOIN order_hdrs oh ON bh.tr_code = oh.tr_code AND oh.tr_type = 'SO'
                               WHERE
                                   bh.partner_id = {$partnerId}
                                   AND bh.deleted_at IS NULL
                                   AND (bh.amt + COALESCE(bh.amt_shipcost, 0) - COALESCE(bh.amt_reff, 0)) > 0
                                   {$excludeCondition}
                           ) sub ORDER BY tr_code,outstanding_amt,due_date DESC";
    }

    public function toggleLunas($key)
    {
        if (isset($this->input_details[$key])) {
            $isLunas = $this->input_details[$key]['is_lunas'] ?? false;
            $outstanding_amt = isset($this->input_details[$key]['outstanding_amt']) ? (float)$this->input_details[$key]['outstanding_amt'] : 0;
            $amt = isset($this->input_details[$key]['amt']) ? (float)$this->input_details[$key]['amt'] : 0;

            if ($isLunas) {
                // Jika toggle aktif (lunas), isi amt_adjustment dengan selisih outstanding_amt - amt
                // Ini akan membuat total bayar = outstanding_amt (lunas)
                $this->input_details[$key]['amt_adjustment'] = $amt - $outstanding_amt;
            } else {
                // Jika toggle tidak aktif (belum lunas), hapus amt_adjustment
                $this->input_details[$key]['amt_adjustment'] = 0;
            }
        }
        $this->updateTotalAmt();
    }

    // Method untuk update summary footer
    private function updateTotalAmt()
    {
        // Total advance yang digunakan
        $this->totalAmtAdvance = array_sum(array_column($this->input_advance, 'amt')) ?? 0;
        $this->totalAmtBillingNota = array_sum(array_column($this->input_details, 'outstanding_amt')) ?? 0;
        $this->totalAmtBilling = array_sum(array_column($this->input_details, 'amt')) ?? 0;
        $this->totalAmtSource = array_sum(array_column($this->input_payments, 'amt')) ?? 0;
        $this->overPayment = $this->totalAmtAdvance + $this->totalAmtSource - $this->totalAmtBilling;
    }

    /**
     * Hitung total piutang customer yang belum dibayar
     */
    private function calculateTotalPiutangCustomer($partnerId)
    {
        if (empty($partnerId)) {
            $this->totalPiutangCustomer = 0;
            return;
        }

        $billingService = app(BillingService::class);
        $outstandingBills = $billingService->getOutstandingBillsByPartner($partnerId);

        $basePiutang = $outstandingBills->sum('outstanding_amt');

        // Jika mode Edit, tambahkan total pembayaran yang sudah ada
        if ($this->isEditOrView()) {
            $this->totalPiutangCustomer = $basePiutang + $this->totalAmtSource;
        } else {
            $this->totalPiutangCustomer = $basePiutang;
        }
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

    public function onBankCodeChanged($key, $bankCode)
    {

        $this->input_payments[$key]['bank_id'] =
            $this->partnerOptions[array_search($bankCode, array_column($this->partnerOptions, 'label'))]['id'] ?? null;

        if ($bankCode == $this->chequeDepositCode) {
            $payTypeCode = 'GIRO';
        } elseif ($bankCode == $this->cashDepositCode) {
            $payTypeCode = 'CASH';
        } else {
            $payTypeCode = 'TRANSFER';
        }

        $this->input_payments[$key]['pay_type_code'] = $payTypeCode;
        $this->input_payments[$key]['pay_type_id'] =
            $this->paytypeOptions[array_search($payTypeCode, array_column($this->paytypeOptions, 'value'))]['id'] ?? 0;

    }

    public function onNotaSelected()
    {
        if (empty($this->selectedNotaId)) {
            return;
        }

        // Cek apakah nota sudah ada di input_details
        $existingNota = collect($this->input_details)->firstWhere('billhdr_id', $this->selectedNotaId);
        if ($existingNota) {
            $this->dispatch('warning', 'Nota ini sudah ada dalam daftar pembayaran.');
            $this->selectedNotaId = null;
            return;
        }

        // Ambil data nota dari database
        $billingHdr = BillingHdr::find($this->selectedNotaId);
        if (!$billingHdr) {
            $this->dispatch('error', 'Nota tidak ditemukan.');
            $this->selectedNotaId = null;
            return;
        }

		// Hitung outstanding amount
		$outstandingAmt = ($billingHdr->amt + ($billingHdr->amt_shipcost ?? 0)) - ($billingHdr->amt_reff ?? 0);
        if ($outstandingAmt <= 0) {
            $this->dispatch('warning', 'Nota ini sudah lunas atau tidak memiliki outstanding amount.');
            $this->selectedNotaId = null;
            return;
        }

        // Hitung due date - get tr_date from order_hdrs instead of billing_hdrs
        $orderHdr = OrderHdr::where('tr_code', $billingHdr->tr_code)->where('tr_type', 'SO')->first();
        $trDate = $orderHdr ? $orderHdr->tr_date : $billingHdr->tr_date;
        $dueDate = Carbon::parse($trDate)->addDays($billingHdr->payment_due_days ?? 0)->format('Y-m-d');

        // Tambahkan nota ke input_details
        $newDetail = populateArrayFromModel(new PaymentDtl());
        $newDetail['billhdr_id'] = $billingHdr->id;
        $newDetail['billhdrtr_type'] = $billingHdr->tr_type;
        $newDetail['billhdrtr_code'] = $billingHdr->tr_code;
        $newDetail['due_date'] = $dueDate;
        $newDetail['amt'] = 0;
        $newDetail['amtbill'] = $outstandingAmt;
        $newDetail['outstanding_amt'] = $outstandingAmt;
        $newDetail['is_selected'] = false;
        $newDetail['is_lunas'] = false;
        $newDetail['amt_adjustment'] = 0;

        $this->input_details[] = $newDetail;

        // Reset selectedNotaId
        $this->selectedNotaId = null;

        // Update query to exclude newly selected nota
        if (!empty($this->inputs['partner_id'])) {
            $this->updateNotaQuery($this->inputs['partner_id']);
        }

        $this->dispatch('success', 'Nota berhasil ditambahkan ke daftar pembayaran.');
    }

    public function deleteNotaItem($index)
    {
        try {
            if (!isset($this->input_details[$index])) {
                throw new Exception('Nota item not found.');
            }

            // Ambil data nota yang akan dihapus untuk logging
            $deletedNota = $this->input_details[$index];

            // Hapus nota dari array
            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            // Update query to include deleted nota back in dropdown
            if (!empty($this->inputs['partner_id'])) {
                $this->updateNotaQuery($this->inputs['partner_id']);
            }

            $this->dispatch('success', 'Nota ' . ($deletedNota['billhdrtr_code'] ?? '') . ' berhasil dihapus dari daftar pembayaran.');
        } catch (Exception $e) {
            $this->dispatch('error', 'Error menghapus nota: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
