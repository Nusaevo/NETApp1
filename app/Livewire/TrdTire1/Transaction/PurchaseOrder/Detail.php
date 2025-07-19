<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseOrder;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivDtl, OrderHdr, OrderDtl, BillingHdr, BillingDtl};
use App\Models\TrdTire1\Master\{Partner, Material, MatlUom};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\TrdTire1\Status;
use App\Services\SysConfig1\ConfigService;
use App\Services\TrdTire1\InventoryService;
use App\Services\TrdTire1\Master\MasterService;
use App\Services\TrdTire1\OrderService;
use App\Services\TrdTire1\DeliveryService;
use Illuminate\Support\Facades\{Session, DB};
use Exception;
use Illuminate\Support\Number;

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
    public $total_amount = 0;
    public $total_tax = 0;
    public $total_dpp = 0;
    public $total_discount = 0;
    public $trType = "PO";
    public $versionNumber = "0.0";
    public $npwpOptions = [];
    public $isPanelEnabled = "false";
    public $notaCount = 0;
    public $suratJalanCount = 0;
    public $ddMaterial = [];
    public $object;
    public $object_detail;

    // Detail (item) properties
    public $input_details = [];
    public $materials;
    public $deletedItems = [];

    // Delivery status property - simplified
    public $isDeliv = false;
    public $materialCategory = null; // Tambahan: untuk menyimpan category hasil mapping sales_type
    public $materialQuery = "";

    protected $masterService;
    protected $orderService;
    protected $inventoryService;

    // Validation rules for header and details
    public $rules = [
        'inputs.tr_code' => 'required',
        'inputs.partner_id' => 'required',
        'inputs.tax_code' => 'required',
        // 'input_details.*.qty' => 'required',
        'input_details.*.matl_id' => 'required',
    ];

    // Event listeners
    protected $listeners = [
        'changeStatus' => 'changeStatus',
        'delete' => 'delete',
        // 'updateAmount' => 'updateAmount',
        'salesTypeOnChanged' => 'salesTypeOnChanged', // tambahkan listener baru
    ];

    // Livewire lifecycle hooks
    public function updated($propertyName)
    {
        if ($propertyName === 'input_details') {
            $this->checkDeliveryStatus();
        }
    }

    // Constructor untuk menginisialisasi services
    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue, $additionalParam);

        $this->orderService = app(OrderService::class);
    }



    /**
     * Handle partner change and load NPWP data
     */
    // public function partnerIdOnChanged()
    // {
    //     $partner = Partner::find($this->inputs['partner_id']);
    //     $this->npwpOptions = $partner ? $this->listNpwp($partner) : null;
    // }

    /**
     * Handle supplier selection from dropdown search
     */
    // public function onSupplierSelected($partnerId)
    // {
    //     if ($partnerId && $partnerId !== '0') {
    //         $partner = Partner::find($partnerId);
    //         if ($partner) {
    //             $this->inputs['partner_id'] = $partnerId;
    //             $this->inputs['partner_name'] = $partner->name;

    //             // Update detail supplier textarea
    //             $this->inputs['textareasupplier'] =
    //                 "Kode: " . $partner->code . "\n" .
    //                 "Nama: " . $partner->name . "\n" .
    //                 "Alamat: " . ($partner->address ?? '-') . "\n" .
    //                 "Telepon: " . ($partner->phone ?? '-') . "\n" .
    //                 "Email: " . ($partner->email ?? '-');

    //             // Load NPWP data
    //             $this->npwpOptions = $this->listNpwp($partner);

    //             // Trigger partner changed event
    //             $this->partnerIdOnChanged();
    //         }
    //     } else {
    //         // Clear partner data when selection is cleared
    //         $this->inputs['partner_id'] = null;
    //         $this->inputs['partner_name'] = '';
    //         $this->inputs['textareasupplier'] = '';
    //         $this->npwpOptions = null;
    //     }
    // }

    /**
     * Extract NPWP list from partner details
     */
    // private function listNpwp($partner)
    // {
    //     $partnerDetail = $partner->PartnerDetail;

    //     if ($partnerDetail && $partnerDetail->wp_details) {
    //         $wpDetails = $partnerDetail->wp_details;

    //         if (is_string($wpDetails)) {
    //             $wpDetails = json_decode($wpDetails, true);
    //         }

    //         if (is_array($wpDetails)) {
    //             return array_map(function ($item) {
    //                 return [
    //                     'label' => $item['npwp'],
    //                     'value' => $item['npwp'],
    //                 ];
    //             }, $wpDetails);
    //         }
    //     }

    //     return null;
    // }

    /**
     * Initialize component data before rendering
     */
    protected function onPreRender()
    {
        // Pastikan services sudah diinisialisasi
        // $this->initializeServices();

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
            $this->salesTypeOnChanged();
        }

        if ($this->isEditOrView()) {
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = $this->object->toArray(); // Populate inputs from the model
            // $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['status_code_text'] = $this->object->status_Code_text;
            $this->inputs['tax_invoice'] = $this->object->tax_invoice;
            $this->inputs['tr_code'] = $this->object->tr_code;
            $this->inputs['partner_name'] = $this->object->partner->code;
            $this->inputs['textareasupplier'] = $this->object->partner->name . "\n" . $this->object->partner->address . "\n" . $this->object->partner->city;
            $trDate = $this->object->tr_date ? \Carbon\Carbon::parse($this->object->tr_date) : null;
            $paymentDueDays = is_numeric($this->object->payment_due_days) ? (int)$this->object->payment_due_days : 0;
            $this->inputs['due_date'] = ($trDate && $paymentDueDays > 0)
                ? $trDate->copy()->addDays($paymentDueDays)->format('Y-m-d')
                : ($trDate ? $trDate->format('Y-m-d') : null);
            $this->salesTypeOnChanged();
            $this->loadDetails();   
            // dd($this->input_details);
        } else {
            $this->isPanelEnabled = "true";
            $this->inputs['tax_code'] = 'I';
        }

        if (!empty($this->inputs['tax_code'])) {
            $this->taxCodeOnChanged();
        }
        // dd($this->input_details);
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
        $this->inputs['curr_code'] = "IDR";
        $this->inputs['curr_id'] = app(ConfigService::class)->getConstIdByStr1('BASE_CURRENCY', $this->inputs['curr_code']);
        $this->inputs['curr_rate'] = 1.00;
        $this->inputs['send_to'] = "Pelanggan";
        $this->inputs['partner_id'] = 0;
        $this->isDeliv = false;
    }

    /**
     * Add a new item to the purchase order
     */
    public function addItemOnClick()
    {
        // Validasi: sales_type harus dipilih dulu
        if (empty($this->inputs['sales_type'])) {
            $this->dispatch('error', 'Silakan pilih tipe kendaraan terlebih dahulu sebelum menambah item!');
            return;
        }

        try {
            // Check if can add new item
            if ($this->isDeliv) {
                $this->dispatch('error', 'Tidak dapat menambah item baru karena ada item yang sudah memiliki delivery.');
                return;
            }

            $this->input_details[] = [
                'matl_id' => null,
                'qty' => null,
                'price' => null,
                'disc_pct' => null,
                'dpp' => null,
                'ppn' => null,
                'amt' => null,
                'disc_amt' => null,
            ];
            $this->dispatch('success', __('generic.string.add_item'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Generate transaction code based on sales_type and tax_invoice
     */
    public function trCodeOnClick()
    {
        $this->inputs['tr_code'] = app(MasterService::class)->getNewTrCode($this->trType);
    }

    /**
     * Calculate tax when tax type changes
     */
    public function taxCodeOnChanged()
    {
        try {
            $configData = ConfigConst::select('id', 'num1', 'str1')
                ->where('const_group', 'TRX_SO_TAX')
                ->where('str1', $this->inputs['tax_code'])
                ->first();

            $this->inputs['tax_id'] = $configData->id;
            $this->inputs['tax_value'] = $configData->num1;
            // $taxType = $configData->str1 ?? '';
            $this->inputs['tax_pct'] = $this->inputs['tax_value'];

            // Recalculate all item amounts when tax changes
            foreach ($this->input_details as $key => $detail) {
                $this->calcItemAmount($key);
            }

        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }
    /**
     * Handle material selection and auto-populate fields
     */
    public function matlIdOnChanged($key, $matl_id)
    {
        if ($matl_id) {
            $material = Material::find($matl_id);
            if ($material) {
                $matlUom = MatlUom::where('matl_id', $matl_id)->first();
                if ($matlUom) {
                    $this->input_details[$key]['matl_id'] = $material->id;
                    $this->input_details[$key]['price'] = $matlUom->last_buying_price ?: 0;
                    $this->input_details[$key]['matl_uom'] = $material->uom;
                    $this->input_details[$key]['matl_descr'] = $material->name;
                    $this->input_details[$key]['disc_pct'] = 0.00; // Default 0%
                    $this->calcItemAmount($key);
                    // dd($this->input_details[$key]);
                    // $this->input_details = array_values($this->input_details);
                } else {
                    $this->dispatch('error', __('generic.error.material_uom_not_found'));
                }
            } else {
                $this->dispatch('error', __('generic.error.material_not_found'));
            }
        }
    }

    public function priceOnChanged($key)
    {
        $this->calcItemAmount($key);
    }

    public function qtyOnChanged($key)
    {
        $this->calcItemAmount($key);
    }

    public function discPctOnChanged($key)
    {
        $this->calcItemAmount($key);
    }

    public function calcItemAmount($key)
    {
        // dd($this->input_details[$key]);
        if (!empty($this->input_details[$key]['qty']) && !empty($this->input_details[$key]['price'])) {
            // Calculate basic amount with discount
            $qty = $this->input_details[$key]['qty'];
            $price = $this->input_details[$key]['price'];
            $discountPercent = $this->input_details[$key]['disc_pct'];

            $amountGross = $qty * $price;
            // dd($this->input_details[$key], $amountGross, $discountPercent);
            $discountAmount = $amountGross * ($discountPercent / 100);
            $this->input_details[$key]['amt'] = $amountGross - $discountAmount;
            $this->input_details[$key]['disc_amt'] = $discountAmount;

            // Calculate tax amounts
            $taxFlag = $this->inputs['tax_code'];
            $taxValue = $this->inputs['tax_pct'];
            $taxPctDecimal = $taxValue / 100;
            $amount = $this->input_details[$key]['amt'];
            
            $this->input_details[$key]['dpp'] = 0;
            $this->input_details[$key]['ppn'] = 0;
            if ($taxFlag === 'I') {
                $this->input_details[$key]['dpp'] = round($amount / (1 + $taxPctDecimal), 0);
                $this->input_details[$key]['ppn'] = $amount - $this->input_details[$key]['dpp'];
            } elseif ($taxFlag === 'E') {
                $this->input_details[$key]['dpp'] = $amount;
                $this->input_details[$key]['ppn'] = $amount * $taxPctDecimal;
            } else {
                $this->input_details[$key]['dpp'] = $amount;
                $this->input_details[$key]['ppn'] = 0;
            }

            // $tesdpp = number_format((float)$this->input_details[$key]['dpp'], 5, ',', '.');
            // dd($tesdpp, $this->input_details[$key]['dpp']);
            $this->input_details[$key]['amt_tax'] = $this->input_details[$key]['dpp'] + $this->input_details[$key]['ppn'];
            
            $this->total_amount = 0;
            $this->total_discount = 0;
            $this->total_dpp = 0;
            $this->total_tax = 0;
            // dd($this->input_details, $this->input_details[$key]['disc_amt']);
            foreach ($this->input_details as $detail) {
                $this->total_amount += $detail['amt'];
                $this->total_discount += $detail['disc_amt'] ?? 0;
                $this->total_dpp += $detail['dpp'];
                $this->total_tax += $detail['ppn'];
            }
            // Format as Rupiah
            $this->total_amount = rupiah($this->total_amount);
            $this->total_discount = rupiah($this->total_discount);
            $this->total_dpp = rupiah($this->total_dpp);
            $this->total_tax = rupiah($this->total_tax);
            
        }
    }

    public function deleteItem($index)
    {
        try {
            if (!isset($this->input_details[$index])) {
                throw new Exception(__('generic.error.delete_item', ['message' => 'Item tidak ditemukan.']));
            }

            // Check if item is editable
            if ($this->isDeliv) {
                $this->dispatch('error', 'Tidak dapat menghapus item karena sudah memiliki delivery.');
                return;
            }

            // Track deleted items with IDs
            if (isset($this->input_details[$index]['id'])) {
                $this->deletedItems[] = $this->input_details[$index]['id'];
            }

            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);

            $this->dispatch('success', __('generic.string.delete_item'));
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
            $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id, $this->object->tr_type)
                ->orderBy('tr_seq')
                ->get();

            $this->input_details = $this->object_detail->toArray();
            foreach ($this->object_detail as $key => $detail) {
                // $this->input_details[$key] = populateArrayFromModel($detail);
                $this->calcItemAmount($key);
            }
            // dd($this->input_details);
            // dd($this->input_details);

            // Check delivery status after loading details
            $this->checkDeliveryStatus();
        }
    }

    /**
     * Handle payment term change and update due date
     */
    public function paymentTermOnChanged()
    {
        if (!empty($this->inputs['payment_term_id'])) {
            $paymentTerm = ConfigConst::find($this->inputs['payment_term_id']);
            if ($paymentTerm) {
                $dueDays = $paymentTerm->num1;
                $this->inputs['due_date'] = date('Y-m-d', strtotime("+$dueDays days"));
                $this->inputs['payment_term'] = $paymentTerm->str1;
                $this->inputs['payment_due_days'] = $paymentTerm->num1;
            }
        }
    }

    /**
     * Validate and save the purchase order
     */
    public function onValidateAndSave()
    {
        // throw new Exception('Gagal menyimpan detail pesanan. Periksa data yang diberikan.');
        if (!$this->orderService) {
            $this->orderService = app(OrderService::class);
        }

        // Validasi input dan format diskon
        // $this->validateAndFormatInputs();

        // Jika sudah ada delivery, hanya boleh update header
        if ($this->isDeliv) {
            // Prepare data header saja
            $headerData = $this->prepareHeaderData();
            $detailData = []; // Kosongkan detail agar tidak diubah

            // Simpan hanya header (tanpa update detail)
            try {
                $result = $this->orderService->updOrder($this->object->id, $headerData, []);
                if (!$result) {
                    throw new Exception('Gagal mengubah Purchase Order.');
                }
                // $this->dispatch('success', 'Header berhasil diperbarui. Detail tidak diubah karena sudah ada delivery.');
                return $this->redirectToEdit();
            } catch (Exception $e) {
                $this->dispatch('error', $e->getMessage());
                throw new Exception('Gagal memperbarui Purchase Order: ' . $e->getMessage());
            }
        }

        // Jika belum ada delivery, proses normal
        if ($this->actionValue === 'Edit' && $this->object->isOrderCompleted()) {
            $this->dispatch('warning', 'Nota ini tidak bisa di-edit karena status sudah Completed');
            return;
        }

        // Prepare data
        $headerData = $this->prepareHeaderData();
        $detailData = $this->prepareDetailData();

        // dd($detailData);

        // Calculate totals from detail data
        $totals = $this->calculateTotalsFromDetails($detailData);
        $headerData['amt'] = $totals['amt'];
        $headerData['amt_tax'] = $totals['amt_tax'];

        $this->processNormalOrder($headerData, $detailData);
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
            'tax_code' => 'N'
        ];

        foreach ($defaults as $key => $value) {
            $headerData[$key] = $headerData[$key] ?? $value;
        }

        // Fallback untuk partner_code
        if (empty($headerData['partner_code']) && !empty($headerData['partner_id'])) {
            $partner = Partner::find($headerData['partner_id']);
            $headerData['partner_code'] = $partner ? $partner->code : '';
        }
        // Fallback untuk payment_term dan payment_due_days
        if (!empty($headerData['payment_term_id'])) {
            $paymentTerm = ConfigConst::find($headerData['payment_term_id']);
            if ($paymentTerm) {
                if (empty($headerData['payment_term'])) {
                    $headerData['payment_term'] = $paymentTerm->str1;
                }
                if (empty($headerData['payment_due_days'])) {
                    $headerData['payment_due_days'] = $paymentTerm->num1;
                }
            }
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

            // Set price data - gunakan price yang sudah diinput user, bukan dari database
            $detail['price'] = $detail['price'];
            $detail['qty'] = $detail['qty'];
            $detail['disc_pct'] = $detail['disc_pct'];
            $detail['price_uom'] = $detail['matl_uom'] ?? 'PCS';
            $detail['qty_uom'] = 'PCS';
            $detail['qty_base'] = 1;
            $detail['tr_type'] = $this->trType;
            $detail['tr_seq'] = $i + 1;
        }
        unset($detail);

        return $detailData;
    }

    private function calculateTotalsFromDetails($detailData)
    {
        $totalAmt = 0;
        $totalAmtTax = 0;

        foreach ($detailData as $detail) {
            $totalAmt += $detail['amt'] ?? 0;
            $totalAmtTax += $detail['amt_tax'] ?? 0;
        }

        return [
            'amt' => $totalAmt,
            'amt_tax' => $totalAmtTax
        ];
    }

    /**
     * Proses order normal (non-CASH)
     */
    private function processNormalOrder($headerData, $detailData)
    {
        // Save order
        $this->saveOrder($headerData, $detailData);

        return $this->redirectToEdit();
    }

    private function saveOrder($headerData, $detailData)
    {
        if ($this->actionValue === 'Create') {
            $order = $this->orderService->addOrder($headerData, $detailData);
            if (!$order) {
                throw new Exception('Gagal membuat Purchase Order.');
            }
            $this->object = $order;
        } else {
            $result = $this->orderService->updOrder($this->object->id, $headerData, $detailData);
            if (!$result) {
                throw new Exception('Gagal mengubah Purchase Order.');
            }
        }
    }

    private function redirectToEdit()
    {
        $objectId = $this->actionValue === 'Create' ? $this->object->id : $this->object->id;

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

            // Pastikan OrderService sudah diinisialisasi
            // $this->initializeServices();

            $this->object->status_code = Status::CANCEL;
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

            // 2) Validasi apakah order bisa dihapus
            if ($this->object->isOrderCompleted()) {
                $this->dispatch('warning', 'Nota ini tidak bisa dihapus karena status sudah Completed');
                return;
            }

            if (!$this->object->isOrderEnableToDelete()) {
                // Debug: cek qty_reff untuk memastikan validasi berjalan
                $orderDtlWithQtyReff = OrderDtl::where('tr_code', $this->object->tr_code)
                    ->where('qty_reff', '>', 0)
                    ->count();

                $this->dispatch('warning', "Nota ini tidak bisa dihapus karena memiliki material yang sudah dijual. (qty_reff count: {$orderDtlWithQtyReff})");
                return;
            }

            // 3) Pastikan OrderService sudah diinisialisasi
            if (!$this->orderService) {
                $this->orderService = app(OrderService::class);
            }

            // 4) Gunakan OrderService untuk menghapus order
            $this->orderService->delOrder($this->object->id);

            $this->dispatch('success', __('Data berhasil terhapus'));
            return redirect()->route(str_replace('.Detail', '', $this->baseRoute));

        } catch (\Exception $e) {
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
     * Handle sales_type change and filter materials accordingly
     */
    public function salesTypeOnChanged()
    {
        $salesType = $this->inputs['sales_type'] ?? null;
        $this->input_details = [];

        if (!$salesType) {
            $this->materials = [];
            $this->materialQuery = "";
            $this->materialCategory = null;
            return;
        }

        $categories = ConfigConst::where('const_group', 'MMATL_CATEGORY')
            ->where('str1', $salesType)
            ->pluck('str2') // Category names
            ->map(function ($val) {
                return "'" . trim($val) . "'";
            })->toArray();

        $categoryList = implode(',', $categories); // 'BAN DALAM MOBIL','BAN DALAM MOTOR'

        $this->materialQuery = "
            SELECT id, code, name
            FROM materials
            WHERE status_code = 'A'
            AND deleted_at IS NULL
            AND category IN ($categoryList)
        ";
    }

    /**
     * Check delivery status for all items
     */
    public function checkDeliveryStatus()
    {
        $this->isDeliv = false; // Default: field aktif (bisa diedit)

        foreach ($this->input_details as $key => $detail) {
            if (isset($detail['id']) && !empty($detail['id'])) {
                $orderDtl = OrderDtl::find($detail['id']);
                if ($orderDtl && $orderDtl->hasDelivery()) {
                    $this->isDeliv = true; // Ada delivery, field nonaktif
                    $this->dispatch('warning', 'Beberapa item sudah memiliki delivery. Detail item tidak dapat diedit.');
                    break; // Jika ada satu item yang sudah delivery, maka semua nonaktif
                }
            }
        }
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
