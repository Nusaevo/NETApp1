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
    public $taxCode = [];
    // public $SOSend = [];
    public $paymentTerms = [];
    public $suppliers = [];
    // public $warehouses;
    // public $partners;
    public $sales_type;
    // public $tax_invoice;
    public $total_amount = 0;
    public $total_tax = 0;
    public $total_dpp = 0;
    public $total_discount = 0;
    public $trType = "PO";
    public $versionNumber = "0.0";
    // public $npwpOptions = [];
    public $isPanelEnabled = "false";
    // public $notaCount = 0;
    // public $suratJalanCount = 0;
    // public $ddMaterial = [];
    public $object;
    public $object_detail;

    // Detail (item) properties
    public $input_details = [];
    public $materials;
    public $deletedItems = [];

    // Delivery status property - simplified
    public $isDeliv = false;
    // Array untuk track editable status per item (key = index item di input_details)
    public $itemEditableStatus = [];
    public $materialCategory = null; // Tambahan: untuk menyimpan category hasil mapping sales_type
    public $materialQuery = "";
    public $generatedTrCode = null; // Store temporary generated tr_code for validation

    // Properties untuk komponen dropdown search multiple select
    public $items = [];
    public $selectedItems = [];

    protected $masterService;
    protected $orderService;
    protected $inventoryService;

    // Validation rules for header and details
    public $rules = [
        'inputs.tr_code' => 'required',
        'inputs.partner_id' => 'required|min:1',
        'inputs.tax_code' => 'required',
        'input_details.*.matl_id' => 'required',
    ];

    // Event listeners
    protected $listeners = [
        'changeStatus' => 'changeStatus',
        'delete' => 'delete',
        // 'updateAmount' => 'updateAmount',
        'salesTypeOnChanged' => 'salesTypeOnChanged', // tambahkan listener baru
        'onTrCodeChanged' => 'onTrCodeChanged', // listener untuk perubahan tr_code
        'resetToCreateMode' => 'resetToCreateMode', // listener untuk reset ke mode create
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
        // $this->partners = $this->masterService->getCustomers();
        $this->taxCode = $this->masterService->getSOTaxData();
        // $this->SOSend = $this->masterService->getSOSendData();
        $this->paymentTerms = $this->masterService->getPaymentTerm();
        // $this->warehouses = $this->masterService->getWarehouse();
        // $this->materials = $this->masterService->getMaterials();

        // Tambahkan filter material jika sales_type sudah terisi
        // if (!empty($this->inputs['sales_type'])) {
        //     $this->salesTypeOnChanged();
        // }

        if ($this->isEditOrView()) {
            // dd($this->objectIdValue);
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = $this->object->toArray();
            // $this->inputs['tax_invoice'] = $this->object->tax_invoice;
            $this->inputs['tr_code'] = $this->object->tr_code;
            // $this->inputs['partner_name'] = $this->object->partner->code;
            $trDate = $this->object->tr_date ? \Carbon\Carbon::parse($this->object->tr_date) : null;
            $paymentDueDays = is_numeric($this->object->payment_due_days) ? (int)$this->object->payment_due_days : 0;
            $this->inputs['due_date'] = ($trDate && $paymentDueDays > 0)
                ? $trDate->copy()->addDays($paymentDueDays)->format('Y-m-d')
                : ($trDate ? $trDate->format('Y-m-d') : null);
            // dd($this->inputs);
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

    public function onReset()
    {
        $this->reset('inputs', 'input_details');
        $this->object = new OrderHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tax_process_date'] = null;
        $this->inputs['tr_date'] = date('Y-m-d');
        $this->inputs['due_date'] = date('Y-m-d');
        $this->inputs['tr_type'] = $this->trType;
        $this->inputs['curr_code'] = "IDR";
        $this->inputs['curr_id'] = app(ConfigService::class)->getConstIdByStr1('BASE_CURRENCY', $this->inputs['curr_code']);
        $this->inputs['curr_rate'] = 1.00;
        $this->inputs['print_date']=null;
        $this->isDeliv = false;
        
        // Reset generated tr_code
        $this->generatedTrCode = null;
    }

     public function onValidateAndSave()
    {
        // throw new Exception('Gagal menyimpan detail pesanan. Periksa data yang diberikan.');
        if (!$this->orderService) {
            $this->orderService = app(OrderService::class);
        }

        // Validasi tr_code sesuai dengan yang di-generate
        $this->validateGeneratedTrCode();

        // Validasi tr_code sesuai dengan mode dan object
        $this->validateTrCodeConsistency();

        // Validasi duplikasi tr_code
        $this->validateTrCodeDuplicate();

        // Validasi duplikasi matl_id dalam detail
        $this->validateMatlIdDuplicate();

        // Validasi: pastikan item yang tidak editable tidak diubah
        $this->validateNonEditableItems();

        // Jika belum ada delivery, proses normal
        if ($this->actionValue === 'Edit' && $this->object->isOrderCompleted()) {
            $this->dispatch('warning', 'Nota ini tidak bisa di-edit karena status sudah Completed');
            return;
        }

        $headerData = $this->prepareHeaderData();
        $detailData = $this->prepareDetailData();

        // PENTING: Semua item harus dikirim ke service untuk mencegah item non-editable terhapus
        // Service akan menghapus item yang tidak ada di array detailData
        $finalDetailData = [];
        foreach ($detailData as $index => $detail) {
            // Jika item sudah ada di DB (punya id), cek editable status
            if (!empty($detail['id'])) {
                // Cari index di input_details yang sesuai dengan detail ini
                $originalIndex = null;
                foreach ($this->input_details as $key => $inputDetail) {
                    if (isset($inputDetail['id']) && $inputDetail['id'] == $detail['id']) {
                        $originalIndex = $key;
                        break;
                    }
                }

                // Jika ditemukan
                if ($originalIndex !== null) {
                    $isEditable = isset($this->itemEditableStatus[$originalIndex]) ? $this->itemEditableStatus[$originalIndex] : true;

                    if ($isEditable) {
                        // Item editable: gunakan data dari input (bisa diupdate)
                        $finalDetailData[] = $detail;
                    } else {
                        // Item non-editable: ambil data asli dari database (jangan diupdate)
                        $orderDtl = OrderDtl::find($detail['id']);
                        if ($orderDtl) {
                            // Gunakan data asli dari database untuk memastikan tidak ada perubahan
                            // Format sesuai dengan prepareDetailData agar kompatibel
                            $dbData = $orderDtl->toArray();
                            // Pastikan field yang dibutuhkan ada
                            $dbData['price_curr'] = $dbData['price'] ?? 0;
                            $dbData['qty_uom'] = $dbData['qty_uom'] ?? 'PCS';
                            $dbData['price_uom'] = $dbData['price_uom'] ?? 'PCS';
                            $dbData['qty_base'] = $dbData['qty_base'] ?? 1;
                            $dbData['price_base'] = $dbData['price_base'] ?? 1;
                            // Pastikan field yang mungkin tidak ada di toArray() tapi ada di input
                            if (!isset($dbData['disc_amt'])) $dbData['disc_amt'] = 0;
                            if (!isset($dbData['amt_adjustdtl'])) $dbData['amt_adjustdtl'] = 0;

                            $finalDetailData[] = $dbData;
                        } else {
                            // Fallback: gunakan detail dari input jika tidak ditemukan di DB
                            $finalDetailData[] = $detail;
                        }
                    }
                } else {
                    // Tidak ditemukan index, gunakan data dari input
                    $finalDetailData[] = $detail;
                }
            } else {
                // Item baru, selalu include
                $finalDetailData[] = $detail;
            }
        }

        // Hitung total dari semua detail (termasuk yang non-editable)
        $totals = $this->calcTotalFromDetails($finalDetailData);
        $headerData['amt'] = $totals['amt'];
        $headerData['amt_beforetax'] = $totals['amt_beforetax'];
        $headerData['amt_tax'] = $totals['amt_tax'];
        $headerData['amt_adjustdtl'] = $totals['amt_adjustdtl'];

        $order = $this->orderService->saveOrder($headerData, $finalDetailData);

        $this->object = $order['header'];

        $this->redirectToEdit();
    }

    private function prepareHeaderData()
    {
        $headerData = $this->inputs;

        if ($this->actionValue === 'Create') {
            $headerData['status_code'] = Status::OPEN;
        }

        if (empty($headerData['partner_code']) && !empty($headerData['partner_id'])) {
            $partner = Partner::find($headerData['partner_id']);
            $headerData['partner_code'] = $partner ? $partner->code : '';
        }
        return $headerData;
    }
    private function prepareDetailData()
    {
        $detailData = $this->input_details;

        $trSeq = 1;
        foreach ($detailData as $i => &$detail) {
            $detail['tr_seq'] = $trSeq++;
            $detail['qty_uom'] = 'PCS';
            $detail['price_uom'] = 'PCS';
            $detail['price_curr'] = $detail['price'];
            $detail['price_base'] = 1;
            $detail['qty_base'] = 1;
            if ($this->actionValue === 'Create') {
                $detail['status_code'] = Status::OPEN;
            }
        }
        unset($detail);
        return $detailData;
    }

    // private function prepareDetailData()
    // {
    //     $detailData = $this->input_details;

    //     $trSeq = 1;
    //     foreach ($detailData as &$detail) {
    //         $detail['price_curr'] = $detail['price'];
    //         $detail['qty_uom'] = 'PCS';
    //         $detail['price_uom'] = 'PCS';
    //         $detail['qty_base'] = 1;

    //         // Set tr_seq untuk SEMUA item (baik yang baru maupun yang sudah ada)
    //         // Ini mencegah duplicate key violation saat menambah item baru di mode edit
    //         $detail['tr_seq'] = $trSeq++;
    //     }
    //     unset($detail);
    //     return $detailData;
    // }

   public function addItemOnClick()
    {
        // Validasi: sales_type harus dipilih dulu
        if (empty($this->inputs['sales_type'])) {
            $this->dispatch('error', 'Silakan pilih nota MOTOR atau MOBIL terlebih dahulu.');
            return;
        }

        try {
            // Bisa selalu tambah item baru, tidak perlu cek isDeliv
            // karena item baru selalu editable
            $this->input_details[] = populateArrayFromModel(new OrderDtl());
            $key = count($this->input_details) - 1;
            $this->input_details[$key]['gt_process_date'] = null;
            $this->input_details[$key]['disc_pct'] = 0;
            $this->input_details[$key]['price_base'] = 1;

            // Set sebagai editable (item baru)
            $this->itemEditableStatus[$key] = true;
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
        }
    }

     public function trCodeOnClick()
    {
        // Reset ke mode create hanya jika sedang dalam mode edit
        if ($this->actionValue !== 'Create') {
            $this->resetToCreateModeInternal();
        }

        // Tambahkan pengecekan sales_type
        if (empty($this->inputs['sales_type'])) {
            $this->dispatch('error', 'Silakan pilih Tipe Kendaraan terlebih dahulu sebelum generate Nomor.');
            return;
        }

        // Tambahkan pengecekan tr_date
        if (empty($this->inputs['tr_date'])) {
            $this->dispatch('error', 'Silakan pilih Tanggal Transaksi terlebih dahulu sebelum generate Nomor.');
            return;
        }

        $salesType = $this->inputs['sales_type'];
        $trDate = $this->inputs['tr_date'];
        
        // Generate new tr_code and store in temporary variable
        $newTrCode = app(MasterService::class)->getNewTrCode($this->trType, $salesType, "", $trDate);
        $this->generatedTrCode = $newTrCode;
        $this->inputs['tr_code'] = $newTrCode;
    }

    public function onTrCodeChanged()
    {
        $trCode = trim($this->inputs['tr_code'] ?? '');

        if (empty($trCode)) {
            // Jika tr_code kosong, reset ke mode create tanpa object
            $this->resetToCreateModeInternal();
            return;
        }

        try {
            // Cari OrderHdr berdasarkan tr_code dan tr_type
            $existingOrder = OrderHdr::where('tr_code', $trCode)
                ->where('tr_type', $this->trType)
                ->first();

            if ($existingOrder) {
                // Jika nota ditemukan, load data untuk edit tanpa redirect
                $this->loadOrderByTrCode($existingOrder);
                $this->dispatch('info', "Purchase Order {$trCode} ditemukan. Data telah dimuat untuk edit.");
            } else {
                // Jika tidak ditemukan, biarkan tr_code tetap di field untuk koreksi user
                $this->dispatch('warning', "Nomor Purchase Order '{$trCode}' tidak ditemukan. Silakan periksa kembali atau gunakan nomor lain.");
            }
        } catch (Exception $e) {
            $this->dispatch('error', 'Terjadi kesalahan saat mencari Purchase Order: ' . $e->getMessage());
            // Jangan hapus tr_code, biarkan user bisa perbaiki
        }
    }

    private function loadOrderByTrCode($order)
    {
        // Set mode ke edit dan load object
        $this->actionValue = 'Edit';
        $this->objectIdValue = $order->id;
        $this->object = $order;

        // Load semua data order
        $this->inputs = $this->object->toArray();
        $this->inputs['tr_code'] = $this->object->tr_code;
        $this->inputs['partner_name'] = $this->object->partner ? $this->object->partner->code : '';

        // Load payment due date
        $trDate = $this->object->tr_date ? \Carbon\Carbon::parse($this->object->tr_date) : null;
        $paymentDueDays = is_numeric($this->object->payment_due_days) ? (int)$this->object->payment_due_days : 0;
        $this->inputs['due_date'] = ($trDate && $paymentDueDays > 0)
            ? $trDate->copy()->addDays($paymentDueDays)->format('Y-m-d')
            : ($trDate ? $trDate->format('Y-m-d') : null);

        // Load related data
        $this->salesTypeOnChanged();
        $this->loadDetails();
        $this->isPanelEnabled = "false"; // Disable panel karena sudah ada data

        // Update tax settings
        if (!empty($this->inputs['tax_code'])) {
            $this->taxCodeOnChanged();
        }

        // Dispatch event to refresh partner dropdown with loaded partner_id
        $this->dispatch('resetSelect2Dropdowns', [
            'partner_id' => $this->inputs['partner_id'] ?? null
        ]);

        // Sinkronisasi version number dengan object yang di-load
        $this->versionNumber = $this->object->version_number ?? 1;
        
        // Reset generated tr_code karena mode Edit tidak memerlukan validasi generated tr_code
        $this->generatedTrCode = null;
    }

    private function resetToCreateModeInternal()
    {
        // Reset ke mode create murni
        $this->actionValue = 'Create';
        $this->objectIdValue = null;
        $this->object = new OrderHdr();
        $this->resetInputsToDefault();
        $this->isPanelEnabled = "true";

        // Reset version number untuk create mode
        $this->versionNumber = 1;

        // Reset Select2 dropdowns
        $this->dispatch('resetSelect2Dropdowns');
    }

    /**
     * Helper method to check if delete button should be visible
     */
    public function canShowDeleteButton()
    {
        return $this->actionValue !== 'Create' && !empty($this->object->id);
    }

    private function resetInputsToDefault()
    {
        // Reset semua inputs ke nilai default
        $this->inputs = populateArrayFromModel(new OrderHdr());
        $this->inputs['tr_date'] = date('Y-m-d');
        $this->inputs['due_date'] = date('Y-m-d');
        $this->inputs['tr_type'] = $this->trType;
        $this->inputs['curr_code'] = "IDR";
        $this->inputs['curr_id'] = app(ConfigService::class)->getConstIdByStr1('BASE_CURRENCY', $this->inputs['curr_code']);
        $this->inputs['curr_rate'] = 1.00;

        // Set default payment term
        $this->setDefaultPaymentTerm();

        // Reset detail items
        $this->input_details = [];

        // Reset semua totals
        $this->total_amount = 0;
        $this->total_tax = 0;
        $this->total_dpp = 0;
        $this->total_discount = 0;
        
        // Reset generated tr_code
        $this->generatedTrCode = null;
    }



    public function resetToCreateMode()
    {
        // Method untuk reset ke mode create (dipanggil dari tombol "Mode Create")
        $this->resetToCreateModeInternal();
        $this->dispatch('info', 'Mode telah direset ke Create. Silakan input data baru.');
    }

    private function setDefaultPaymentTerm()
    {
        $cod = ConfigConst::where('const_group', 'MPAYMENT_TERMS')->where('str1', 'COD')->first();
        if ($cod) {
            $this->inputs['payment_term_id'] = $cod->id;
            $this->inputs['payment_term'] = $cod->str1;
            $this->inputs['payment_due_days'] = $cod->num1;
            if (!empty($this->inputs['tr_date'])) {
                $this->inputs['due_date'] = \Carbon\Carbon::parse($this->inputs['tr_date'])->addDays($cod->num1)->format('Y-m-d');
            }
        }
    }

    public function taxCodeOnChanged()
    {
        try {
            $configData = ConfigConst::select('id', 'num1', 'str1')
                ->where('const_group', 'TRX_SO_TAX')
                ->where('str1', $this->inputs['tax_code'])
                ->first();

            $this->inputs['tax_id'] = $configData->id;
            $this->inputs['tax_pct'] = $configData->num1;

            // Recalculate all item amounts when tax changes
            foreach ($this->input_details as $key => $detail) {
                $this->calcItemAmount($key);
            }
        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

     public function matlIdOnChanged($key, $matl_id)
    {
        if ($matl_id) {
            $material = Material::find($matl_id);
            if ($material) {
                // Cari UOM untuk material ini
                $matlUom = MatlUom::where('matl_id', $matl_id)
                    ->where('matl_uom', $material->uom)
                    ->first();

                // Set data material terlebih dahulu
                $this->input_details[$key]['matl_id'] = $material->id;
                $this->input_details[$key]['matl_code'] = $material->code;
                $this->input_details[$key]['matl_uom'] = $material->uom;
                $this->input_details[$key]['matl_descr'] = $material->name;
                $this->input_details[$key]['disc_pct'] = 0;

                // Set harga berdasarkan UOM yang ditemukan
                if ($matlUom) {
                    $this->input_details[$key]['price'] = $matlUom->last_buying_price ?? 0;
                } else {
                    // Jika UOM tidak ditemukan, set harga 0 dan tampilkan warning
                    $this->input_details[$key]['price'] = 0;
                    $this->dispatch('warning', __('generic.error.material_uom_not_found') . ' - Material: ' . $material->name . ' (UOM: ' . $material->uom . ')');
                }

                $this->calcItemAmount($key);
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
        if (!empty($this->input_details[$key]['qty']) && !empty($this->input_details[$key]['price'])) {
            // Calculate basic amount with discount
            $qty = $this->input_details[$key]['qty'];
            $price = $this->input_details[$key]['price'];
            $discount = $this->input_details[$key]['disc_pct'] / 100;
            $taxValue = $this->inputs['tax_pct'] / 100;
            $priceAfterDisc = $price * (1 - $discount);
            $priceBeforeTax = round($priceAfterDisc / (1 + $taxValue),0);
            // dd($this->inputs['tax_code'], $price, $priceAfterDisc, $priceBeforeTax, $taxValue);
            $this->input_details[$key]['disc_amt'] = round($qty * $price * $discount,0);

            $this->input_details[$key]['amt'] = 0;
            $this->input_details[$key]['amt_beforetax'] = 0;
            $this->input_details[$key]['amt_tax'] = 0;
            if ($this->inputs['tax_code'] === 'I') {
                $this->input_details[$key]['price_beforetax'] = $priceBeforeTax;
                // Catatan: khusus untuk yang include PPN
                // DPP dihitung dari harga setelah disc dikurangi PPN dibulatkan ke rupiah * qty
                $this->input_details[$key]['amt_beforetax'] = $priceBeforeTax * $qty ;
                // PPN dihitung dari DPP * PPN dibulatkan ke rupiah
                $this->input_details[$key]['amt_tax'] = round($this->input_details[$key]['amt_beforetax'] * $taxValue,0);
            } else if ($this->inputs['tax_code'] === 'E') {
                $this->input_details[$key]['price_beforetax'] = $priceAfterDisc;
                $this->input_details[$key]['amt_beforetax'] = $priceAfterDisc * $qty;
                $this->input_details[$key]['amt_tax'] = round($priceAfterDisc * $qty * $taxValue,0);
            } else if ($this->inputs['tax_code'] === 'N') {
                $this->input_details[$key]['price_beforetax'] = $priceAfterDisc;
                $this->input_details[$key]['amt_beforetax'] = $priceAfterDisc * $qty;
                $this->input_details[$key]['amt_tax'] = 0;
            }
            // amt selalu dihitung tanpa dipengaruhi tax_code: (price after discount) * qty
            $this->input_details[$key]['amt'] = $priceAfterDisc * $qty;
            $this->input_details[$key]['price_afterdisc'] = $priceAfterDisc;
            $this->input_details[$key]['amt_adjustdtl'] = $this->input_details[$key]['amt'] - $this->input_details[$key]['amt_beforetax'] - $this->input_details[$key]['amt_tax'];

            $this->total_amount = 0;
            $this->total_discount = 0;
            $this->total_dpp = 0;
            $this->total_tax = 0;
            // dd($this->input_details, $this->input_details[$key]['disc_amt']);
            foreach ($this->input_details as $detail) {
                // Total header dipengaruhi tax_code
                if ($this->inputs['tax_code'] === 'E') {
                    // Exclude PPN pada harga item; total = DPP + PPN
                    $this->total_amount += ($detail['amt_beforetax'] + $detail['amt_tax']);
                } else {
                    // Include atau Non PPN: total = amt (sudah termasuk/ tanpa PPN sesuai kebijakan)
                    $this->total_amount += $detail['amt'];
                }
                $this->total_discount += $detail['disc_amt'] ?? 0;
                $this->total_dpp += $detail['amt_beforetax'];
                $this->total_tax += $detail['amt_tax'];
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

            // Check if item is editable (per-item check)
            $isEditable = isset($this->itemEditableStatus[$index]) ? $this->itemEditableStatus[$index] : true;
            if (!$isEditable) {
                $this->dispatch('error', 'Tidak dapat menghapus item karena sudah full delivery (qty_reff >= qty).');
                return;
            }

            // Track deleted items with IDs
            if (isset($this->input_details[$index]['id'])) {
                $this->deletedItems[] = $this->input_details[$index]['id'];
            }

            unset($this->input_details[$index]);
            unset($this->itemEditableStatus[$index]);
            $this->input_details = array_values($this->input_details);

            // Re-index itemEditableStatus
            $this->itemEditableStatus = array_values($this->itemEditableStatus);

            // $this->dispatch('success', __('generic.string.delete_item'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id, $this->object->tr_type)
                ->orderBy('tr_seq')
                ->get();

            $this->input_details = $this->object_detail->toArray();
            // dd($this->input_details);
            foreach ($this->input_details as $key => &$detail) {
                if (!isset($detail['disc_amt'])) $detail['disc_amt'] = 0;
                if (!isset($detail['amt_adjustdtl'])) $detail['amt_adjustdtl'] = 0;
                $this->calcItemAmount($key);
            }
            unset($detail);

            // Check delivery status after loading details
            $this->checkDeliveryStatus();
        }
    }

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

    private function calcTotalFromDetails($detailData)
    {
        $amt = 0;
        $amtBeforeTax = 0;
        $amtTax = 0;
        $amtAdjustDtl = 0;

        foreach ($detailData as $detail) {
            $amt += $detail['amt'] ?? 0;
            $amtBeforeTax += $detail['amt_beforetax'] ?? 0;
            $amtTax += $detail['amt_tax'] ?? 0;
            $amtAdjustDtl += $detail['amt_adjustdtl'] ?? 0;
        }

        return [
            'amt' => $amt,
            'amt_beforetax' => $amtBeforeTax,
            'amt_tax' => $amtTax,
            'amt_adjustdtl' => $amtAdjustDtl
        ];
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
    // public function delete()
    // {
    //     try {
    //         if ($this->object->isOrderCompleted()) {
    //             $this->dispatch('warning', 'Nota ini tidak bisa dihapus karena status sudah Completed');
    //             return;
    //         }

    //         if (!$this->object->isOrderEnableToDelete()) {
    //             $this->dispatch('warning', 'Nota ini tidak bisa dihapus karena memiliki material yang sudah dijual.');
    //             return;
    //         }

    //         $this->object->status_code = Status::CANCEL;
    //         $this->object->save();
    //         $this->object->delete();

    //         $this->dispatch('success', __('generic.string.delete'));
    //     } catch (Exception $e) {
    //         $this->dispatch('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
    //     }

    //     return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
    // }

    public function delete()
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

    // /**
    //  * Update version number for printing
    //  */
    // protected function updateVersionNumber()
    // {
    //     $this->versionNumber = "{$this->notaCount}.{$this->suratJalanCount}";
    // }

    /**
     * Print invoice
     */
    // public function printInvoice()
    // {
    //     try {
    //         $this->notaCount++;
    //         $this->updateVersionNumber();

    //         return redirect()->route('TrdTire1.Transaction.PurchaseOrder.PrintPdf', [
    //             'action' => encryptWithSessionKey('Edit'),
    //             'objectId' => encryptWithSessionKey($this->object->id)
    //         ]);
    //     } catch (Exception $e) {
    //         $this->dispatch('error', $e->getMessage());
    //     }
    // }

    // /**
    //  * Print delivery document
    //  */
    // public function printDelivery()
    // {
    //     try {
    //         $this->suratJalanCount++;
    //         $this->updateVersionNumber();

    //         return redirect()->route('TrdTire1.Transaction.PurchaseDelivery.PrintPdf', [
    //             'action' => encryptWithSessionKey('Edit'),
    //             'objectId' => encryptWithSessionKey($this->object->id)
    //         ]);
    //     } catch (Exception $e) {
    //         $this->dispatch('error', $e->getMessage());
    //     }
    // }

    public function salesTypeOnChanged()
    {
        $salesType = $this->inputs['sales_type'] ?? null;
        // Reset detail items when sales_type changes to mirror Sales Order behavior
        $this->input_details = [];

        // Only clear tr_code when creating a new document
        if ($this->actionValue === 'Create') {
            $this->inputs['tr_code'] = '';
        }

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

        $this->materialQuery = "SELECT id, code, name FROM materials WHERE status_code = 'A' AND deleted_at IS NULL AND category IN ($categoryList)";

    }

    /**
     * Check delivery status per-item
     * Set isDeliv = true jika ada item yang sudah full delivery (qty_reff >= qty)
     * Set itemEditableStatus untuk setiap item berdasarkan qty_reff vs qty
     */
    public function checkDeliveryStatus()
    {
        $this->isDeliv = false; // Default: field aktif (bisa diedit)
        $this->itemEditableStatus = []; // Reset editable status per item

        foreach ($this->input_details as $key => $detail) {
            if (isset($detail['id']) && !empty($detail['id'])) {
                $orderDtl = OrderDtl::find($detail['id']);
                if ($orderDtl) {
                    // Cek apakah item editable berdasarkan qty_reff
                    $isItemEditable = $orderDtl->isEditable();
                    $this->itemEditableStatus[$key] = $isItemEditable;

                    // Jika ada item yang tidak bisa diedit (full delivery), set flag
                    if (!$isItemEditable) {
                        $this->isDeliv = true; // Ada item yang full delivery
                    }
                } else {
                    // Item baru atau belum ada di DB, default editable
                    $this->itemEditableStatus[$key] = true;
                }
            } else {
                // Item baru (belum ada id), default editable
                $this->itemEditableStatus[$key] = true;
            }
        }

        // Dispatch warning jika ada item yang full delivery
        if ($this->isDeliv) {
            $nonEditableCount = count(array_filter($this->itemEditableStatus, fn($status) => !$status));
            if ($nonEditableCount > 0) {
                $this->dispatch('warning', "Ada {$nonEditableCount} item yang sudah full delivery dan tidak dapat diedit.");
            }
        }
    }

    /**
     * Validasi tr_code sesuai dengan yang di-generate
     */
    private function validateGeneratedTrCode()
    {
        // Skip validasi jika dalam mode Edit (tidak perlu validasi generated tr_code)
        if ($this->actionValue === 'Edit') {
            return;
        }

        $currentTrCode = $this->inputs['tr_code'] ?? null;
        if (empty($currentTrCode)) {
            throw new Exception('Kode transaksi harus diisi');
        }

        // Jika ada generated tr_code dan berbeda dengan current tr_code
        if (!empty($this->generatedTrCode) && $this->generatedTrCode !== $currentTrCode) {
            throw new Exception("Nomor Purchase Order '{$currentTrCode}' berbeda dengan yang di-generate ('{$this->generatedTrCode}'). Silakan gunakan nomor yang di-generate atau klik tombol Generate Nomor lagi.");
        }
    }

    /**
     * Validasi konsistensi tr_code sesuai dengan mode dan object
     */
    private function validateTrCodeConsistency()
    {
        $trCode = $this->inputs['tr_code'] ?? null;
        if (empty($trCode)) {
            throw new Exception('Kode transaksi harus diisi');
        }

        if ($this->actionValue === 'Edit') {
            // Mode Edit: pastikan tr_code masih sesuai dengan object yang sedang diedit
            if (!$this->object || !$this->object->id) {
                throw new Exception('Data Purchase Order tidak ditemukan untuk mode edit');
            }

            // Cari order berdasarkan tr_code
            $orderByTrCode = OrderHdr::where('tr_code', $trCode)
                ->where('tr_type', $this->trType)
                ->first();

            if (!$orderByTrCode) {
                throw new Exception("Nomor Purchase Order '{$trCode}' tidak ditemukan di database");
            }

            // Pastikan tr_code yang diinput masih merujuk ke object yang sama
            if ($orderByTrCode->id !== $this->object->id) {
                throw new Exception("Nomor Purchase Order '{$trCode}' tidak sesuai dengan data yang sedang diedit. Silakan refresh halaman atau gunakan nomor Purchase Order yang benar.");
            }
        } else if ($this->actionValue === 'Create') {
            // Mode Create: pastikan tr_code belum ada di database
            $existingOrder = OrderHdr::where('tr_code', $trCode)
                ->where('tr_type', $this->trType)
                ->first();

            if ($existingOrder) {
                throw new Exception("Nomor Purchase Order '{$trCode}' sudah ada di database dengan ID: {$existingOrder->id}. Silakan generate nomor baru atau gunakan mode edit untuk mengubah data tersebut.");
            }
        }
    }

    /**
     * Validasi duplikasi tr_code
     */
    private function validateTrCodeDuplicate()
    {
        $trCode = $this->inputs['tr_code'] ?? null;
        if (empty($trCode)) {
            throw new Exception('Kode transaksi harus diisi');
        }



        $query = OrderHdr::where('tr_code', $trCode)->where('tr_type', $this->trType);

        if ($this->actionValue === 'Edit' && !empty($this->object->id)) {
            $query->where('id', '!=', $this->object->id);
        }

        if ($query->exists()) {
            throw new Exception("Kode transaksi '{$trCode}' sudah digunakan. Silakan gunakan kode yang berbeda.");
        }
    }

    /**
     * Validasi duplikasi matl_id dalam detail items
     */
    private function validateMatlIdDuplicate()
    {
        if (empty($this->input_details)) return;

        $matlIds = [];
        foreach ($this->input_details as $index => $detail) {
            $matlId = $detail['matl_id'] ?? null;
            if (empty($matlId)) continue;

            if (in_array($matlId, $matlIds)) {
                $material = Material::find($matlId);
                $materialName = $material ? $material->name : "ID: {$matlId}";
                throw new Exception("Material '{$materialName}' sudah ada dalam detail. Silakan hapus salah satu atau gunakan material yang berbeda.");
            }
            $matlIds[] = $matlId;
        }
    }

    /**
     * Validasi: item yang tidak editable (full delivery) tidak boleh diubah
     */
    private function validateNonEditableItems()
    {
        foreach ($this->input_details as $key => $detail) {
            // Skip item baru (belum ada id)
            if (empty($detail['id'])) {
                continue;
            }

            // Cek apakah item editable
            $isEditable = isset($this->itemEditableStatus[$key]) ? $this->itemEditableStatus[$key] : true;

            if (!$isEditable) {
                // Item tidak editable, validasi bahwa data tidak berubah dari DB
                $orderDtl = OrderDtl::find($detail['id']);
                if ($orderDtl) {
                    // Cek perubahan pada field yang penting
                    $hasChanged = false;
                    $changedFields = [];

                    if (abs(($detail['qty'] ?? 0) - ($orderDtl->qty ?? 0)) > 0.0001) {
                        $hasChanged = true;
                        $changedFields[] = 'qty';
                    }
                    if (abs(($detail['price'] ?? 0) - ($orderDtl->price ?? 0)) > 0.0001) {
                        $hasChanged = true;
                        $changedFields[] = 'price';
                    }
                    if (abs(($detail['disc_pct'] ?? 0) - ($orderDtl->disc_pct ?? 0)) > 0.0001) {
                        $hasChanged = true;
                        $changedFields[] = 'disc_pct';
                    }
                    if (($detail['matl_id'] ?? null) != ($orderDtl->matl_id ?? null)) {
                        $hasChanged = true;
                        $changedFields[] = 'material';
                    }

                    if ($hasChanged) {
                        throw new Exception("Item dengan material '{$orderDtl->matl_code}' tidak dapat diubah karena sudah full delivery (qty_reff >= qty).");
                    }
                }
            }
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
