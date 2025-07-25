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
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = $this->object->toArray();
            $this->inputs['status_code_text'] = $this->object->status_Code_text;
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
        $this->inputs['tr_date'] = date('Y-m-d');
        $this->inputs['due_date'] = date('Y-m-d');
        $this->inputs['tr_type'] = $this->trType;
        $this->inputs['curr_code'] = "IDR";
        $this->inputs['curr_id'] = app(ConfigService::class)->getConstIdByStr1('BASE_CURRENCY', $this->inputs['curr_code']);
        $this->inputs['curr_rate'] = 1.00;
        $this->inputs['print_date']=null;
        $this->isDeliv = false;
    }

     public function onValidateAndSave()
    {
        // throw new Exception('Gagal menyimpan detail pesanan. Periksa data yang diberikan.');
        if (!$this->orderService) {
            $this->orderService = app(OrderService::class);
        }

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

        $headerData = $this->prepareHeaderData();
        $detailData = $this->prepareDetailData();
        $totals = $this->calcTotalFromDetails($detailData);
        $headerData['amt'] = $totals['amt'];
        $headerData['amt_beforetax'] = $totals['amt_beforetax'];
        $headerData['amt_tax'] = $totals['amt_tax'];
        $headerData['amt_adjustdtl'] = $totals['amt_adjustdtl'];

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
            $detail['qty_base'] = 1;
            // Pastikan disc_pct selalu default 0 jika belum ada
            if (!isset($detail['disc_pct']) || $detail['disc_pct'] === null) {
                $detail['disc_pct'] = 0;
            }
            if ($this->actionValue === 'Create') {
                $detail['status_code'] = Status::OPEN;
            }
        }
        unset($detail);
        return $detailData;
    }

   public function addItemOnClick()
    {
        // Validasi: sales_type harus dipilih dulu
        if (empty($this->inputs['sales_type'])) {
            $this->dispatch('error', 'Silakan pilih nota MOTOR atau MOBIL terlebih dahulu.');
            return;
        }

        try {
            // Check if can add new item
            if ($this->isDeliv) {
                $this->dispatch('error', 'Tidak dapat menambah item baru karena ada item yang sudah terkirim.');
                return;
            }
            $item = populateArrayFromModel(new OrderDtl());
            $item['disc_pct'] = 0;
            $item['price_base'] = 1;
            $this->input_details[] = $item;
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
        }
    }

     public function trCodeOnClick()
    {
        $this->inputs['tr_code'] = app(MasterService::class)->getNewTrCode($this->trType,"","");
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
                $matlUom = MatlUom::where('matl_id', $matl_id)
                    -> where('matl_uom', $material->uom)->first();
                // dd($matlUom);
                if ($matlUom) {
                    $this->input_details[$key]['price'] = $matlUom->last_buying_price;
                } else {
                    $this->dispatch('error', __('generic.error.material_uom_not_found'));
                }
                $this->input_details[$key]['matl_id'] = $material->id;
                $this->input_details[$key]['matl_code'] = $material->code;
                $this->input_details[$key]['matl_uom'] = $material->uom;
                $this->input_details[$key]['matl_descr'] = $material->name;
                $this->input_details[$key]['disc_pct'] = 0;
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
                // Total Nota dihiitung dari harga setelah disc * qty
                // selisih yang timbul antara Total Nota dan DPP + PPN diabaikan
                // priceAdjustment
                $this->input_details[$key]['amt'] = $priceAfterDisc * $qty;
            } else if ($this->inputs['tax_code'] === 'E') {
                $this->input_details[$key]['price_beforetax'] = $priceAfterDisc;
                $this->input_details[$key]['amt_beforetax'] = $priceAfterDisc * $qty;
                $this->input_details[$key]['amt_tax'] = round($priceAfterDisc * $qty * $taxValue,0);
                $this->input_details[$key]['amt'] = $this->input_details[$key]['amt_beforetax'] + $this->input_details[$key]['amt_tax'];
            } else if ($this->inputs['tax_code'] === 'N') {
                $this->input_details[$key]['price_beforetax'] = $priceAfterDisc;
                $this->input_details[$key]['amt_beforetax'] = $priceAfterDisc * $qty;
                $this->input_details[$key]['amt_tax'] = 0;
                $this->input_details[$key]['amt'] = $priceAfterDisc * $qty;
            }
            $this->input_details[$key]['price_afterdisc'] = $priceAfterDisc;
            $this->input_details[$key]['amt_adjustdtl'] = $this->input_details[$key]['amt'] - $this->input_details[$key]['amt_beforetax'] - $this->input_details[$key]['amt_tax'];

            $this->total_amount = 0;
            $this->total_discount = 0;
            $this->total_dpp = 0;
            $this->total_tax = 0;
            // dd($this->input_details, $this->input_details[$key]['disc_amt']);
            foreach ($this->input_details as $detail) {
                $this->total_amount += $detail['amt'];
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

            $this->object->status_code = Status::CANCEL;
            $this->object->save();
            $this->object->delete();

            $this->dispatch('success', __('generic.string.disable'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
    }

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

        // Ambil semua matl_id yang sedang dipilih di input_details
        $selectedMatlIds = collect($this->input_details)->pluck('matl_id')->filter()->unique()->toArray();
        $selectedMatlIdsStr = '';
        if (!empty($selectedMatlIds)) {
            $selectedMatlIdsStr = implode(',', $selectedMatlIds);
        }

        $mainQuery = "SELECT id, code, name FROM materials WHERE status_code = 'A' AND deleted_at IS NULL AND category IN ($categoryList)";
        // Jika ada matl_id yang tidak ada di hasil utama, tambahkan dengan UNION
        if (!empty($selectedMatlIdsStr)) {
            $unionQuery = "SELECT id, code, name FROM materials WHERE id IN ($selectedMatlIdsStr)";
            $this->materialQuery = "$mainQuery UNION $unionQuery";
        } else {
            $this->materialQuery = $mainQuery;
        }
    }

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

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
