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
use Illuminate\Support\Facades\Log;
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
    public $total_discount = 0;
    public $total_dpp = 0;
    public $total_tax = 0;
    public $trType = "APB"; // Changed from PD to APB for Purchase Invoice
    public $items = [];
    public $selectedItems = [];
    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];
    public $returnIds = [];
    public $currencyRate = 0;
    public $npwpOptions = [];
    protected $masterService;
    public $isPanelEnabled = true;
    public $purchaseOrders = [];
    public $uniqueDelivData = [];

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
        'onCurrencyChanged' => 'onCurrencyChanged',
        'onMaterialChanged' => 'onMaterialChanged',
        'onDelivChanged' => 'onDelivChanged',
        'removeDelivery' => 'removeDelivery'
    ];
    #endregion

    #region Livewire Lifecycle Methods
    public function updated($propertyName)
    {
        // Handle changes to input_details for automatic amount calculation
        if (str_starts_with($propertyName, 'input_details.')) {
            $parts = explode('.', $propertyName);
            if (count($parts) >= 3) {
                $index = $parts[1];
                $field = $parts[2];

                if (in_array($field, ['qty', 'price', 'disc_pct']) && isset($this->input_details[$index])) {
                    $this->calculateTotals();
                }
            }
        }
    }

    private function calculateTotals()
    {
        $this->total_amount = 0;
        $this->total_discount = 0;
        $this->total_dpp = 0;
        $this->total_tax = 0;

        // Calculate amounts for each item and sum totals
        foreach ($this->input_details as $key => $detail) {
            if (!empty($detail['qty']) && !empty($detail['price'])) {
                $qty = $detail['qty'];
                $price = $detail['price'];
                $discount = $detail['disc_pct'] / 100;

                // Calculate discount amount
                $this->input_details[$key]['disc_amt'] = round($qty * $price * $discount, 0);

                // Use price_afterdisc and price_beforetax from orderDtl
                $priceAfterDisc = $detail['price_afterdisc'] ?? ($price * (1 - $discount));
                $priceBeforeTax = $detail['price_beforetax'] ?? $priceAfterDisc;

                // Calculate amounts based on delivery qty
                $this->input_details[$key]['amt_beforetax'] = $priceBeforeTax * $qty;
                $this->input_details[$key]['amt_tax'] = $detail['amt_tax'] ?? ($qty * $price * (1 - $discount) * 0.11); // Calculate tax based on delivery qty
                $this->input_details[$key]['amt'] = $priceAfterDisc * $qty;

                // Calculate adjustment
                $this->input_details[$key]['amt_adjustdtl'] = $this->input_details[$key]['amt'] - $this->input_details[$key]['amt_beforetax'] - $this->input_details[$key]['amt_tax'];
            }

            // Sum totals
            $this->total_amount += $this->input_details[$key]['amt'] ?? 0;
            $this->total_discount += $this->input_details[$key]['disc_amt'] ?? 0;
            $this->total_dpp += $this->input_details[$key]['amt_beforetax'] ?? 0;
            $this->total_tax += $this->input_details[$key]['amt_tax'] ?? 0;
        }

        // Format as Rupiah
        $this->total_amount = rupiah($this->total_amount);
        $this->total_discount = rupiah($this->total_discount);
        $this->total_dpp = rupiah($this->total_dpp);
        $this->total_tax = rupiah($this->total_tax);
    }

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

            // Load delivery items untuk komponen x-tes-component dalam mode edit
            if (!empty($this->inputs['partner_id'])) {
                $this->loadDeliveryItemsForEdit();
            }
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
            // Load BillingOrder records directly
            $billingOrders = BillingOrder::where('trhdr_id', $this->object->id)->get();
            $billingDelivs = BillingDeliv::where('trhdr_id', $this->object->id)->get();

            // Group data by matl_id to avoid duplication
            $groupedData = [];
            $this->input_details = [];

            foreach ($billingOrders as $billingOrder) {
                // Get delivery information - take the first billingDeliv to get deliv_id
                $billingDeliv = $billingDelivs->first();
                $delivHdr = null;
                if ($billingDeliv && $billingDeliv->deliv_id) {
                    $delivHdr = DelivHdr::find($billingDeliv->deliv_id);
                }

                // Get OrderDtl data for material and pricing information
                $orderDtl = OrderDtl::where('id', $billingOrder->reffdtl_id ?? null)
                    ->where('tr_type', 'PO')
                    ->first();

                $matl_id = $orderDtl ? $orderDtl->matl_id : null;

                if ($matl_id) {
                    // Group data by matl_id
                    if (!isset($groupedData[$matl_id])) {
                        $groupedData[$matl_id] = [
                            // BillingOrder fields
                            'trhdr_id' => $this->object->id,
                            'reffhdr_id' => $billingOrder->reffhdr_id,
                            'reffhdrtr_type' => $billingOrder->reffhdrtr_type,
                            'reffhdrtr_code' => $billingOrder->reffhdrtr_code,
                            'reffdtltr_seq' => $billingOrder->reffdtltr_seq,
                            'matl_descr' => $billingOrder->matl_descr,
                            'qty' => 0, // Will be summed
                            'qty_uom' => $billingOrder->qty_uom,
                            'qty_base' => 0, // Will be summed
                            'amt' => 0, // Will be summed
                            'amt_beforetax' => 0, // Will be summed
                            'amt_tax' => 0, // Will be summed
                            'amt_adjustdtl' => 0, // Will be summed

                            'deliv_id' => $billingDeliv ? $billingDeliv->deliv_id : null,
                            // Delivery information
                            'deliv_code' => $delivHdr ? $delivHdr->tr_code : null,
                            'deliv_date' => $delivHdr ? $delivHdr->reff_date : null,
                            'deliv_type' => 'PD',

                            // Material and pricing fields from OrderDtl
                            'matl_id' => $matl_id,
                            'price' => $orderDtl ? $orderDtl->price : 0,
                            'disc_pct' => $orderDtl ? $orderDtl->disc_pct : 0,
                            'disc_amt' => 0, // Will be calculated
                            'price_afterdisc' => $orderDtl ? $orderDtl->price_afterdisc : 0,
                            'price_beforetax' => $orderDtl ? $orderDtl->price_beforetax : 0,
                        ];
                    }

                    // Sum the quantities and amounts
                    $groupedData[$matl_id]['qty'] += $billingOrder->qty;
                    $groupedData[$matl_id]['qty_base'] += $billingOrder->qty_base;
                    $groupedData[$matl_id]['amt'] += $billingOrder->amt;
                    $groupedData[$matl_id]['amt_beforetax'] += $billingOrder->amt_beforetax;
                    $groupedData[$matl_id]['amt_tax'] += $billingOrder->amt_tax;
                    $groupedData[$matl_id]['amt_adjustdtl'] += $billingOrder->amt_adjustdtl;
                }
            }

            // Convert grouped data to input_details
            foreach ($groupedData as $matl_id => $data) {
                $this->input_details[] = $data;
            }

            // Calculate totals after loading details
            $this->calculateTotals();
        }
    }

    protected function loadDeliveryItemsForEdit()
    {
        // Get selected delivery IDs from existing billing details
        $selectedDelivIds = collect($this->input_details)->pluck('deliv_id')->unique()->filter()->toArray();

        // Get all delivery headers for this partner (including already billed ones)
        $allDelivHeaders = DelivHdr::where('partner_id', $this->inputs['partner_id'])
            ->where('tr_type', 'PD')
            ->whereNull('deleted_at')
            ->get();

        // Populate items untuk komponen multiple select
        $this->items = [];
        foreach ($allDelivHeaders as $delivHdr) {
            // Format untuk komponen multiple select: array asosiatif dengan key-value pairs
            $this->items[(string)$delivHdr->id] = $delivHdr->tr_code;
        }

        // Set selected items (only the ones that are actually in this billing)
        $this->selectedItems = array_map('strval', $selectedDelivIds);

        // Dispatch event untuk update komponen
        $this->dispatch('selectedItemsUpdated');

        // Recalculate totals to ensure amounts are correct
        $this->calculateTotals();
    }

    public function deleteItem($index)
    {
        try {
            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            // Recalculate totals after deleting item
            $this->calculateTotals();

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
                // Reset items dan selected items
                $this->items = [];
                $this->selectedItems = [];
                $this->dispatch('selectedItemsUpdated');
                return;
            }

            // Populate items untuk komponen multiple select dengan data delivery
            $this->items = [];
            $this->selectedItems = []; // Reset selected items

            foreach ($delivHeaders as $delivHdr) {
                // Format untuk komponen multiple select: array asosiatif dengan key-value pairs
                $this->items[(string)$delivHdr->id] = $delivHdr->tr_code;
                // Otomatis pilih semua nota penerimaan
                $this->selectedItems[] = (string)$delivHdr->id;
            }

            // Dispatch event untuk update komponen
            $this->dispatch('selectedItemsUpdated');

            // Otomatis load detail material untuk semua nota penerimaan yang dipilih
            if (!empty($this->selectedItems)) {
                $this->loadMaterialDetails();
            }

            $this->dispatch('success', 'Berhasil memuat ' . count($this->items) . ' nota delivery dan detail material yang sesuai.');
        }
    }

    public function onDelivChanged($selectedItems = null)
    {
        // Log the incoming data for debugging
        Log::info('onDelivChanged called', [
            'selectedItems' => $selectedItems,
            'type' => gettype($selectedItems),
            'is_array' => is_array($selectedItems),
            'is_string' => is_string($selectedItems)
        ]);

        // Update selected items - handle both array and string input
        if (is_string($selectedItems)) {
            $this->selectedItems = json_decode($selectedItems, true) ?? [];
        } elseif (is_array($selectedItems)) {
            $this->selectedItems = $selectedItems;
        } else {
            // If no parameter provided, use the current selectedItems from the component
            $this->selectedItems = $this->selectedItems ?? [];
        }

        // Pastikan semua selectedItems adalah string untuk konsistensi
        $this->selectedItems = array_map('strval', $this->selectedItems);

        // Jika ada item yang dipilih, filter input_details berdasarkan delivery yang dipilih
        if (!empty($this->selectedItems) && !empty($this->inputs['partner_id'])) {
            // Load material details using the new method
            $this->loadMaterialDetails();

            if (count($this->input_details) > 0) {
                $this->dispatch('success', 'Berhasil memuat ' . count($this->input_details) . ' item dari ' . count($this->selectedItems) . ' nota delivery yang dipilih.');
            } else {
                $this->dispatch('warning', 'Tidak ada item yang ditemukan dari nota delivery yang dipilih.');
            }
        } else {
            // Jika tidak ada item yang dipilih atau partner_id, kosongkan input_details
            $this->input_details = [];
            $this->calculateTotals();

            if (empty($this->inputs['partner_id'])) {
                $this->dispatch('info', 'Silakan pilih supplier terlebih dahulu.');
            } else {
                $this->dispatch('info', 'Silakan pilih satu atau lebih nota delivery untuk memuat data.');
            }
        }

        // Dispatch event untuk update komponen
        $this->dispatch('selectedItemsUpdated');
    }

    public function removeDelivery($delivId)
    {
        // Remove from items array
        if (isset($this->items[$delivId])) {
            unset($this->items[$delivId]);
        }

        // Remove from selectedItems array
        $delivIdStr = (string)$delivId;
        if (in_array($delivIdStr, $this->selectedItems)) {
            $this->selectedItems = array_diff($this->selectedItems, [$delivIdStr]);
        }

        // Recalculate input_details based on remaining selected items
        if (!empty($this->selectedItems) && !empty($this->inputs['partner_id'])) {
            // Always reload material details to ensure consistency
            // This is safer than trying to selectively remove materials
            $this->loadMaterialDetails();
        } else {
            $this->input_details = [];
            $this->calculateTotals();
        }

        $this->dispatch('success', 'Nota penerimaan berhasil dihapus dari daftar.');
    }



    public function loadMaterialDetails()
    {
        if (empty($this->selectedItems) || empty($this->inputs['partner_id'])) {
            $this->input_details = [];
            $this->calculateTotals();
            return;
        }

        // Reset input_details
        $this->input_details = [];

        // Get delivery headers yang dipilih
        $selectedIds = array_map('intval', $this->selectedItems);
        $selectedDelivHeaders = DelivHdr::whereIn('id', $selectedIds)
            ->where('partner_id', $this->inputs['partner_id'])
            ->where('tr_type', 'PD')
            ->whereNull('deleted_at')
            ->get();

        // Temporary array to group data by matl_id
        $groupedData = [];
        // Track unique delivery IDs to avoid duplication in BillingDeliv
        $uniqueDelivIds = [];

        // Get details from each selected delivery header
        foreach ($selectedDelivHeaders as $delivHdr) {
            // Add to unique delivery IDs (only once per delivery)
            $uniqueDelivIds[$delivHdr->id] = [
                'trhdr_id' => null, // Will be set when saving
                'deliv_id' => $delivHdr->id,
                'deliv_type' => 'PD',
                'deliv_code' => $delivHdr->tr_code,
                'amt_shipcost' => $delivHdr->amt_shipcost ?? 0,
            ];

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

                    // Get price from Purchase Order Detail using reffdtl_id from delivpacking
                    $orderDtl = OrderDtl::where('id', $delivPacking->reffdtl_id)
                        ->where('matl_id', $picking->matl_id)
                        ->where('tr_type', 'PO')
                        ->first();

                    $matl_id = $picking->matl_id;

                    // Group data by matl_id
                    if (!isset($groupedData[$matl_id])) {
                        $groupedData[$matl_id] = [
                            // BillingDeliv fields (urut sesuai fillable) - use first delivery for this material
                            'trhdr_id' => null, // Will be set when saving
                            'deliv_id' => $delivHdr->id,
                            'deliv_type' => 'PD',
                            'deliv_code' => $delivHdr->tr_code,
                            'amt_shipcost' => $delivHdr->amt_shipcost ?? 0,

                            // BillingOrder fields
                            'reffhdr_id' => $delivHdr->id,
                            'reffhdrtr_type' => 'PD',
                            'reffhdrtr_code' => $delivHdr->reff_code, // Kode Purchase Order
                            'reffdtltr_seq' => $delivPacking->tr_seq,
                            'matl_descr' => $matl_descr,
                            'qty_uom' => $picking->matl_uom,
                            'qty_base' => 0, // Will be summed

                            // Additional info for display
                            'deliv_date' => $delivHdr->tr_date,
                            'partner_id' => $delivHdr->partner_id,
                            'partner_code' => $delivHdr->partner_code,
                            'matl_id' => $matl_id,
                            'matl_code' => $matl_code,
                            'matl_uom' => $picking->matl_uom,

                            // Price and amount fields
                            'qty' => 0, // Will be summed from delivery qty
                            'price' => $orderDtl ? $orderDtl->price : 0,
                            'disc_pct' => $orderDtl ? $orderDtl->disc_pct : 0,
                            'disc_amt' => 0, // Will be calculated
                            'amt_beforetax' => 0, // Will be calculated based on delivery qty
                            'amt_tax' => 0, // Will be calculated based on delivery qty
                            'amt' => 0, // Will be calculated based on delivery qty
                            'price_afterdisc' => $orderDtl ? $orderDtl->price_afterdisc : 0,
                            'price_beforetax' => $orderDtl ? $orderDtl->price_beforetax : 0,
                            'amt_adjustdtl' => 0, // Will be calculated
                        ];
                    }

                    // Sum the quantities and amounts
                    $groupedData[$matl_id]['qty_base'] += $delivPacking->qty;
                    $groupedData[$matl_id]['qty'] += $delivPacking->qty; // Use delivery qty, not order qty

                    // Calculate amounts based on delivery qty and order price
                    if ($orderDtl) {
                        $deliveryQty = $delivPacking->qty;
                        $price = $orderDtl->price;
                        $discount = $orderDtl->disc_pct / 100;

                        // Calculate amounts for this delivery
                        $amt_beforetax = $deliveryQty * $price * (1 - $discount);
                        $amt_tax = $deliveryQty * $price * (1 - $discount) * 0.11; // 11% tax on amount after discount
                        $amt = $amt_beforetax + $amt_tax;

                        $groupedData[$matl_id]['amt_tax'] += $amt_tax;
                        $groupedData[$matl_id]['amt_beforetax'] += $amt_beforetax;
                    }
                }
            }
        }

        // Convert grouped data to input_details and calculate final amounts
        foreach ($groupedData as $matl_id => $data) {
            // Calculate final amounts for each material
            if (!empty($data['qty']) && !empty($data['price'])) {
                $qty = $data['qty'];
                $price = $data['price'];
                $discount = $data['disc_pct'] / 100;

                // Calculate amounts based on delivery qty
                $data['disc_amt'] = round($qty * $price * $discount, 0);
                $data['amt_beforetax'] = $qty * $price * (1 - $discount);
                $data['amt_tax'] = $data['amt_tax'] ?? ($qty * $price * (1 - $discount) * 0.11); // Calculate tax if not set
                $data['amt'] = $data['amt_beforetax'] + $data['amt_tax'];
                $data['amt_adjustdtl'] = $data['amt'] - $data['amt_beforetax'] - $data['amt_tax'];
            }

            $this->input_details[] = $data;
        }

        // Store unique delivery data for BillingService
        $this->uniqueDelivData = array_values($uniqueDelivIds);

        // Calculate totals (includes item calculations)
        $this->calculateTotals();
    }

    public function onMaterialChanged($key, $matl_id)
    {
        if (isset($this->input_details[$key])) {
            // Get material data
            $material = Material::find($matl_id);
            if ($material) {
                $this->input_details[$key]['matl_id'] = $matl_id;
                $this->input_details[$key]['matl_code'] = $material->code;
                $this->input_details[$key]['matl_descr'] = $material->name;
            }

            // Recalculate totals
            $this->calculateTotals();
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

            // Use unique delivery data to avoid duplication
            if (!empty($this->uniqueDelivData)) {
                // Create a new detailData with unique delivery entries
                $uniqueDetailData = [];
                foreach ($this->uniqueDelivData as $delivData) {
                    $uniqueDetailData[] = $delivData;
                }
                $detailData = $uniqueDetailData;
            }

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
