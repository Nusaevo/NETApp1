<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseDelivery;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivDtl};
use App\Models\TrdTire1\Master\{Partner, Material};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdTire1\Master\MasterService;
use Exception;


class Detail extends BaseComponent
{
    #region Constant Variables
    public $inputs = [];
    public $SOTax = [];
    public $SOSend = [];
    public $suppliers;
    public $warehouses;
    public $partners;
    public $vehicle_type;
    public $tax_invoice;
    public $transaction_id;
    public $payments;
    public $deletedItems = [];
    public $newItems = [];

    public $total_amount = 0;
    public $trType = "PD";

    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];

    public $returnIds = [];
    public $currencyRate = 0;
    public $npwpOptions = [];

    protected $masterService;
    public $isPanelEnabled = "false";


    public $rules  = [
        'inputs.tr_date' => 'nullable',
        'inputs.send_to' => 'nullable',
        'inputs.tax_payer' => 'nullable',
        'inputs.payment_terms' => 'nullable',
        'inputs.tax' => 'nullable',
        'inputs.due_date' => 'nullable',
        'inputs.cust_reff' => 'nullable',
    ];
    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'delete' => 'delete',
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
        $this->inputs['tr_id'] = DelivHdr::generateTransactionId($vehicle_type, $tax_invoice);
    }

    public function onTaxInvoiceChanged()
    {
        $this->getTransactionCode(); // Regenerate transaction code when the checkbox changes
    }
    public function updatedInputsPartnerId($partnerId)
    {
        // Ambil partner berdasarkan partner_id yang dipilih
        $partner = \App\Models\TrdTire1\Master\Partner::find($partnerId);

        if ($partner) {
            // Ambil PartnerDetail berdasarkan partner_id yang sama
            $partnerDetail = $partner->partnerDetail;

            if ($partnerDetail && $partnerDetail->wp_details) {
                $wpDetails = $partnerDetail->wp_details; // Bisa array atau string

                // Jika wp_details berupa string JSON, decode
                if (is_string($wpDetails)) {
                    $wpDetails = json_decode($wpDetails, true);
                }

                if (is_array($wpDetails)) {
                    // Ambil NPWP pertama jika ada
                    $npwp = array_column($wpDetails, 'npwp');

                    if (count($npwp) > 0) {
                        // Jika ada NPWP, set tax_payer ke NPWP pertama
                        $this->inputs['tax_payer'] = $npwp[0];
                    } else {
                        // Jika tidak ada NPWP, kosongkan tax_payer
                        $this->inputs['tax_payer'] = null;
                    }
                } else {
                    // Jika wp_details bukan array atau gagal decode, kosongkan tax_payer
                    $this->inputs['tax_payer'] = null;
                }
            } else {
                // Jika tidak ada wp_details, kosongkan tax_payer
                $this->inputs['tax_payer'] = null;
            }
        } else {
            // Jika tidak ada partner, kosongkan tax_payer
            $this->inputs['tax_payer'] = null;
        }
    }

    // public function generateBasicTransactionId()
    // {
    //     $appCode = $this->getAppCode($this->vehicle_type);
    //     $this->transaction_id = $this->generateTransactionId($appCode, 'some_code');
    // }


    // private function getAppCode($vehicleType)
    // {
    //     switch ($vehicleType) {
    //         case '0':
    //             return 'Motor';
    //         case '1':
    //             return 'Mobil';
    //         case '2':
    //             return 'Lain-lain';
    //         default:
    //             return 'Lain-lain';
    //     }
    // }

    // public function generateTransactionId($appCode, $codeType)
    // {
    //     // Logic to generate transaction ID based on appCode and codeType
    //     return $appCode . '-' . $codeType . '-' . uniqid();
    // }
    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.tax'      => $this->trans('tax'),
        ];

        $this->masterService = new MasterService();
        $this->partners = $this->masterService->getCustomers();
        $this->SOTax = $this->masterService->getSOTaxData();
        $this->SOSend = $this->masterService->getSOSendData();
        $this->suppliers = $this->masterService->getSuppliers();
        $this->warehouses = $this->masterService->getWarehouse();
        if ($this->isEditOrView()) {
            $this->object = DelivHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['status_code_text'] = $this->object->status_Code_text;
            $this->inputs['tax_invoice'] = $this->object->tax_invoice; // Ensure tax_invoice is populated
        }
        if (!$this->isEditOrView()) {

            $this->isPanelEnabled = "true";
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->object = new DelivHdr();
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
        $this->object->savePurchaseHeader($this->appCode, $this->trType, $this->inputs, 'SALESORDER_LASTID');
        if ($this->actionValue == 'Create') {
            return redirect()->route($this->appCode . '.Transaction.PurchaseDelivery.Detail', [
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

            //$this->updateVersionNumber();
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

    #region Component Events

    #endregion
}
