<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseOrder;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl};
use App\Models\TrdTire1\Master\{Partner, Material, MatlUom};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdTire1\Master\MasterService;
use Illuminate\Support\Facades\{Session, DB};
use Exception;

use function PHPUnit\Framework\throwException;

class Detail extends BaseComponent
{
    // Header properties
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
    public $tax_invoice;
    public $transaction_id;
    public $payments;
    public $total_amount = 0;
    public $total_tax = 0;
    public $total_dpp = 0;
    public $total_discount = 0;
    public $trType = "PO";
    public $versionNumber = "0.0";

    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];

    public $returnIds = [];
    public $currencyRate = 0;
    public $npwpOptions = [];
    public $isPanelEnabled = "false";
    public $notaCount = 0;
    public $suratJalanCount = 0;

    // Detail (item) properties
    public $input_details = [];
    public $materials;
    public $deletedItems = [];
    protected $masterService;

    // Validation rules for header and details
    public $rules = [
        'inputs.tr_code' => 'required',
        'inputs.partner_name' => 'required',
        'inputs.tax_flag' => 'required',
        'input_details.*.qty' => 'required',
        'input_details.*.matl_id' => 'required',
    ];

    // Event listeners
    protected $listeners = [
        'changeStatus' => 'changeStatus',
        'delete' => 'delete',
        'updateAmount' => 'updateAmount',
    ];

    /**
     * Generate transaction code based on sales_type and tax_invoice
     */
    public function getTransactionCode()
    {
        if (!isset($this->inputs['sales_type'])) {
            $this->dispatch('warning', 'Tipe Kendaraan harus diisi');
            return;
        }

        $sales_type = $this->inputs['sales_type'];
        $tax_invoice = isset($this->inputs['tax_invoice']) && $this->inputs['tax_invoice'];
        $this->inputs['tr_code'] = OrderHdr::generateTransactionId($sales_type, 'PO', $tax_invoice);
    }

    /**
     * Handle tax invoice checkbox change
     */
    public function onTaxInvoiceChanged()
    {
        $this->getTransactionCode();
    }

    /**
     * Calculate tax when tax type changes
     */
    public function onSOTaxChange()
    {
        try {
            $configData = ConfigConst::select('num1', 'str1')
                ->where('const_group', 'TRX_SO_TAX')
                ->where('str1', $this->inputs['tax_flag'])
                ->first();

            $this->inputs['tax_value'] = $configData->num1 ?? 0;
            $taxType = $configData->str1 ?? '';
            $this->inputs['tax_pct'] = $this->inputs['tax_value'];

            $this->calculateDPPandPPN($taxType);
        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    /**
     * Calculate DPP (taxable base) and PPN (tax)
     */
    public function calculateDPPandPPN($taxType)
    {
        try {
            $taxValue = (float)($this->inputs['tax_value'] ?? 0);
            $totalAmount = (float)$this->total_amount;

            if ($taxType === 'I') {
                $dpp = $totalAmount / (1 + $taxValue / 100);
                $ppn = $totalAmount - $dpp;
            } elseif ($taxType === 'E') {
                $dpp = $totalAmount;
                $ppn = ($taxValue / 100) * $totalAmount;
            } else {
                $dpp = $totalAmount;
                $ppn = 0;
            }

            $this->total_dpp = round($dpp, 2);
            $this->total_tax = round($ppn, 2);

            $this->dispatch('updateDPP', $this->total_dpp);
        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    /**
     * Handle partner change and load NPWP data
     */
    public function onPartnerChanged()
    {
        $partner = Partner::find($this->inputs['partner_id']);
        $this->npwpOptions = $partner ? $this->listNpwp($partner) : null;
    }

    /**
     * Extract NPWP list from partner details
     */
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

    /**
     * Initialize component data before rendering
     */
    protected function onPreRender()
    {
        $this->customValidationAttributes = [
            'inputs.tax' => $this->trans('tax'),
            'inputs.tr_code' => $this->trans('tr_code'),
            'inputs.partner_id' => $this->trans('partner_id'),
            'input_details.*.matl_id' => $this->trans('code'),
            'input_details.*.qty' => $this->trans('qty'),
        ];

        $this->masterService = new MasterService();
        $this->partners = $this->masterService->getCustomers();
        $this->SOTax = $this->masterService->getSOTaxData();
        $this->SOSend = $this->masterService->getSOSendData();
        $this->paymentTerms = $this->masterService->getPaymentTerm();
        $this->warehouses = $this->masterService->getWarehouse();
        $this->materials = $this->masterService->getMaterials();

        if ($this->isEditOrView()) {
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['status_code_text'] = $this->object->status_Code_text;
            $this->inputs['tax_invoice'] = $this->object->tax_invoice;
            $this->inputs['tr_code'] = $this->object->tr_code;
            $this->inputs['partner_name'] = $this->object->partner->code;
            $this->inputs['textareasupplier'] = $this->object->partner->name . "\n" . $this->object->partner->address . "\n" . $this->object->partner->city;
            // Hitung due_date berdasarkan tr_date dan payment_due_days
            $trDate = $this->object->tr_date ? \Carbon\Carbon::parse($this->object->tr_date) : null;
            $paymentDueDays = is_numeric($this->object->payment_due_days) ? (int)$this->object->payment_due_days : 0;
            $this->inputs['due_date'] = ($trDate && $paymentDueDays > 0)
                ? $trDate->copy()->addDays($paymentDueDays)->format('Y-m-d')
                : ($trDate ? $trDate->format('Y-m-d') : null);
            $this->onPartnerChanged();
            $this->loadDetails();
        } else {
            $this->isPanelEnabled = "true";
            $this->inputs['tax_flag'] = 'I';
        }

        if (!empty($this->inputs['tax_flag'])) {
            $this->onSOTaxChange();
        }
    }

    /**
     * Reset form data
     */
    public function onReset()
    {
        $this->reset('inputs', 'input_details');
        $this->object = new OrderHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_date'] = date('Y-m-d');
        $this->inputs['due_date'] = date('Y-m-d');
        $this->inputs['tr_type'] = $this->trType;
        $this->inputs['curr_id'] = ConfigConst::CURRENCY_DOLLAR_ID;
        $this->inputs['curr_code'] = "USD";
        $this->inputs['send_to'] = "Pelanggan";
        $this->inputs['wh_code'] = 18;
        $this->inputs['partner_id'] = 0;
    }

    /**
     * Add a new item to the purchase order
     */
    public function addItem()
    {
        try {
            $this->input_details[] = [
                'matl_id' => null,
                'qty' => null,
            ];
            $this->dispatch('success', __('generic.string.add_item'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Handle material selection and auto-populate fields
     */
    public function onMaterialChanged($key, $matl_id)
    {
        if ($matl_id) {
            $material = Material::find($matl_id);
            if ($material) {
                $matlUom = MatlUom::where('matl_id', $matl_id)->first();
                if ($matlUom) {
                    $this->input_details[$key]['matl_id'] = $material->id;
                    $this->input_details[$key]['price'] = $matlUom->selling_price;
                    $this->input_details[$key]['matl_uom'] = $material->uom;
                    $this->input_details[$key]['matl_descr'] = $material->name;
                    $this->updateItemAmount($key);
                } else {
                    $this->dispatch('error', __('generic.error.material_uom_not_found'));
                }
            } else {
                $this->dispatch('error', __('generic.error.material_not_found'));
            }
        }
    }

    /**
     * Calculate item amount based on quantity, price and discount
     */
    public function updateItemAmount($key)
    {
        if (!empty($this->input_details[$key]['qty']) && !empty($this->input_details[$key]['price'])) {
            $amount = $this->input_details[$key]['qty'] * $this->input_details[$key]['price'];
            $discountPercent = $this->input_details[$key]['disc_pct'] ?? 0;
            $discountAmount = $amount * ($discountPercent / 100);
            $this->input_details[$key]['amt'] = $amount - $discountAmount;
        } else {
            $this->input_details[$key]['amt'] = 0;
        }

        $this->input_details[$key]['amt_idr'] = rupiah($this->input_details[$key]['amt']);
        $this->recalculateTotals();
    }

    /**
     * Recalculate all totals
     */
    public function recalculateTotals()
    {
        $this->calculateTotalAmount();
        $this->calculateTotalDiscount();

        // After recalculating amount and discount, calculate DPP and PPN
        if (!empty($this->inputs['tax_flag'])) {
            $this->calculateDPPandPPN($this->inputs['tax_flag']);
        }

        $this->dispatch('updateAmount', [
            'total_amount' => $this->total_amount,
            'total_discount' => $this->total_discount,
            'total_tax' => $this->total_tax,
            'total_dpp' => $this->total_dpp,
        ]);
    }

    /**
     * Calculate total amount from all items
     */
    private function calculateTotalAmount()
    {
        $this->total_amount = array_sum(array_map(function ($detail) {
            $qty = $detail['qty'] ?? 0;
            $price = $detail['price'] ?? 0;
            $discountPercent = $detail['disc_pct'] ?? 0;
            $amount = $qty * $price;
            $discountAmount = $amount * ($discountPercent / 100);
            return $amount - $discountAmount;
        }, $this->input_details));

        $this->total_amount = round($this->total_amount, 2);
    }

    /**
     * Calculate total discount from all items
     */
    private function calculateTotalDiscount()
    {
        $this->total_discount = array_sum(array_map(function ($detail) {
            $qty = $detail['qty'] ?? 0;
            $price = $detail['price'] ?? 0;
            $discountPercent = $detail['disc_pct'] ?? 0;
            $amount = $qty * $price;
            return $amount * ($discountPercent / 100);
        }, $this->input_details));

        $this->total_discount = round($this->total_discount, 2);
    }

    /**
     * Delete an item from the purchase order
     */
    public function deleteItem($index)
    {
        try {
            if (!isset($this->input_details[$index])) {
                throw new Exception(__('generic.error.delete_item', ['message' => 'Item tidak ditemukan.']));
            }

            // Track deleted items with IDs
            if (isset($this->input_details[$index]['id'])) {
                $this->deletedItems[] = $this->input_details[$index]['id'];
            }

            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            $this->dispatch('success', __('generic.string.delete_item'));
            $this->recalculateTotals();
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Load detail items for the purchase order
     */
    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $objectDetails = OrderDtl::GetByOrderHdr($this->object->id, $this->object->tr_type)
                ->orderBy('tr_seq')
                ->get();

            foreach ($objectDetails as $key => $detail) {
                $this->input_details[$key] = populateArrayFromModel($detail);
                $this->updateItemAmount($key);
            }
        }
    }

    /**
     * Handle payment term change and update due date
     */
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

    /**
     * Validate and save the purchase order
     */
    public function onValidateAndSave()
    {
        // Validate inputs
        $this->validate([
            'inputs.tr_code' => 'required',
            'inputs.partner_id' => 'required',
            'inputs.tax_flag' => 'required',
            'input_details.*.qty' => 'required',
            'input_details.*.matl_id' => 'required',
        ]);

        // Check if order can be edited
        foreach ($this->input_details as $key => $detail) {
            if (isset($detail['qty_reff']) && $detail['qty'] < $detail['qty_reff']) {
                throw new Exception('Qty tidak boleh kurang dari Qty Reff pada item ke-' . ($key + 1));
            }
        }

        try {
            // Begin transaction
            DB::beginTransaction();

            // Set partner code
            if (!isNullOrEmptyNumber($this->inputs['partner_id'])) {
                $partner = Partner::find($this->inputs['partner_id']);
                $this->inputs['partner_code'] = $partner->code;
            }

            // Set payment term
            if (!empty($this->inputs['payment_term_id'])) {
                $paymentTerm = ConfigConst::find($this->inputs['payment_term_id']);
                $this->inputs['payment_term'] = $paymentTerm->str1;
                // $this->inputs['payment_due_days'] = $paymentTerm->num1; // Hapus baris ini
            }

            // Hitung payment_due_days berdasarkan tr_date dan due_date
            if (!empty($this->inputs['tr_date']) && !empty($this->inputs['due_date'])) {
                $trDate = \Carbon\Carbon::parse($this->inputs['tr_date']);
                $dueDate = \Carbon\Carbon::parse($this->inputs['due_date']);
                $this->inputs['payment_due_days'] = $trDate->diffInDays($dueDate, false);
            } else {
                $this->inputs['payment_due_days'] = null;
            }
            // Jangan simpan due_date ke model
            unset($this->inputs['due_date']);

            // Save order header
            $this->object->saveOrderHeader($this->appCode, $this->trType, $this->inputs, 'SALESORDER_LASTID');

            // Save order details if available
            if (!empty($this->input_details)) {
                // Get existing details for comparison
                $existingDetails = OrderDtl::where('trhdr_id', $this->object->id)
                    ->where('tr_type', $this->object->tr_type)
                    ->get()
                    ->keyBy('tr_seq')
                    ->toArray();

                // Delete removed items
                $itemsToDelete = array_diff_key($existingDetails, array_flip(array_keys($this->input_details)));
                foreach ($itemsToDelete as $tr_seq => $detail) {
                    $orderDtl = OrderDtl::find($detail['id']);
                    if ($orderDtl) {
                        $orderDtl->forceDelete();
                    }
                }

                // Save or update each detail item
                foreach ($this->input_details as $key => $detail) {
                    $tr_seq = $key + 1;
                    $orderDtl = OrderDtl::firstOrNew([
                        'tr_code' => $this->object->tr_code,
                        'tr_seq' => $tr_seq,
                    ]);

                    $detail['tr_code'] = $this->object->tr_code;
                    $detail['trhdr_id'] = $this->object->id;
                    $detail['tr_type'] = $this->object->tr_type;
                    $detail['qty_reff'] = $detail['qty_reff'] ?? '0';

                    // Get material data
                    $material = Material::find($detail['matl_id']);
                    if ($material) {
                        $detail['matl_code'] = $material->code;
                        $detail['matl_uom'] = $material->uom;
                        $detail['price_uom'] = $material->uom;
                    }

                    // Calculate amt_tax based on tax_pct
                    $taxPct = (float)($this->inputs['tax_pct'] ?? 0);
                    $detail['amt_tax'] = round(($detail['amt'] ?? 0) * ($taxPct / 100), 2);

                    $orderDtl->fill($detail);
                    $orderDtl->save();
                }
            }

            // Calculate total amount from all details
            $totalAmt = 0;
            foreach ($this->input_details as $detail) {
                $qty = $detail['qty'] ?? 0;
                $price = $detail['price'] ?? 0;
                $disc_pct = $detail['disc_pct'] ?? 0;
                $amount = $qty * $price;
                $discount = $amount * ($disc_pct / 100);
                $totalAmt += ($amount - $discount);
            }

            // Update total_amt and total_amt_tax
            $this->object->total_amt = round($totalAmt, 2);

            // Calculate total_amt_tax based on tax flag
            if ($this->object->tax_flag === 'I') {
                $this->object->total_amt_tax = $this->object->total_amt;
            } elseif ($this->object->tax_flag === 'E') {
                $taxPct = ($this->object->tax_pct ?? 0) / 100;
                $this->object->total_amt_tax = round($this->object->total_amt * (1 + $taxPct), 2);
            } else {
                $this->object->total_amt_tax = $this->object->total_amt;
            }

            $this->object->save();

            // Commit transaction
            DB::commit();

            // Redirect if in create mode
            if ($this->actionValue === 'Create') {
                return redirect()->route($this->appCode . '.Transaction.PurchaseOrder.Detail', [
                    'action' => encryptWithSessionKey('Edit'),
                    'objectId' => encryptWithSessionKey($this->object->id)
                ]);
            }

        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    /**
     * Delete the purchase order
     */
    public function delete()
    {
        try {
            if ($this->object->isOrderCompleted()) {
                $this->dispatch('warning', 'Nota ini tidak bisa dihapus karena status sudah Completed');
                return;
            }

            if (!$this->object->isOrderEnableToDelete()) {
                $this->dispatch('warning', 'Nota ini tidak bisa dihapus karena memiliki material yang sudah dijual.');
                return;
            }

            $this->object->status_code = Status::NONACTIVE;
            $this->object->save();
            $this->object->delete();

            $this->dispatch('success', __('generic.string.disable'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
    }

    /**
     * Delete the transaction (header and/or details)
     */
    public function deleteTransaction()
    {
        try {
            // 1) Pastikan object ada dan memang tercatat di DB
            if (!$this->object || is_null($this->object->id) ||
                !OrderHdr::where('id', $this->object->id)->exists()) {
                throw new \Exception(__('Data header tidak ditemukan'));
            }

            DB::beginTransaction();

            // 2) Hapus detail jika ada
            $detailsExist = OrderDtl::where('trhdr_id', $this->object->id)
                ->where('tr_type', $this->object->tr_type)
                ->exists();

            if ($detailsExist) {
                $orderDetails = OrderDtl::where('trhdr_id', $this->object->id)
                    ->where('tr_type', $this->object->tr_type)
                    ->get();

                foreach ($orderDetails as $detail) {
                    $detail->forceDelete(); // Event deleting di OrderDtl akan menangani pengurangan qty_fgr
                }
            }

            // 3) Hapus header
            $this->object->forceDelete();

            DB::commit();

            $this->dispatch('success', __('Data berhasil terhapus'));
            return redirect()->route(str_replace('.Detail', '', $this->baseRoute));

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('error', __('generic.error.delete', [
                'message' => $e->getMessage()
            ]));
        }
    }

    /**
     * Update version number for printing
     */
    protected function updateVersionNumber()
    {
        $this->versionNumber = "{$this->notaCount}.{$this->suratJalanCount}";
    }

    /**
     * Print invoice
     */
    public function printInvoice()
    {
        try {
            $this->notaCount++;
            $this->updateVersionNumber();

            return redirect()->route('TrdTire1.Transaction.PurchaseOrder.PrintPdf', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);
        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    /**
     * Print delivery document
     */
    public function printDelivery()
    {
        try {
            $this->suratJalanCount++;
            $this->updateVersionNumber();

            return redirect()->route('TrdTire1.Transaction.PurchaseDelivery.PrintPdf', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);
        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    /**
     * Open partner selection dialog
     */
    public function openPartnerDialogBox()
    {
        $this->partnerSearchText = '';
        $this->suppliers = [];
        $this->selectedPartners = [];
        $this->dispatch('openPartnerDialogBox');
    }

    /**
     * Search for partners/suppliers
     */
    public function searchPartners()
    {
        if (!empty($this->partnerSearchText)) {
            $searchTerm = strtoupper($this->partnerSearchText);
            $this->suppliers = Partner::where('grp', Partner::SUPPLIER)
                ->where(function ($query) use ($searchTerm) {
                    $query->whereRaw("UPPER(code) LIKE ?", ["%{$searchTerm}%"])
                        ->orWhereRaw("UPPER(name) LIKE ?", ["%{$searchTerm}%"]);
                })
                ->get();
        } else {
            $this->dispatch('error', "Mohon isi kode atau nama supplier");
        }
    }

    /**
     * Select/deselect a partner
     */
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

    /**
     * Confirm partner selection
     */
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
            $this->inputs['partner_name'] = $partner->code;
            $this->inputs['textareasupplier'] = $partner->name . "\n" . $partner->address . "\n" . $partner->city;
            $this->dispatch('success', "Supplier berhasil dipilih.");
            $this->dispatch('closePartnerDialogBox');
            $this->onPartnerChanged();
        }
    }

    /**
     * Update totals when changes are made
     */
    public function updateAmount($data)
    {
        $this->total_amount = $data['total_amount'];
        $this->total_discount = $data['total_discount'];

        // Recalculate DPP and PPN
        $this->calculateDPPandPPN($this->inputs['tax_flag'] ?? '');
    }

    /**
     * Render view
     */
    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
