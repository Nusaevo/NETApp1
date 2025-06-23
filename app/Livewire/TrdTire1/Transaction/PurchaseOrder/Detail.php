<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseOrder;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\{DelivHdr, OrderHdr, OrderDtl};
use App\Models\TrdTire1\Master\{Partner, Material, MatlUom};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\TrdTire1\InventoryService;
use App\Services\TrdTire1\Master\MasterService;
use App\Services\TrdTire1\OrderService;
use App\Services\TrdTire1\DeliveryService;
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
    protected $orderService;

    // Validation rules for header and details
    public $rules = [
        'inputs.tr_code' => 'required',
        'inputs.partner_name' => 'required',
        'inputs.tax_flag' => 'required',
        'input_details.*.qty' => 'required',
        'input_details.*.matl_id' => 'required',
        'input_details.*.disc_pct' => 'nullable|numeric|min:0|max:100',
    ];

    // Event listeners
    protected $listeners = [
        'changeStatus' => 'changeStatus',
        'delete' => 'delete',
        'updateAmount' => 'updateAmount',
        'onSalesTypeChanged' => 'onSalesTypeChanged', // tambahkan listener baru
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

            $this->total_dpp = number_format((float)$dpp, 2, ',', '.');
            $this->total_tax = number_format((float)$ppn, 2, ',', '.');

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

        // Tambahkan filter material jika sales_type sudah terisi
        if (!empty($this->inputs['sales_type'])) {
            $this->onSalesTypeChanged();
        }

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
                    $this->input_details[$key]['disc_pct'] = 0.00; // Default 0%
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

            // Konversi diskon dari format koma ke titik
            $discountPercent = $this->input_details[$key]['disc_pct'] ?? 0;
            if (is_string($discountPercent)) {
                $discountPercent = str_replace(',', '.', $discountPercent);
            }
            $discountPercent = (float)$discountPercent;

            $discountAmount = $amount * ($discountPercent / 100);
            $this->input_details[$key]['amt'] = $amount - $discountAmount;
        } else {
            $this->input_details[$key]['amt'] = 0;
        }

        // Pastikan amt_idr juga menyimpan nilai numerik (sama dengan amt)
        $this->input_details[$key]['amt_idr'] = $this->input_details[$key]['amt'];
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
                // Ambil array aslinya (misalnya ['disc_pct' => 555.00000, ...])
                $arr = populateArrayFromModel($detail);

                // Jika disc_pct ada, bagi 10 supaya kelihatan 55.5
                if (isset($arr['disc_pct']) && is_numeric($arr['disc_pct'])) {
                    $arr['disc_pct'] = $arr['disc_pct'] / 10;
                }

                $this->input_details[$key] = $arr;

                // Hitung ulang amount dengan disc_pct yang sudah di‐scale down
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
     * Create delivery header and details automatically for CASH payment terms
     */
    private function createDeliveryHdr()
    {
        try {
            // Generate delivery code dengan format CASH + tr_code PO
            $poTrCode = $this->object->tr_code ?? '';
            if (empty($poTrCode)) {
                throw new Exception('Kode transaksi PO tidak ditemukan');
            }
            $delivCode = 'CASH' . ' - ' . $poTrCode;

            // Ambil data OrderDtl dari database
            $orderDetails = OrderDtl::where('trhdr_id', $this->object->id)
                ->where('tr_type', 'PO')
                ->orderBy('tr_seq')
                ->get();

            if ($orderDetails->isEmpty()) {
                throw new Exception('Tidak ada data detail PO yang valid');
            }

            // Prepare header data dengan nilai default
            $headerData = [
                'tr_code' => $delivCode,
                'tr_date' => $this->inputs['tr_date'] ?? date('Y-m-d'),
                'reff_date' => $this->inputs['reff_date'] ?? date('Y-m-d'),
                'tr_type' => 'PD',
                'partner_id' => $this->inputs['partner_id'] ?? null,
                'partner_code' => $this->inputs['partner_code'] ?? null,
                'wh_id' => $this->inputs['wh_id'] ?? 0,
                'wh_code' => $this->inputs['wh_code'] ?? '',
                'reffhdrtr_code' => $this->object->tr_code,
                'status_code' => 'OPEN',
                'tax_invoice' => $this->inputs['tax_invoice'] ?? null,
                'send_to' => $this->inputs['send_to'] ?? 'Supplier',
                'curr_id' => $this->inputs['curr_id'] ?? 1,
                'curr_code' => $this->inputs['curr_code'] ?? 'USD'
            ];

            // Prepare detail data dengan pengecekan null
            $detailData = [];
            foreach ($orderDetails as $key => $orderDetail) {
                // Pastikan data material lengkap
                $material = Material::find($orderDetail->matl_id);
                if (!$material) {
                    continue; // Skip jika material tidak ditemukan
                }

                $detailData[] = [
                    'tr_seq' => $key + 1,
                    'matl_id' => $orderDetail->matl_id,
                    'matl_code' => $material->code,
                    'matl_descr' => $material->name,
                    'matl_uom' => $material->uom,
                    'qty' => $orderDetail->qty ?? 0,
                    'wh_id' => $this->inputs['wh_id'] ?? 0,
                    'wh_code' => $this->inputs['wh_code'] ?? '',
                    'reffdtl_id' => $orderDetail->id,
                    'reffhdrtr_type' => 'PO',
                    'reffhdrtr_code' => $this->object->tr_code,
                    'reffdtltr_seq' => $orderDetail->tr_seq
                ];
            }

            // Validasi data sebelum proses
            if (empty($detailData)) {
                throw new Exception('Tidak ada data detail yang valid untuk dibuat delivery. Pastikan semua item memiliki data material yang lengkap.');
            }

            // Create delivery using delivery service
            $deliveryService = app(DeliveryService::class);
            $result = $deliveryService->addDelivery($headerData, $detailData);

            $this->dispatch('success', 'Purchase Delivery berhasil dibuat otomatis dengan kode: ' . $delivCode);
            return $result;

        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal membuat Purchase Delivery: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate and save the purchase order
     */
    public function onValidateAndSave()
    {
        if (!$this->orderService) {
            $this->orderService = app(OrderService::class);
        }

        // Validasi input dan format diskon
        $this->validateAndFormatInputs();

        try {
            if ($this->actionValue === 'Edit' && $this->object->isOrderCompleted()) {
                $this->dispatch('warning', 'Nota ini tidak bisa di-edit karena status sudah Completed');
                return;
            }

            // Prepare data
            $headerData = $this->prepareHeaderData();
            $detailData = $this->prepareDetailData();

            DB::beginTransaction();
            try {
                // Cek payment term dan proses sesuai jenisnya
                if ($this->processPaymentTerm($headerData, $detailData)) {
                    return;
                }

                // Proses normal untuk non-CASH payment
                $this->processNormalOrder($headerData, $detailData);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
    }

    /**
     * Validasi input dan format diskon
     */
    private function validateAndFormatInputs()
    {
        if (!isset($this->input_details) || !is_array($this->input_details)) {
            $this->input_details = [];
        }

        foreach ($this->input_details as &$detail) {
            if (isset($detail['disc_pct']) && is_string($detail['disc_pct'])) {
                $discPct = preg_replace('/[^0-9,.]/', '', $detail['disc_pct']);
                $discPct = str_replace(',', '.', $discPct);
                $parts = explode('.', $discPct);
                if (count($parts) > 2) {
                    $discPct = $parts[0] . '.' . implode('', array_slice($parts, 1));
                }
                $detail['disc_pct'] = round((float)$discPct, 5);
            }
        }
        unset($detail);

        $this->validate($this->rules);
    }

    /**
     * Siapkan data header
     */
    private function prepareHeaderData()
    {
        
        $headerData = $this->inputs;

        // Set default values
        $defaults = [
            'tr_type' => $this->trType,
            'tr_date' => date('Y-m-d'),
            'total_amt' => $this->total_amount,
            'total_amt_tax' => $this->total_tax,
            'tax_flag' => 'N'
        ];

        foreach ($defaults as $key => $value) {
            $headerData[$key] = $headerData[$key] ?? $value;
        }

        return $headerData;
    }

    /**
     * Siapkan data detail
     */
    private function prepareDetailData()
    {
        $detailData = $this->input_details;

        foreach ($detailData as $i => &$detail) {
            // Set material data
            $material = Material::find($detail['matl_id']);
            if ($material) {
                $detail['matl_code'] = $material->code;
                $detail['matl_descr'] = $material->name;
                $detail['matl_uom'] = $material->uom;
            }

            // Set price data
            $matlUom = MatlUom::where('matl_id', $detail['matl_id'])
                ->where('matl_uom', $detail['matl_uom'])
                ->first();

            $detail['price'] = $matlUom ? $matlUom->selling_price : 0;
            $detail['price_uom'] = $detail['matl_uom'] ?? 'PCS';

            // Calculate amounts
            $this->calculateDetailAmounts($detail, $this->inputs['tax_flag'] ?? 'N', $this->inputs['tax_pct'] ?? 0);

            // Set transaction fields
            $detail['tr_type'] = $this->trType;
            $detail['tr_seq'] = $i + 1;
        }
        unset($detail);

        return $detailData;
    }

    /**
     * Hitung jumlah detail
     */
    private function calculateDetailAmounts(&$detail, $taxFlag, $taxPct)
    {
        $qty = $detail['qty'] ?? 0;
        $price = $detail['price'] ?? 0;
        $discPct = $detail['disc_pct'] / 100;
        $detail['amt'] = $qty * $price * (1 - $discPct);

        $taxPct = $taxPct / 100;

        if ($taxFlag === 'I') {
            $detail['dpp'] = $detail['amt'] / (1 + $taxPct);
            $detail['ppn'] = $detail['amt'] - $detail['dpp'];
        } elseif ($taxFlag === 'E') {
            $detail['dpp'] = $detail['amt'];
            $detail['ppn'] = $detail['amt'] * $taxPct;
        } else {
            $detail['dpp'] = $detail['amt'];
            $detail['ppn'] = 0;
        }

        $detail['amt_tax'] = $detail['dpp'] + $detail['ppn'];
    }

    /**
     * Proses payment term
     */
    private function processPaymentTerm($headerData, $detailData)
    {
        if (empty($headerData['payment_term_id'])) {
            return false;
        }

        $paymentTerm = ConfigConst::find($headerData['payment_term_id']);
        if (!$paymentTerm || $paymentTerm->str2 !== 'CASH') {
            return false;
        }

        $headerData['payment_term'] = $paymentTerm->str1;
        $headerData['payment_due_days'] = $paymentTerm->num1;

        if ($this->actionValue === 'Create') {
            $order = $this->orderService->addOrder($headerData, $detailData);
            $this->object = $order;
        } else {
            $this->orderService->modOrder($this->object->id, $headerData, $detailData);
        }

        $this->createDeliveryHdr();
        DB::commit();

        $this->dispatch('success', 'Purchase Order dan Delivery berhasil ' .
            ($this->actionValue === 'Create' ? 'disimpan' : 'diperbarui') . '.');

        return redirect()->route(
            $this->appCode . '.Transaction.PurchaseOrder.Detail',
            [
                'action'   => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id),
            ]
        );
    }

    /**
     * Proses order normal (non-CASH)
     */
    private function processNormalOrder($headerData, $detailData)
    {
        if ($this->actionValue === 'Create') {
            $order = $this->orderService->addOrder($headerData, $detailData);
            $this->dispatch('success', 'Purchase Order berhasil disimpan.');
            $objectId = $order->id;
        } else {
            $this->orderService->modOrder($this->object->id, $headerData, $detailData);
            $this->dispatch('success', 'Purchase Order berhasil diperbarui.');
            $objectId = $this->object->id;
        }

        DB::commit();

        return redirect()->route(
            $this->appCode . '.Transaction.PurchaseOrder.Detail',
            [
                'action'   => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($objectId),
            ]
        );
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
        $this->total_amount = (float)$data['total_amount'];
        $this->total_discount = (float)$data['total_discount'];

        // Recalculate DPP and PPN
        $this->calculateDPPandPPN($this->inputs['tax_flag'] ?? '');
    }

    /**
     * Handle sales_type change and filter materials accordingly
     */
    public function onSalesTypeChanged()
    {
        $salesType = $this->inputs['sales_type'] ?? null;
        if (!$salesType) {
            $this->materials = [];
            return;
        }

        // Ambil data material lengkap dari database
        $allMaterials = Material::all();
        $filtered = [];

        foreach ($allMaterials as $material) {
            $category = $material->category ?? null;
            if (!$category) continue;

            $categoryNorm = trim(strtoupper($category));
            $config = ConfigConst::where('const_group', 'MMATL_CATEGORY')
                ->whereRaw('UPPER(TRIM(str2)) = ?', [$categoryNorm])
                ->first();

            if ($config && $config->str1 === $salesType) {
                $filtered[] = [
                    'label' => $material->code . ' - ' . $material->name,
                    'value' => $material->id,
                ];
            }
        }

        $this->materials = $filtered;
        $this->input_details = [];
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
