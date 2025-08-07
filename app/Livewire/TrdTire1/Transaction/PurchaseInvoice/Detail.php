<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseInvoice;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Master\{Partner, Material};
use App\Models\TrdTire1\Transaction\{BillingHdr, BillingDeliv, BillingOrder, DelivHdr, DelivDtl, DelivPacking, OrderHdr, OrderDtl};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Models\TrdTire1\Transaction\DelivPicking;
use App\Services\TrdTire1\Master\MasterService;
use App\Services\TrdTire1\OrderService;
use Exception;
use App\Models\TrdRetail1\Inventories\IvtBal;
use App\Services\TrdTire1\BillingService;
use Illuminate\Support\Facades\DB;
use App\Services\TrdTire1\DeliveryService;

class Detail extends BaseComponent
{
    #region Constant Variables
    public $inputs = [];
    public $input_details = [];
    public $suppliers;
    public $warehouses;
    public $partners;
    public $vehicle_type;
    public $tax_invoice;
    public $transaction_id;
    public $payments;
    public $deletedItems = [];
    public $newItems = [];
    public $materials;
    public $object;
    public $object_detail;
    public $trhdr_id;
    public $tr_seq;
    public $tr_code;
    public $reffhdrtr_code;

    public $total_amount = 0;
    public $trType = "APB"; // Changed from PD to APB for Purchase Invoice

    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];
    public $returnIds = [];
    public $currencyRate = 0;
    public $npwpOptions = [];
    protected $masterService;
    public $isPanelEnabled = true;
    public $purchaseOrders = [];

    protected $rules = [
        'inputs.tr_code' => 'required',
        'inputs.partner_id' => 'required',
        'input_details.*.qty' => 'required',
    ];

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'delete' => 'delete',
        'onPartnerChanged' => 'onPartnerChanged',
        'onPaymentTermChanged' => 'onPaymentTermChanged',
        'onCurrencyChanged' => 'onCurrencyChanged'
    ];
    #endregion

    #region Component Lifecycle Methods
    protected function onPreRender()
    {
        $this->customValidationAttributes = [
            'inputs.tax' => $this->trans('tax'),
            'input_details.*' => $this->trans('product'),
            'input_details.*.matl_id' => $this->trans('matl_id'),
            'input_details.*.qty_order' => $this->trans('qty_order'),
            'input_details.*.price' => $this->trans('price'),
            'input_details.*.qty' => $this->trans('qty'),
        ];

        $this->masterService = new MasterService();
        $this->warehouses = $this->masterService->getWarehouse();
        $this->purchaseOrders = app(OrderService::class)->getOutstandingPO();

        if ($this->isEditOrView()) {
            $this->object = BillingHdr::withTrashed()->find($this->objectIdValue);
            $this->isPanelEnabled = "false";

            // Populate inputs array with BillingHdr fields
            $this->inputs = populateArrayFromModel($this->object);

            // Ensure all BillingHdr fields are properly set
            $this->inputs['tr_type'] = $this->object->tr_type ?? $this->trType;
            $this->inputs['tr_code'] = $this->object->tr_code ?? '';
            $this->inputs['tr_date'] = $this->object->tr_date ?? date('Y-m-d');
            $this->inputs['reff_code'] = $this->object->reff_code ?? '';
            $this->inputs['partner_id'] = $this->object->partner_id ?? null;
            $this->inputs['partner_code'] = $this->object->partner_code ?? '';
            $this->inputs['payment_term_id'] = $this->object->payment_term_id ?? null;
            $this->inputs['payment_term'] = $this->object->payment_term ?? '';
            $this->inputs['payment_due_days'] = $this->object->payment_due_days ?? 0;
            $this->inputs['curr_id'] = $this->object->curr_id ?? null;
            $this->inputs['curr_code'] = $this->object->curr_code ?? '';
            $this->inputs['curr_rate'] = $this->object->curr_rate ?? 1;
            $this->inputs['partnerbal_id'] = $this->object->partnerbal_id ?? null;
            $this->inputs['amt'] = $this->object->amt ?? 0;
            $this->inputs['amt_beforetax'] = $this->object->amt_beforetax ?? 0;
            $this->inputs['amt_tax'] = $this->object->amt_tax ?? 0;
            $this->inputs['amt_adjustdtl'] = $this->object->amt_adjustdtl ?? 0;
            $this->inputs['amt_adjusthdr'] = $this->object->amt_adjusthdr ?? 0;
            $this->inputs['amt_shipcost'] = $this->object->amt_shipcost ?? 0;
            $this->inputs['amt_reff'] = $this->object->amt_reff ?? 0;
            $this->inputs['print_date'] = $this->object->print_date ?? null;

            // Load partner data
            $partner = Partner::find($this->object->partner_id);
            if ($partner) {
                $this->inputs['partner_id'] = $partner->id;
                $this->inputs['partner_name'] = $partner->name;
            }

            // Get payment and currency data from OrderHdr if reff_code exists
            if (!empty($this->inputs['reff_code'])) {
                $orderHdr = OrderHdr::where('tr_code', $this->inputs['reff_code'])
                    ->where('tr_type', 'PO') // Purchase Order
                    ->first();

                if ($orderHdr) {
                    // Set payment and currency fields from OrderHdr
                    $this->inputs['payment_term_id'] = $orderHdr->payment_term_id;
                    $this->inputs['payment_term'] = $orderHdr->payment_term;
                    $this->inputs['payment_due_days'] = $orderHdr->payment_due_days;
                    $this->inputs['curr_id'] = $orderHdr->curr_id;
                    $this->inputs['curr_code'] = $orderHdr->curr_code;
                    $this->inputs['curr_rate'] = $orderHdr->curr_rate;
                }

                if (!collect($this->purchaseOrders)->pluck('value')->contains($this->inputs['reff_code'])) {
                    $this->purchaseOrders[] = [
                        'label' => $this->inputs['reff_code'],
                        'value' => $this->inputs['reff_code'],
                    ];
                }
            }

            // Load details and purchase order details
            $this->loadDetails();
            // $this->onPartnerChanged($this->inputs['partner_id']);
        }
    }

    public function onReset()
    {
        $this->reset('inputs', 'input_details');
        $this->object = new BillingHdr();
        $this->inputs = populateArrayFromModel($this->object);

        // Initialize BillingHdr fields
        $this->inputs['tr_type'] = $this->trType;
        $this->inputs['tr_code'] = '';
        $this->inputs['tr_date'] = date('Y-m-d');
        $this->inputs['reff_code'] = '';
        $this->inputs['partner_id'] = null;
        $this->inputs['partner_code'] = '';
        $this->inputs['payment_term_id'] = null;
        $this->inputs['payment_term'] = '';
        $this->inputs['payment_due_days'] = 0;
        $this->inputs['curr_id'] = null;
        $this->inputs['curr_code'] = '';
        $this->inputs['curr_rate'] = 1;
        $this->inputs['partnerbal_id'] = null;
        $this->inputs['amt'] = 0;
        $this->inputs['amt_beforetax'] = 0;
        $this->inputs['amt_tax'] = 0;
        $this->inputs['amt_adjustdtl'] = 0;
        $this->inputs['amt_adjusthdr'] = 0;
        $this->inputs['amt_shipcost'] = 0;
        $this->inputs['amt_reff'] = 0;
        $this->inputs['print_date'] = null;
    }

    #endregion

    #region Material List Methods
    protected function loadDetails()
    {
        if (!empty($this->object)) {
            // Load BillingDeliv records
            $this->object_detail = BillingDeliv::where('trhdr_id', $this->object->id)->get();
            $this->input_details = $this->object_detail->toArray();

            // Load BillingOrder records
            $billingOrders = BillingOrder::where('trhdr_id', $this->object->id)->get();

            foreach ($this->object_detail as $key => $detail) {
                // Ensure trhdr_id is set
                $this->input_details[$key]['trhdr_id'] = $this->object->id;

                // Get corresponding BillingOrder record
                $billingOrder = $billingOrders->where('reffhdr_id', $detail->deliv_id)->first();

                if ($billingOrder) {
                    $this->input_details[$key]['reffhdr_id'] = $billingOrder->reffhdr_id;
                    $this->input_details[$key]['reffhdrtr_type'] = $billingOrder->reffhdrtr_type;
                    $this->input_details[$key]['reffhdrtr_code'] = $billingOrder->reffhdrtr_code;
                    $this->input_details[$key]['reffdtltr_seq'] = $billingOrder->reffdtltr_seq;
                    $this->input_details[$key]['matl_descr'] = $billingOrder->matl_descr;
                    $this->input_details[$key]['qty'] = $billingOrder->qty;
                    $this->input_details[$key]['qty_uom'] = $billingOrder->qty_uom;
                    $this->input_details[$key]['qty_base'] = $billingOrder->qty_base;
                }

                // Get delivery information
                $delivHdr = DelivHdr::find($detail->deliv_id);
                if ($delivHdr) {
                    $this->input_details[$key]['deliv_code'] = $delivHdr->tr_code;
                    $this->input_details[$key]['deliv_date'] = $delivHdr->tr_date;
                }
            }
        }
    }

    public function deleteItem($index)
    {
        try {
            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            // If no items left in input_details, enable reff_code field
            if (empty($this->input_details)) {
                $this->isPanelEnabled = true;
                $this->inputs['reff_code'] = null;
            }
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menghapus item: ' . $e->getMessage());
        }
    }

        public function onPartnerChanged($value)
    {
        if ($value) {
            // Update partner_id and partner_code in inputs
            $this->inputs['partner_id'] = $value;

            // Get partner data to set partner_code
            $partner = Partner::find($value);
            if ($partner) {
                $this->inputs['partner_code'] = $partner->code;
            }

            // Reset input_details
            $this->input_details = [];

            // Get delivery headers that haven't been billed for the selected partner
            $delivHeaders = DelivHdr::where('partner_id', $value)
                ->where('billhdr_id', 0)
                ->where('tr_type', 'PD') // Purchase Delivery
                ->whereNull('deleted_at')
                ->get();

            if ($delivHeaders->isEmpty()) {
                $this->dispatch('info', 'Tidak ada data delivery yang belum dibilling untuk supplier ini.');
                return;
            }

            // Get details from each delivery header
            foreach ($delivHeaders as $delivHdr) {
                $delivPackings = DelivPacking::where('trhdr_id', $delivHdr->id)
                    ->where('tr_type', 'PD')
                    ->get();

                foreach ($delivPackings as $delivPacking) {
                    // Get picking data to get material info
                    $picking = DelivPicking::where('trpacking_id', $delivPacking->id)->first();

                    if ($picking) {
                        // Get material description and code from Material relation
                        $material = Material::find($picking->matl_id);
                        $matl_descr = $material ? $material->name : $delivPacking->matl_descr;
                        $matl_code = $material ? $material->code : $picking->matl_code;

                        $this->input_details[] = [
                            // BillingDeliv fields (urut sesuai fillable)
                            'trhdr_id' => null, // Will be set when saving
                            'deliv_id' => $delivHdr->id,
                            'deliv_type' => 'PD',
                            'deliv_code' => $delivHdr->tr_code,
                            'amt_shipcost' => $delivHdr->amt_shipcost ?? 0,

                            // BillingOrder fields (urut sesuai fillable)
                            'reffhdr_id' => $delivHdr->id,
                            'reffhdrtr_type' => 'PD',
                            'reffhdrtr_code' => $delivHdr->tr_code,
                            'reffdtltr_seq' => $delivPacking->tr_seq,
                            'matl_descr' => $matl_descr,
                            'qty' => $delivPacking->qty,
                            'qty_uom' => $picking->matl_uom,
                            'qty_base' => $delivPacking->qty,

                            // Additional info for display
                            'deliv_date' => $delivHdr->tr_date,
                            'partner_id' => $delivHdr->partner_id,
                            'partner_code' => $delivHdr->partner_code,
                            'matl_id' => $picking->matl_id,
                            'matl_code' => $matl_code,
                            'matl_uom' => $picking->matl_uom,
                        ];
                    }
                }
            }

            // Get payment and currency data from OrderHdr (first delivery's order)
            // if (!empty($this->input_details)) {
            //     $firstDelivery = $delivHeaders->first();
            //     if ($firstDelivery) {
            //         // Get OrderHdr from delivery's reff_code
            //         $orderHdr = OrderHdr::where('tr_code', $firstDelivery->reff_code)
            //             ->where('tr_type', 'PO') // Purchase Order
            //             ->first();

            //         if ($orderHdr) {
            //             // Set payment and currency fields from OrderHdr
            //             $this->inputs['payment_term_id'] = $orderHdr->payment_term_id;
            //             $this->inputs['payment_term'] = $orderHdr->payment_term;
            //             $this->inputs['payment_due_days'] = $orderHdr->payment_due_days;
            //             $this->inputs['curr_id'] = $orderHdr->curr_id;
            //             $this->inputs['curr_code'] = $orderHdr->curr_code;
            //             $this->inputs['curr_rate'] = $orderHdr->curr_rate;
            //         }
            //     }
            // }

            $this->dispatch('success', 'Berhasil memuat ' . count($this->input_details) . ' item delivery yang belum dibilling.');
        }
    }

    #endregion

    #region CRUD Operations
    public function onValidateAndSave()
    {
        // dd($this->inputs, $this->input_details);
        try {
            $this->validate();

            // Cek duplikasi tr_code
            $existingBilling = BillingHdr::where([
                'tr_type' => $this->trType,
                'tr_code' => $this->inputs['tr_code']
            ])->first();

            if ($existingBilling && $existingBilling->id !== $this->object->id) {
                $this->dispatch('error', 'Nomor Invoice ' . $this->inputs['tr_code'] . ' sudah ada. Silakan gunakan nomor yang berbeda.');
                return;
            }

            if ($this->object->isNew()) {
                $this->object->status_code = Status::OPEN;
            }

            $headerData = $this->inputs;
            $detailData = $this->input_details;

            $billingService = app(BillingService::class);
            $result = $billingService->saveBilling($headerData, $detailData);
            $this->object = $result['billing_hdr'];

            $this->dispatch('success', 'Purchase Invoice berhasil ' .
                ($this->actionValue === 'Create' ? 'disimpan' : 'diperbarui') . '.');

            return redirect()->route(
                $this->appCode . '.Transaction.PurchaseInvoice.Detail',
                [
                    'action'   => encryptWithSessionKey('Edit'),
                    'objectId' => encryptWithSessionKey($this->object->id),
                ]
            );
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Gagal menyimpan Purchase Invoice: ' . $e->getMessage());
            // $this->dispatch('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function delete()
    {
        try {
            // Use service to delete billing
            $billingService = app(\App\Services\TrdTire1\BillingService::class);
            $billingService->delBilling($this->object->id);

            return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
    #endregion

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
