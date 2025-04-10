<?php

namespace App\Livewire\TrdTire1\Transaction\ProsesGt;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\{BillingDtl, BillingHdr, DelivDtl, DelivHdr, OrderHdr, OrderDtl};
use App\Models\TrdTire1\Master\{Partner, Material};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdTire1\Master\MasterService;
use Illuminate\Support\Facades\{Session, DB};
use Exception;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $inputs = [];
    public $SOTax = [];
    public $SOSend = [];
    public $paymentTerms = [];
    public $suppliers = [];
    public $partnerSearchText = '';
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
    public $trType = "SO";
    public $versionNumber = "0.0";

    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];

    public $returnIds = [];
    public $currencyRate = 0;
    public $npwpOptions = [];
    public $shipOptions = [];
    protected $masterService;
    public $isPanelEnabled = "false";
    public $notaCount = 0; // x: jumlah nota jual dicetak
    public $suratJalanCount = 0; // y: jumlah surat jalan dicetak

    public $payer = "true"; // Ensure this line is present to define the payer property

    public $rules  = [
        'inputs.tr_code' => 'required',
        'inputs.partner_id' => 'required',
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

    public function getTransactionCode()
    {
        if (!isset($this->inputs['sales_type']) || !isset($this->trType)) {
            $this->dispatch('warning', 'Tipe Kendaraan dan Jenis Transaksi harus diisi');
            return;
        }

        $sales_type = $this->inputs['sales_type'];
        $tax_doc_flag = !empty($this->inputs['tax_doc_flag']); // Konversi ke boolean
        $tr_type = $this->trType;

        $this->inputs['tr_code'] = OrderHdr::generateTransactionId($sales_type, $tr_type, $tax_doc_flag);
        // dd($this->inputs['tr_code']);
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
            $totalAmount = (float)$this->total_amount;

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

            $this->total_dpp = rupiah(round($dpp, 2));
            $this->total_tax = rupiah(round($ppn, 2));

            $this->dispatch('updateDPP', $this->total_dpp);
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

    public function onTaxDocFlagChanged()
    {
        $this->payer = !empty($this->inputs['tax_doc_flag']) ? "true" : "false";
        $this->dispatch('updateTaxPayerEnabled', $this->payer); // Ensure the UI is updated
    }

    public function onPaymentTermChanged()
    {
        if (!empty($this->inputs['payment_term_id'])) {
            $paymentTerm = ConfigConst::find($this->inputs['payment_term_id']);
            if ($paymentTerm) {
                $dueDays = $paymentTerm->num1;
                $this->inputs['due_date'] = date('Y-m-d', strtotime("+$dueDays days"));
            }
        }
    }

    private function createDelivAndBilling()
    {
        try {
            DB::beginTransaction();

            // Create DelivDtl
            DelivDtl::create([
                'trhdr_id' => $this->object->id,
                'tr_type' => 'SD',
                'tr_code' => $this->inputs['tr_code'],
                'tr_seq' => 1, // Example sequence number
                'matl_id' => $this->inputs['matl_id'],
                'matl_code' => $this->inputs['matl_code'],
                'matl_descr' => $this->inputs['matl_descr'],
                'qty' => $this->inputs['qty'],
            ]);

            // Create BillingDtl
            BillingDtl::create([
                'trhdr_id' => $this->object->id,
                'tr_type' => 'ARB',
                'tr_code' => $this->inputs['tr_code'],
                'tr_seq' => 1, // Example sequence number
                'matl_id' => $this->inputs['matl_id'],
                'matl_code' => $this->inputs['matl_code'],
                'matl_descr' => $this->inputs['matl_descr'],
                'qty' => $this->inputs['qty'],
                'price' => $this->inputs['price'],
                'amt' => $this->inputs['amt'],
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatch('error', $e->getMessage());
        }
    }

    private function createDelivAndBillingHdr()
    {
        try {
            DB::beginTransaction();

            // Ensure partner_id is set
            if (empty($this->inputs['partner_id'])) {
                throw new Exception('Partner ID is required');
            }

            // Create DelivHdr
            $delivHdr = DelivHdr::create([
                'trhdr_id' => $this->object->id,
                'tr_type' => 'SD',
                'tr_code' => $this->inputs['tr_code'],
                'partner_id' => $this->inputs['partner_id'],
                'partner_code' => $this->inputs['partner_code'],
                'tr_date' => now(),
            ]);

            // Create BillingHdr
            $billingHdr = BillingHdr::create([
                'trhdr_id' => $this->object->id,
                'tr_type' => 'ARB',
                'tr_code' => $this->inputs['tr_code'],
                'partner_id' => $this->inputs['partner_id'],
                'partner_code' => $this->inputs['partner_code'],
                'tr_date' => now(),
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatch('error', $e->getMessage());
        }
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
        $this->SOTax = $this->masterService->getSOTaxData();
        $this->SOSend = $this->masterService->getSOSendData();
        $this->paymentTerms = $this->masterService->getPaymentTerm();
        // $this->suppliers = $this->masterService->getSuppliers();
        $this->warehouses = $this->masterService->getWarehouse();
        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            if (!$this->object) {
                $this->dispatch('error', 'Object not found');
                return;
            }
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['status_code_text'] = $this->object->status_Code_text;
            $this->inputs['tax_doc_flag'] = $this->object->tax_doc_flag;
            $this->inputs['partner_name'] = $this->object->partner->code;
            $this->inputs['textareasend_to'] = $this->object->ship_to_addr;
            $this->inputs['textarea_npwp'] = $this->object->npwp_name . "\n" . $this->object->npwp_addr; // Populate textarea_npwp
            $this->inputs['textareacustommer'] = $this->object->partner->name . "\n" . $this->object->partner->address . "\n" . $this->object->partner->city;
            $this->onPartnerChanged();
        }
        if (!$this->isEditOrView()) {
            $this->isPanelEnabled = "true";
            $this->inputs['tax_doc_flag'] = true;
            $this->inputs['tax_flag'] = 'I';
        }
        // Panggil perhitungan DPP dan PPN saat halaman dimuat
        if (!empty($this->inputs['tax_flag'])) {
            $this->onSOTaxChange();
        }
        $this->dispatch('updateTaxPayerEnabled', !empty($this->inputs['tax_doc_flag']));
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new OrderHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_date']  = date('Y-m-d');
        $this->inputs['due_date']  = date('Y-m-d');
        $this->inputs['tr_type']  = $this->trType;
        $this->inputs['curr_id'] = ConfigConst::CURRENCY_DOLLAR_ID;
        $this->inputs['curr_code'] = "USD";
        $this->inputs['wh_code'] = 18;
        $this->inputs['partner_id'] = 0;

    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
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
            $this->inputs['payment_due_days'] = $paymentTerm->num1; // Save payment_due_days from num1
        }

        // Ensure npwp_code is set to null if tax_payer is disabled
        if ($this->payer === "false") {
            $this->inputs['npwp_code'] = null;
        }

        $this->object->saveOrderHeader($this->appCode, $this->trType, $this->inputs, 'SALESORDER_LASTID');

        // Check if payment term is CASH and create DelivHdr and BillingHdr
        if ($paymentTerm->str2 === 'CASH') {
            $this->createDelivAndBillingHdr();
        }

        if ($this->actionValue == 'Create') {
            return redirect()->route($this->appCode . '.Transaction.SalesOrder.Detail', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);
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

    private function updateVersionNumber2()
    {
        $this->versionNumber = "{$this->notaCount}.{$this->suratJalanCount}";
    }

    public function printInvoice()
    {
        try {
            $this->notaCount++;
            $this->updateVersionNumber2();
            // Logika cetak nota jual
            return redirect()->route('TrdTire1.Transaction.SalesOrder.PrintPdf', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);
        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    public function printDelivery()
    {
        try {
            $this->suratJalanCount++;
            $this->updateVersionNumber2();
            // Logika cetak surat jalan
            return redirect()->route('TrdTire1.Transaction.SalesDelivery.PrintPdf', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
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

            if (!empty($this->inputs['tax_doc_flag'])) {
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
            } else {
                $this->inputs['npwp_code'] = null;
                $this->inputs['textarea_npwp'] = null;
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

    #endregion

    #region Component Events
    // Update total amount based on changes
    public function updateAmount($data)
    {
        $this->total_amount = $data['total_amount'];
        $this->total_discount = ($data['total_discount']);

        // Recalculate DPP and PPN when amount or discount changes
        $this->calculateDPPandPPN($this->inputs['tax_flag'] ?? '');
    }

    // Update discount percentage
    // public function updateDiscount($discount)
    // {
    //     $this->total_discount = $discount;
    //     $this->calculateDPPandPPN($this->inputs['tax'] ?? '');
    // }

    // Update DPP
    // public function updateDPP($dpp)
    // {
    //     $this->total_dpp = $dpp;
    // }
    #endregion
}
