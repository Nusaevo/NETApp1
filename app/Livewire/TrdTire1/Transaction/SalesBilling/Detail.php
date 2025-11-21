<?php

namespace App\Livewire\TrdTire1\Transaction\SalesBilling;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl};
use App\Models\TrdTire1\Master\{Partner, Material};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdTire1\Master\MasterService;
use Illuminate\Support\Facades\{Session};
use Exception;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $inputs = [];
    public $SOTax = [];
    public $SOSend = [];
    public $paymentTerms = [];
    public $suppliers;
    public $warehouses;
    public $partners;
    public $vehicle_type;
    public $tax_invoice;
    public $transaction_id;
    public $payments;
    public $deletedItems = [];
    public $newItems = [];

    public $total_amount;
    public $total_tax;
    public $total_dpp;
    public $total_discount;
    public $trType = "PO";
    public $versionNumber = "0.0";

    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];

    public $returnIds = [];
    public $currencyRate = 0;
    public $npwpOptions = [];
    protected $masterService;
    public $isPanelEnabled = "false";
    public $notaCount = 0; // x: jumlah nota jual dicetak
    public $suratJalanCount = 0; // y: jumlah surat jalan dicetak


    public $rules  = [
        'inputs.tr_date' => 'nullable',
        'inputs.send_to' => 'required',
        'inputs.tr_code' => 'required',
        'inputs.partner_id' => 'required',
        'inputs.tax_payer' => 'nullable',
        'inputs.payment_terms' => 'nullable',
        'inputs.tax' => 'nullable',
        'inputs.due_date' => 'nullable',
        'inputs.cust_reff' => 'nullable',
    ];
    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'delete' => 'delete',
        'updateAmount' => 'updateAmount',
        'updateDiscount' => 'updateDiscount',
        'updateDPP' => 'updateDPP',
        'updateTotalTax' => 'updateTotalTax',
        'DropdownSelected' => 'DropdownSelected'
    ];
    #endregion

    #region Populate Data methods

    public function getTransactionCode()
    {
        if (!isset($this->inputs['vehicle_type'])) {
            $this->dispatch('warning', 'Tipe Kendaraan harus diisi');
            return;
        }

        $vehicle_type = $this->inputs['vehicle_type'];
        $tax_invoice = isset($this->inputs['tax_invoice']) && $this->inputs['tax_invoice']; // Check if tax invoice is checked
        $this->inputs['tr_code'] = OrderHdr::generateTransactionId($vehicle_type, 'PO', $tax_invoice);
    }

    public function onTaxInvoiceChanged()
    {
        $this->getTransactionCode(); // Regenerate transaction code when the checkbox changes
    }

    public function onSOTaxChange()
    {
        try {
            // Ambil data konfigurasi berdasarkan konstanta pajak
            $configData = ConfigConst::select('num1', 'str1')
                ->where('const_group', 'TRX_SO_TAX')
                ->where('str1', $this->inputs['tax'])
                ->first();

            $this->inputs['tax_value'] = $configData->num1 ?? 0; // Nilai pajak default 0 jika tidak ditemukan
            $taxType = $configData->str1 ?? ''; // Tipe pajak (str1)

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
    }

    private function listNpwp($partner)
    {
        $partnerDetail = $partner->PartnerDetail;

        if ($partnerDetail && $partnerDetail->wp_details) {
            $wpDetails = $partnerDetail->wp_details;

            if (is_string($wpDetails)) {
                $wpDetails = json_decode($wpDetails, true);
            }

            if (is_array($wpDetails)) {
                return array_map(function ($item) {
                    return [
                        'label' => $item['npwp'],
                        'value' => $item['npwp'],
                    ];
                }, $wpDetails);
            }
        }

        return null;
    }

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.tax'      => $this->trans('tax'),
        ];

        $this->masterService = new MasterService();
        $this->partners = $this->masterService->getCustomers();
        $this->SOTax = $this->masterService->getSOTaxData();
        $this->SOSend = $this->masterService->getSOSendData();
        $this->paymentTerms = $this->masterService->getPaymentTerm();
        // $this->suppliers = $this->masterService->getSuppliers();
        $this->warehouses = $this->masterService->getWarehouse();
        if ($this->isEditOrView()) {
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['status_code_text'] = $this->object->status_Code_text;
            $this->inputs['tax_invoice'] = $this->object->tax_invoice;
            $this->inputs['tr_code'] = $this->object->tr_code;
            $this->onPartnerChanged();
        }
        if (!$this->isEditOrView()) {
            $this->isPanelEnabled = "true";
        }
        // Panggil perhitungan DPP dan PPN saat halaman dimuat
        if (!empty($this->inputs['tax'])) {
            $this->onSOTaxChange();
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new OrderHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_date']  = date('Y-m-d');
        $this->inputs['tr_type']  = $this->trType;
        $this->inputs['curr_id'] = ConfigConst::CURRENCY_DOLLAR_ID;
        $this->inputs['curr_code'] = "USD";
        $this->inputs['send_to'] = "Pelanggan";
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
        $this->object->saveOrderHeader($this->appCode, $this->trType, $this->inputs, 'SALESORDER_LASTID');
        if ($this->actionValue == 'Create') {
            return redirect()->route($this->appCode . '.Transaction.PurchaseOrder.Detail', [
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
            $messageKey = 'generic.string.delete';
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
            $this->dispatch('success', 'Nota jual berhasil dicetak!');
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
            $this->dispatch('success', 'Surat Jalan berhasil dicetak!');
        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
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
        $this->calculateDPPandPPN($this->inputs['tax'] ?? '');
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
