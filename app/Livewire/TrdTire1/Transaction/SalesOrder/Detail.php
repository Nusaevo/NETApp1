<?php

namespace App\Livewire\TrdTire1\Transaction\SalesOrder;

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
    public $trType = "SO";
    public $versionNumber = 1;

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
        'updateAmount' => 'updateAmount',
        'updateDiscount' => 'updateDiscount',
        'updateDPP' => 'updateDPP',
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
        $this->inputs['tr_id'] = OrderHdr::generateTransactionId($vehicle_type, $tax_invoice);
    }

    public function onTaxInvoiceChanged()
    {
        $this->getTransactionCode(); // Regenerate transaction code when the checkbox changes
    }

    public function onSOTaxChange()
    {
        try {
            $configData = ConfigConst::select('num1')
                ->where('const_group', 'TRX_SO_TAX')
                ->where('str1', $this->inputs['tax'])
                ->first();

            $this->inputs['tax_value'] = $configData->num1 ?? 0; // Set ke 0 jika tidak ditemukan

            // Log nilai tax_value untuk debugging

            // Hitung ulang DPP
            $this->calculateTotalDPP();
        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }
    public function calculateTotalDPP()
    {
        // Pastikan tax_value dan total_amount bertipe float
        $taxValue = (float)($this->inputs['tax_value'] ?? 0);
        $totalAmount = (float)$this->total_amount;  // Pastikan ini bertipe float

        if ($taxValue > 0) {
            $dpp = $totalAmount / (1 + $taxValue / 100);
            $this->inputs['dpp'] = round($dpp, 2); // Pembulatan DPP ke 2 angka desimal
        } else {
            // Jika tidak ada pajak, DPP sama dengan total_amount
            $this->inputs['dpp'] = $totalAmount;
        }
        $this->dispatch('updateDPP', $this->inputs['dpp']);
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
    // public function calculateTotalDPP()
    // {
    //     // Mengambil nilai pajak yang sudah dibulatkan
    //     $taxValue = (float)($this->inputs['tax_value'] ?? 0);
    //     $totalAmount = (float)$this->total_amount;

    //     // Jika tax_value > 0, hitung DPP sesuai dengan rumus
    //     if ($taxValue > 0) {
    //         $this->inputs['dpp'] = round($totalAmount / (1 + $taxValue / 100), 2); // Pembulatan ke 2 angka desimal
    //     } else {
    //         // Jika tax_value 0, dpp sama dengan totalAmount
    //         $this->inputs['dpp'] = round($totalAmount, 2); // Pembulatan ke 2 angka desimal
    //     }

    //     $this->dispatch('updateDPP', $this->inputs['dpp']);
    // }




    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.tax'      => $this->trans('tax'),
        ];
        $this->versionNumber = Session::get($this->versionSessionKey);

        $this->masterService = new MasterService();
        $this->partners = $this->masterService->getCustomers();
        $this->SOTax = $this->masterService->getSOTaxData();
        $this->SOSend = $this->masterService->getSOSendData();
        $this->suppliers = $this->masterService->getSuppliers();
        $this->warehouses = $this->masterService->getWarehouse();
        if ($this->isEditOrView()) {
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['status_code_text'] = $this->object->status_Code_text;
            $this->inputs['tax_invoice'] = $this->object->tax_invoice;
            $this->onPartnerChanged();
        }
        if (!$this->isEditOrView()) {

            $this->isPanelEnabled = "true";
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
    // Update total amount based on changes
    public function updateAmount($data)
    {
       // dd($data['total_amount'], $data["total_discount"], $data['total_tax']);
    }

    // Update discount percentage
    public function updateDiscount($discount)
    {
        $this->total_discount = $discount . "%";
        $this->calculateTotalTax(); // Recalculate tax when discount is updated
    }

    // Update DPP
    public function updateDPP($dpp)
    {
        $this->total_dpp = $dpp;
        $this->calculateTotalTax(); // Recalculate tax when DPP is updated
    }

    // New method for calculating the total tax
    public function calculateTotalTax()
    {
        try {
            $taxValue = (float)($this->inputs['tax_value'] ?? 0);
            $totalAmount = (float)$this->total_amount;

            // Calculate total tax based on the tax value
            if ($taxValue > 0) {
                $this->total_tax = round($totalAmount * $taxValue / 100, 2); // Tax calculation
            } else {
                $this->total_tax = 0; // No tax if tax value is 0
            }

            $this->dispatch('updateTotalTax', $this->total_tax); // Dispatch the event with updated tax
        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }
    #endregion
}
