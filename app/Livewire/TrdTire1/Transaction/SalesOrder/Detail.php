<?php

namespace App\Livewire\TrdTire1\Transaction\SalesOrder;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl, DelivHdr, DelivDtl, BillingHdr, BillingDtl};
use App\Models\TrdTire1\Master\{Partner, Material, MatlUom};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
use App\Services\SysConfig1\ConfigService;
use App\Services\TrdTire1\OrderService;
use App\Services\TrdTire1\Master\MasterService;
use Illuminate\Support\Facades\DB;
use Exception;

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
    public $tax_doc_flag;
    public $transaction_id;
    public $payments;
    public $deletedItems = [];
    public $newItems = [];
    public $total_amount = 0;
    public $total_tax = 0;
    public $total_dpp = 0;
    public $total_discount = 0;
    public $trType = "SO";
    public $versionNumber = "0.0";
    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];
    public $returnIds = [];
    public $currencyRate = 0;
    public $npwpOptions = [];
    public $shipOptions = [];
    public $notaCount = 0;
    public $suratJalanCount = 0;
    public $isPanelEnabled = "false";
    public $payer = "true";
    public $isDeliv = false; // True jika ada delivery pada salah satu detail

    // Detail (item) properties
    public $input_details = [];
    public $materials;
    protected $masterService;
    protected $orderService;

    // Gabungan validasi untuk header dan detail (item)
    public $rules  = [
        'inputs.tr_code'       => 'required',
        'inputs.partner_id'    => 'required',
    ];

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'delete'        => 'delete',
        'updateAmount'  => 'updateAmount',
        'onSalesTypeChanged' => 'onSalesTypeChanged', // tambahkan listener baru
    ];

    /*
     * Method untuk generate kode transaksi.
     */
    public function getTransactionCode()
    {
        // Tambahkan pengecekan sales_type
        if (empty($this->inputs['sales_type'])) {
            $this->dispatch('error', 'Silakan pilih Tipe Kendaraan terlebih dahulu sebelum generate Nomor.');
            return;
        }
        if (!isset($this->inputs['sales_type']) || !isset($this->trType)) {
            $this->dispatch('warning', 'Tipe Kendaraan dan Jenis Transaksi harus diisi');
            return;
        }
        $sales_type = $this->inputs['sales_type'];
        $tax_doc_flag = !empty($this->inputs['tax_doc_flag']);
        $tr_type = $this->trType;
        $this->inputs['tr_code'] = OrderHdr::generateTransactionId($sales_type, $tr_type, $tax_doc_flag);
    }

    /*
     * Mengatur perhitungan pajak (DPP dan PPN) berdasarkan tax_flag dan tax_value.
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

    /*
     * Ketika partner berubah, ambil data NPWP dan Shipping Address dari detail partner
     */
    public function onPartnerChanged()
    {
        $partner = Partner::find($this->inputs['partner_id']);
        $this->npwpOptions = $partner ? $this->listNpwp($partner) : [];
        $this->shipOptions = $partner ? $this->listShip($partner) : [];
    }

    private function listNpwp($partner)
    {
        if (!$partner->PartnerDetail || empty($partner->PartnerDetail->wp_details)) {
            return [];
        }
        $wpDetails = $partner->PartnerDetail->wp_details;
        if (is_string($wpDetails)) {
            $wpDetails = json_decode($wpDetails, true);
        }
        return is_array($wpDetails) ? array_map(function ($item) {
            return [
                'label' => $item['npwp'],
                'value' => $item['npwp'],
            ];
        }, $wpDetails) : [];
    }

    private function listShip($partner)
    {
        if (!$partner->PartnerDetail || empty($partner->PartnerDetail->shipping_address)) {
            return [];
        }
        $shipDetail = $partner->PartnerDetail->shipping_address;
        if (is_string($shipDetail)) {
            $shipDetail = json_decode($shipDetail, true);
        }
        return is_array($shipDetail) ? array_map(function ($item) {
            return [
                'label' => $item['name'],
                'value' => $item['name'],
            ];
        }, $shipDetail) : [];
    }

    /*
     * Update flag NPWP (payer) ketika tax_doc_flag berubah.
     */
    public function onTaxDocFlagChanged()
    {
        $this->payer = !empty($this->inputs['tax_doc_flag']) ? "true" : "false";
    }

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
        $allMaterials = Material::with('MatlUom')->get();
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

        // Jika dalam mode edit, jangan reset input_details
        if (!$this->isEditOrView()) {
            $this->input_details = [];
        }
    }

    /*
     * Proses inisialisasi data pada render (pre-render).
     * Bila mode edit/view, load data header (OrderHdr) dan detail (OrderDtl).
     */
    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.tax'          => $this->trans('tax'),
            'inputs.tr_code'      => $this->trans('tr_code'),
            'inputs.partner_id'   => $this->trans('partner_id'),
            'inputs.send_to_name' => $this->trans('send_to_name'),
        ];

        $this->orderService = app(OrderService::class);
        $this->masterService = new MasterService();
        $this->partners = $this->masterService->getCustomers();
        $this->SOTax = $this->masterService->getSOTaxData();
        $this->SOSend = $this->masterService->getSOSendData();
        $this->paymentTerms = $this->masterService->getPaymentTerm();
        $this->warehouses = $this->masterService->getWarehouse();

        if ($this->isEditOrView()) {
            if (empty($this->objectIdValue)) {
                $this->dispatch('error', 'Invalid object ID');
                return;
            }
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            if (!$this->object) {
                $this->dispatch('error', 'Object not found');
                return;
            }
            $this->inputs = populateArrayFromModel($this->object);
            $this->inputs['status_code_text'] = $this->object->status_Code_text;
            $this->inputs['tax_doc_flag'] = $this->object->tax_doc_flag;
            $this->inputs['partner_name'] = $this->object->partner->code;
            $this->inputs['textareasend_to'] = $this->object->ship_to_addr;
            $this->inputs['textarea_npwp'] = $this->object->npwp_name . "\n" . $this->object->npwp_addr;
            $this->inputs['textareacustommer'] = $this->object->partner->name . "\n" . $this->object->partner->address . "\n" . $this->object->partner->city;
            // Hitung due_date berdasarkan tr_date dan payment_due_days
            $trDate = $this->object->tr_date ? \Carbon\Carbon::parse($this->object->tr_date) : null;
            $paymentDueDays = is_numeric($this->object->payment_due_days) ? (int)$this->object->payment_due_days : 0;
            $this->inputs['due_date'] = ($trDate && $paymentDueDays > 0)
                ? $trDate->copy()->addDays($paymentDueDays)->format('Y-m-d')
                : ($trDate ? $trDate->format('Y-m-d') : null);
            $this->onPartnerChanged();
            $this->loadDetails();

            // Set sales_type dan load materials
            if (!empty($this->inputs['sales_type'])) {
                $this->onSalesTypeChanged();
            }
        } else {
            $this->isPanelEnabled = "true";
            $this->inputs['tax_doc_flag'] = true;
            $this->inputs['tax_flag'] = 'I';
        }
        if (!empty($this->inputs['tax_flag'])) {
            $this->onSOTaxChange();
        }
        $this->dispatch('updateTaxPayerEnabled', !empty($this->inputs['tax_doc_flag']));
    }

    /*
     * Reset state untuk header dan detail.
     */
    public function onReset()
    {
        $this->reset('inputs', 'input_details');
        $this->object = new OrderHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tr_date']   = date('Y-m-d');
        $this->inputs['due_date']  = date('Y-m-d');
        $this->inputs['tr_type']   = $this->trType;
        $this->inputs['curr_code'] = "IDR";
        $this->inputs['curr_id'] = app(ConfigService::class)->getConstIdByStr1('BASE_CURRENCY', $this->inputs['curr_code']);
        $this->inputs['curr_rate'] = 1.00;
        $this->inputs['wh_code']   = 18;
        $this->inputs['partner_id']= 0;
    }

    /**
     * Cek apakah item tertentu masih bisa diedit (belum ada delivery)
     */
    public function isItemEditable($key)
    {
        if (!isset($this->input_details[$key])) return true;
        $detail = $this->input_details[$key];
        // Jika sudah ada delivery, field tidak bisa diedit
        return empty($detail['has_delivery']) || !$detail['has_delivery'];
    }

    /**
     * Cek apakah tombol tambah item bisa digunakan (tidak ada item yang sudah delivered)
     */
    public function canAddNewItem()
    {
        // Jika ada satu saja item yang sudah delivered, tidak bisa tambah
        foreach ($this->input_details as $detail) {
            if (!empty($detail['has_delivery'])) {
                return false;
            }
        }
        return true;
    }

    /*
     * TAMBAH ITEM (detail) pada sales order.
     */
    public function addItem()
    {
        if ($this->isDeliv || !$this->canAddNewItem()) {
            $this->dispatch('warning', 'Tidak bisa menambah item karena sudah ada pengiriman (delivery) pada salah satu item.');
            return;
        }
        try {
            $this->input_details[] = [
                'matl_id'  => null,
                'qty'      => null,
            ];
            $this->dispatch('success', __('generic.string.add_item'));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.add_item', ['message' => $e->getMessage()]));
        }
    }

    /*
     * Update item ketika material dipilih.
     */
    public function onMaterialChanged($key, $matl_id)
    {
        if ($matl_id) {
            $material = Material::find($matl_id);
            if ($material) {
                $matlUom = MatlUom::where('matl_id', $matl_id)->first();
                if ($matlUom) {
                    $this->input_details[$key]['matl_id']    = $material->id;
                    $this->input_details[$key]['price']       = $matlUom->selling_price;
                    $this->input_details[$key]['matl_uom']    = $material->uom;
                    $this->input_details[$key]['matl_descr']  = $material->name;
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

    /*
     * Hitung ulang jumlah per item dan total order.
     */
    public function updateItemAmount($key)
    {
        if (!empty($this->input_details[$key]['qty']) && !empty($this->input_details[$key]['price'])) {
            // Calculate basic amount with discount
            $qty = $this->stringToNumeric($this->input_details[$key]['qty']);
            $price = $this->stringToNumeric($this->input_details[$key]['price']);
            $discountPercent = $this->stringToNumeric($this->input_details[$key]['disc_pct'] ?? 0);

            $amountGross = $qty * $price;
            $discountAmount = $amountGross * ($discountPercent / 100);
            $this->input_details[$key]['amt'] = $amountGross - $discountAmount;
            $this->input_details[$key]['disc_amt'] = $discountAmount;

            // Calculate tax amounts
            $taxFlag = $this->inputs['tax_flag'] ?? 'N';
            $taxValue = $this->inputs['tax_pct'] ?? 0;
            $taxPctDecimal = $taxValue / 100;
            $amount = $this->input_details[$key]['amt'];

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

            $this->input_details[$key]['amt_tax'] = $this->input_details[$key]['dpp'] + $this->input_details[$key]['ppn'];
        } else {
            $this->input_details[$key]['amt'] = 0;
            $this->input_details[$key]['disc_amt'] = 0;
            $this->input_details[$key]['dpp'] = 0;
            $this->input_details[$key]['ppn'] = 0;
            $this->input_details[$key]['amt_tax'] = 0;
        }

        $this->input_details[$key]['amt_idr'] = rupiah($this->input_details[$key]['amt']);
        $this->recalculateTotals();
    }

    public function recalculateTotals()
    {
        $this->calculateTotalAmount();
        $this->calculateTotalDiscount();
        $this->calculateTotalDPP();
        $this->calculateTotalTax();
        $this->dispatch('updateAmount', [
            'total_amount'    => $this->total_amount,
            'total_discount'  => $this->total_discount,
            'total_tax'       => $this->total_tax,
            'total_dpp'       => $this->total_dpp,
        ]);
    }

    private function calculateTotalAmount()
    {
        $this->total_amount = 0;
        foreach ($this->input_details as $detail) {
            $this->total_amount += $detail['amt'] ?? 0;
        }
    }

    private function calculateTotalDiscount()
    {
        $this->total_discount = 0;
        foreach ($this->input_details as $detail) {
            $this->total_discount += $detail['disc_amt'] ?? 0;
        }
    }

    private function calculateTotalDPP()
    {
        $this->total_dpp = 0;
        foreach ($this->input_details as $detail) {
            $this->total_dpp += $detail['dpp'] ?? 0;
        }
    }

    private function calculateTotalTax()
    {
        $this->total_tax = 0;
        foreach ($this->input_details as $detail) {
            $this->total_tax += $detail['ppn'] ?? 0;
        }
    }

    /**
     * Update totals when changes are made
     */
    public function updateAmount($data)
    {
        $this->total_amount = (float)$data['total_amount'];
        $this->total_discount = (float)$data['total_discount'];
        $this->total_dpp = (float)$data['total_dpp'];
        $this->total_tax = (float)$data['total_tax'];

        $this->total_amount = 0;
        $this->total_discount = 0;
        $this->total_dpp = 0;
        $this->total_tax = 0;
        foreach ($this->input_details as $detail) {
            $this->total_amount += $detail['amt'] ?? 0;
            $this->total_discount += $detail['disc_amt'] ?? 0;
            $this->total_dpp += $detail['dpp'] ?? 0;
            $this->total_tax += $detail['ppn'] ?? 0;
        }
        // Format as Rupiah
        $this->total_amount = rupiah($this->total_amount);
        $this->total_discount = rupiah($this->total_discount);
        $this->total_dpp = rupiah($this->total_dpp);
        $this->total_tax = rupiah($this->total_tax);
    }

    public function deleteItem($index)
    {
        if ($this->isDeliv || (isset($this->input_details[$index]['has_delivery']) && $this->input_details[$index]['has_delivery'])) {
            $this->dispatch('warning', 'Tidak bisa menghapus item yang sudah ada pengiriman (delivery).');
            return;
        }
        try {
            if (!isset($this->input_details[$index])) {
                throw new Exception(__('generic.error.delete_item', ['message' => 'Item tidak ditemukan.']));
            }
            unset($this->input_details[$index]);
            $this->input_details = array_values($this->input_details);
            $this->dispatch('success', __('generic.string.delete_item'));
            $this->recalculateTotals();
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    /*
     * Load detail item (OrderDtl) jika header sudah tersimpan.
     */
    protected function loadDetails()
    {
        $this->isDeliv = false;
        if (!empty($this->object)) {
            $objectDetails = OrderDtl::GetByOrderHdr($this->object->id, $this->object->tr_type)
                ->orderBy('tr_seq')
                ->get();

            foreach ($objectDetails as $key => $detail) {
                $arr = populateArrayFromModel($detail);
                $arr['has_delivery'] = $detail->has_delivery ?? false;
                $arr['is_editable'] = $detail->is_editable ?? true;
                $this->input_details[$key] = $arr;
                $this->updateItemAmount($key);
                if ($arr['has_delivery']) {
                    $this->isDeliv = true;
                }
            }
        }
    }

    /*
     * Save semua data: bila hanya header yang terisi maka hanya OrderHdr yang disimpan,
     * dan bila terdapat item (detail) maka OrderDtl juga akan tersimpan.
     */
    public function onValidateAndSave()
    {
        if (!$this->orderService) {
            $this->orderService = app(OrderService::class);
        }

        $this->validate();

        // Jika sudah ada delivery, hanya boleh update header
        if ($this->isDeliv) {
            $this->dispatch('warning', 'Ada pengiriman (delivery) pada salah satu item. Hanya header yang bisa diupdate.');
            $headerData = $this->prepareHeaderData();
            $this->saveOrder($headerData, []);
            return $this->redirectToEdit();
        }

        // Jika belum ada delivery, proses normal
        if ($this->actionValue === 'Edit' && $this->object->isOrderCompleted()) {
            $this->dispatch('warning', 'Nota ini tidak bisa di-edit karena status sudah Completed');
            return;
        }

        // Prepare data
        $headerData = $this->prepareHeaderData();
        $detailData = $this->prepareDetailData();

        // Calculate totals from detail data
        $totals = $this->calculateTotalsFromDetails($detailData);
        $headerData['total_amt'] = $totals['total_amt'];
        $headerData['total_amt_tax'] = $totals['total_amt_tax'];

        $this->processNormalOrder($headerData, $detailData);
    }


    private function prepareHeaderData()
    {
        $headerData = $this->inputs;
        // Set default values
        $defaults = [
            'tr_type' => $this->trType,
            'tr_date' => date('Y-m-d'),
            'tax_flag' => 'N'
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
            $detail['price'] = $this->stringToNumeric($detail['price']);
            $detail['qty'] = $this->stringToNumeric($detail['qty']);
            $detail['disc_pct'] = $this->stringToNumeric($detail['disc_pct']);
            $detail['price_uom'] = $detail['matl_uom'] ?? 'PCS';
            $detail['tr_type'] = $this->trType;
            $detail['tr_seq'] = $i + 1;
            $detail['qty_uom'] = 'PCS';
            $detail['qty_base'] = 1;
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
            'total_amt' => $totalAmt,
            'total_amt_tax' => $totalAmtTax
        ];
    }


    private function processNormalOrder($headerData, $detailData)
    {
        try {
            DB::beginTransaction();

            // Save order
            $this->saveOrder($headerData, $detailData);

            DB::commit();

            $this->dispatch('success', 'Sales Order berhasil ' .
                ($this->actionValue === 'Create' ? 'disimpan' : 'diperbarui') . '.');

            return $this->redirectToEdit();
        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Gagal menyimpan Sales Order: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Save order data
     */
    private function saveOrder($headerData, $detailData)
    {
        try {
            if ($this->actionValue === 'Create') {
                $order = $this->orderService->addOrder($headerData, $detailData);
                if (!$order) {
                    throw new Exception('Gagal membuat Sales Order.');
                }
                $this->object = $order;
            } else {
                $result = $this->orderService->updOrder($this->object->id, $headerData, $detailData);
                if (!$result) {
                    throw new Exception('Gagal mengubah Sales Order.');
                }
            }
        } catch (Exception $e) {
            throw $e; // biar bisa rollback di caller
        }
    }

    private function redirectToEdit()
    {
        $objectId = $this->actionValue === 'Create' ? $this->object->id : $this->object->id;

        return redirect()->route(
            $this->appCode . '.Transaction.SalesOrder.Detail',
            [
                'action'   => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($objectId),
            ]
        );
    }

    /**
     * Normalize discount percentage (handle string format with comma)
     */
    private function normalizeDiscountPercent($discountPercent)
    {
        if (is_string($discountPercent)) {
            $discountPercent = str_replace(',', '.', $discountPercent);
        }
        return (float)$discountPercent;
    }



    /*
     * Hapus Sales Order (header dan berkaitan dengan detail-nya)
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

    public function deleteTransaction()
    {
        try {
            if (!$this->object || is_null($this->object->id) || !OrderHdr::where('id', $this->object->id)->exists()) {
                throw new Exception(__('Data header tidak ditemukan'));
            }

            $this->orderService->deleteOrder($this->object->id);
            $this->dispatch('success', __('Data berhasil terhapus'));

            return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete', ['message' => $e->getMessage()]));
        }
    }

    /*
     * Cetak invoice dan surat jalan.
     */
    public function printInvoice()
    {
        try {
            $this->notaCount++;
            $this->versionNumber = "{$this->notaCount}.{$this->suratJalanCount}";
            return redirect()->route('TrdTire1.Transaction.SalesOrder.PrintPdf', [
                'action'   => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);
        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    public function printDelivery()
    {
        try {
            $this->suratJalanCount++;
            $this->versionNumber = "{$this->notaCount}.{$this->suratJalanCount}";
            return redirect()->route('TrdTire1.Transaction.SalesDelivery.PrintPdf', [
                'action'   => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($this->object->id)
            ]);
        } catch (Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }
    }

    /*
     * Metode tambahan untuk pencarian dan pemilihan partner (supplier/customer)
     */
    public function openPartnerDialogBox()
    {
        $this->partnerSearchText = '';
        $this->suppliers = [];
        $this->selectedPartners = [];
        $this->dispatch('openPartnerDialogBox');
    }

    public function searchPartners()
    {
        if (!empty($this->partnerSearchText)) {
            $searchTerm = strtoupper($this->partnerSearchText);
            $this->suppliers = Partner::where('grp', Partner::CUSTOMER)
                ->where(function ($query) use ($searchTerm) {
                    $query->whereRaw("UPPER(code) LIKE ?", ["%{$searchTerm}%"])
                          ->orWhereRaw("UPPER(name) LIKE ?", ["%{$searchTerm}%"]);
                })
                ->get();
        } else {
            $this->dispatch('error', "Mohon isi kode atau nama supplier");
        }
    }

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
            $this->inputs['partner_code'] = $partner->code;
            $this->inputs['partner_name'] = $partner->code;
            $this->inputs['textareacustommer'] = $partner->name . "\n" . $partner->address . "\n" . $partner->city;
            if (!empty($this->inputs['tax_doc_flag'])) {
                if ($partner->PartnerDetail && !empty($partner->PartnerDetail->wp_details)) {
                    $wpDetails = $partner->PartnerDetail->wp_details;
                    if (is_string($wpDetails)) {
                        $wpDetails = json_decode($wpDetails, true);
                    }
                    if (is_array($wpDetails) && !empty($wpDetails)) {
                        $this->npwpOptions = array_map(function ($item) {
                            return [
                                'label' => $item['npwp'],
                                'value' => $item['npwp'],
                            ];
                        }, $wpDetails);
                        $firstNpwpOption = $this->npwpOptions[0] ?? null;
                        if ($firstNpwpOption) {
                            $this->inputs['npwp_code'] = $firstNpwpOption['value'];
                            $this->onTaxPayerChanged();
                        }
                    }
                }
            } else {
                $this->inputs['npwp_code'] = null;
                $this->inputs['textarea_npwp'] = null;
            }
            if ($partner->PartnerDetail && !empty($partner->PartnerDetail->shipping_address)) {
                $shipDetail = $partner->PartnerDetail->shipping_address;
                if (is_string($shipDetail)) {
                    $shipDetail = json_decode($shipDetail, true);
                }
                if (is_array($shipDetail) && !empty($shipDetail)) {
                    $this->shipOptions = array_map(function ($item) {
                        return [
                            'label' => $item['name'],
                            'value' => $item['name'],
                        ];
                    }, $shipDetail);
                    $firstShipOption = $this->shipOptions[0] ?? null;
                    if ($firstShipOption) {
                        $this->inputs['ship_to_name'] = $firstShipOption['value'];
                        $this->onShipToChanged();
                    }
                }
            }
            $this->dispatch('success', "Custommer berhasil dipilih.");
            $this->dispatch('closePartnerDialogBox');
        }
    }

    public function onTaxPayerChanged()
    {
        $partner = Partner::find($this->inputs['partner_id']);
        if ($partner && $partner->PartnerDetail && !empty($partner->PartnerDetail->wp_details)) {
            $wpDetails = $partner->PartnerDetail->wp_details;
            if (is_string($wpDetails)) {
                $wpDetails = json_decode($wpDetails, true);
            }
            if (is_array($wpDetails)) {
                foreach ($wpDetails as $detail) {
                    if ($detail['npwp'] == $this->inputs['npwp_code']) {
                        $this->inputs['textarea_npwp'] = $detail['wp_name'] . "\n" . $detail['wp_location'];
                        $this->inputs['npwp_code'] = $detail['npwp'];
                        $this->inputs['npwp_name'] = $detail['wp_name'];
                        $this->inputs['npwp_addr'] = $detail['wp_location'];
                        break;
                    }
                }
            }
        }
    }

    public function onShipToChanged()
    {
        $partner = Partner::find($this->inputs['partner_id']);
        if ($partner && $partner->PartnerDetail && !empty($partner->PartnerDetail->shipping_address)) {
            $shipDetails = $partner->PartnerDetail->shipping_address;
            if (is_string($shipDetails)) {
                $shipDetails = json_decode($shipDetails, true);
            }
            if (is_array($shipDetails)) {
                foreach ($shipDetails as $detail) {
                    if ($detail['name'] == $this->inputs['ship_to_name']) {
                        $this->inputs['textareasend_to'] = $detail['address'];
                        $this->inputs['ship_to_name'] = $detail['name'];
                        $this->inputs['ship_to_addr'] = $detail['address'];
                        break;
                    }
                }
            }
        }
    }

    /*
     * Render view berdasarkan path yang telah ditentukan.
     */
    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
