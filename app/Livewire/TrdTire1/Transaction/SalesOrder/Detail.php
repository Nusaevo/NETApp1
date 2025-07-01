<?php

namespace App\Livewire\TrdTire1\Transaction\SalesOrder;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl, DelivHdr, DelivDtl, BillingHdr, BillingDtl};
use App\Models\TrdTire1\Master\{Partner, Material, MatlUom};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
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
        'input_details.*.qty'  => 'required_if:input_details.*.matl_id,!=,null',
        'input_details.*.matl_id'  => 'required_if:input_details.*.qty,!=,null',
        'input_details.*.disc_pct' => 'nullable|numeric|min:0|max:100',
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
            'input_details.*.disc_pct' => 'Diskon (%)',
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
        $this->inputs['curr_id']   = ConfigConst::CURRENCY_DOLLAR_ID;
        $this->inputs['curr_code'] = "USD";
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
            // Pastikan qty dan price adalah numerik
            $qty = (float)$this->input_details[$key]['qty'];
            $price = (float)$this->input_details[$key]['price'];

            // Hitung amount dasar
            $amount = $qty * $price;

            // Tangani diskon
            $discountPercent = 0;
            if (isset($this->input_details[$key]['disc_pct'])) {
                // Konversi string ke float dengan benar
                $discPct = $this->input_details[$key]['disc_pct'];
                if (is_string($discPct)) {
                    // Hapus semua karakter non-numerik kecuali titik dan koma
                    $discPct = preg_replace('/[^0-9.,]/', '', $discPct);
                    // Ganti koma dengan titik jika ada
                    $discPct = str_replace(',', '.', $discPct);
                }
                $discountPercent = (float)$discPct;
            }

            // Hitung diskon dan amount akhir
            $discountAmount = $amount * ($discountPercent / 100);
            $this->input_details[$key]['amt'] = $amount - $discountAmount;
        } else {
            $this->input_details[$key]['amt'] = 0;
        }

        $this->input_details[$key]['amt_idr'] = rupiah($this->input_details[$key]['amt']);
        $this->recalculateTotals();
    }

    public function recalculateTotals()
    {
        $this->calculateTotalAmount();
        $this->calculateTotalDiscount();
        $this->dispatch('updateAmount', [
            'total_amount'    => $this->total_amount,
            'total_discount'  => $this->total_discount,
            'total_tax'       => $this->total_tax,
            'total_dpp'       => $this->total_dpp,
        ]);
    }

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
                if (isset($arr['disc_pct']) && is_numeric($arr['disc_pct'])) {
                    $arr['disc_pct'] = $arr['disc_pct'] / 10;
                }
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

        // Pastikan input_details ada dan array
        if (!isset($this->input_details) || !is_array($this->input_details)) {
            $this->input_details = [];
        }

        // Konversi diskon dari format koma ke titik sebelum validasi
        foreach ($this->input_details as &$detail) {
            if (isset($detail['disc_pct']) && is_string($detail['disc_pct'])) {
                // Hapus semua spasi dan karakter non-numerik kecuali koma dan titik
                $discPct = preg_replace('/[^0-9,.]/', '', $detail['disc_pct']);
                // Jika ada koma, ganti dengan titik
                $discPct = str_replace(',', '.', $discPct);
                // Pastikan hanya ada satu titik desimal
                $parts = explode('.', $discPct);
                if (count($parts) > 2) {
                    $discPct = $parts[0] . '.' . implode('', array_slice($parts, 1));
                }
                $detail['disc_pct'] = (float)$discPct;
            }
            // Sesuaikan dengan tipe kolom numeric(15,5)
            $detail['disc_pct'] = round((float)$detail['disc_pct'], 5);
        }
        unset($detail);

        // apply validation including detail rules
        $validated = $this->validate($this->rules);

        try {
            if ($this->actionValue === 'Edit' && $this->object->isOrderCompleted()) {
                $this->dispatch('warning', 'Nota ini tidak bisa di-edit karena status sudah Completed');
                return;
            }

            // Prepare data
            $headerData = $validated['inputs'];
            $detailData = $validated['input_details'] ?? [];

            // Lengkapi data detail sebelum simpan
            foreach ($detailData as $i => &$detail) {
                // Pastikan field material terisi
                if (!isset($detail['matl_code']) || !isset($detail['matl_descr']) || !isset($detail['matl_uom'])) {
                    $material = Material::find($detail['matl_id']);
                    if ($material) {
                        $detail['matl_code'] = $material->code;
                        $detail['matl_descr'] = $material->name;
                        $detail['matl_uom'] = $material->uom;
                    }
                }

                // Ambil selling_price dari matl_uoms
                $matlUom = MatlUom::where('matl_id', $detail['matl_id'])
                    ->where('matl_uom', $detail['matl_uom'])
                    ->first();

                if ($matlUom) {
                    $detail['price'] = $matlUom->selling_price;
                    $detail['price_uom'] = $detail['matl_uom'];
                } else {
                    $detail['price'] = 0;
                    $detail['price_uom'] = $detail['matl_uom'] ?? 'PCS';
                }

                // Pastikan disc_pct ada dan dalam format yang benar
                if (isset($detail['disc_pct'])) {
                    // Konversi dari string ke float dengan benar
                    if (is_string($detail['disc_pct'])) {
                        // Hapus semua spasi dan karakter non-numerik kecuali koma dan titik
                        $discPct = preg_replace('/[^0-9,.]/', '', $detail['disc_pct']);
                        // Jika ada koma, ganti dengan titik
                        $discPct = str_replace(',', '.', $discPct);
                        // Pastikan hanya ada satu titik desimal
                        $parts = explode('.', $discPct);
                        if (count($parts) > 2) {
                            $discPct = $parts[0] . '.' . implode('', array_slice($parts, 1));
                        }
                        $detail['disc_pct'] = (float)$discPct;
                    }
                    // Sesuaikan dengan tipe kolom numeric(15,5)
                    $detail['disc_pct'] = round((float)$detail['disc_pct'], 5);
                } else {
                    $detail['disc_pct'] = 0.00000;
                }

                // Hitung amount dan tax
                $qty = $detail['qty'] ?? 0;
                $price = $detail['price'] ?? 0;
                $discPct = $detail['disc_pct'] / 100;
                $detail['amt'] = $qty * $price * (1 - $discPct);

                // Hitung DPP dan PPN berdasarkan tax_flag
                $taxFlag = $headerData['tax_flag'] ?? 'N';
                $taxPct = ($headerData['tax_pct'] ?? 0) / 100;

                if ($taxFlag === 'I') {
                    // Include tax
                    $detail['dpp'] = $detail['amt'] / (1 + $taxPct);
                    $detail['ppn'] = $detail['amt'] - $detail['dpp'];
                } elseif ($taxFlag === 'E') {
                    // Exclude tax
                    $detail['dpp'] = $detail['amt'];
                    $detail['ppn'] = $detail['amt'] * $taxPct;
                } else {
                    // No tax
                    $detail['dpp'] = $detail['amt'];
                    $detail['ppn'] = 0;
                }

                // Set amt_tax
                $detail['amt_tax'] = $detail['dpp'] + $detail['ppn'];

                // Set field transaksi
                $detail['tr_type'] = $this->trType;
                $detail['tr_seq'] = $i + 1;
            }
            unset($detail); // break reference

            // Pastikan field penting terisi
            $headerData['tr_type']           = $headerData['tr_type']           ?? $this->trType;
            $headerData['tr_date']           = $headerData['tr_date']           ?? date('Y-m-d');
            $headerData['partner_code']      = $headerData['partner_code']      ?? ($this->inputs['partner_code'] ?? null);
            $headerData['partner_id']        = $headerData['partner_id']        ?? ($this->inputs['partner_id'] ?? null);
            $headerData['payment_term_id']   = $headerData['payment_term_id']   ?? ($this->inputs['payment_term_id'] ?? null);
            $headerData['payment_term']      = $headerData['payment_term']      ?? ($this->inputs['payment_term'] ?? null);
            $headerData['ship_to_name']      = $headerData['ship_to_name']      ?? ($this->inputs['ship_to_name'] ?? null);
            $headerData['sales_type']        = $headerData['sales_type']        ?? ($this->inputs['sales_type'] ?? null);
            $headerData['tax_doc_flag']      = $headerData['tax_doc_flag']      ?? ($this->inputs['tax_doc_flag'] ?? null);
            $headerData['tax_flag']          = $headerData['tax_flag']          ?? ($this->inputs['tax_flag'] ?? 'N');
            $headerData['tax_pct']           = $headerData['tax_pct']           ?? ($this->inputs['tax_pct'] ?? null);
            $headerData['npwp_code']         = $headerData['npwp_code']         ?? ($this->inputs['npwp_code'] ?? null);
            $headerData['npwp_name']         = $headerData['npwp_name']         ?? ($this->inputs['npwp_name'] ?? null);
            $headerData['npwp_addr']         = $headerData['npwp_addr']         ?? ($this->inputs['npwp_addr'] ?? null);
            $headerData['ship_to_addr']      = $headerData['ship_to_addr']      ?? ($this->inputs['ship_to_addr'] ?? null);
            $headerData['total_amt']         = $headerData['total_amt']         ?? $this->total_amount;
            $headerData['total_amt_tax']     = $headerData['total_amt_tax']     ?? $this->total_tax;
            $headerData['payment_due_days']  = $headerData['payment_due_days']  ?? ($this->inputs['payment_due_days'] ?? null);
            $headerData['reff_code']  = $headerData['reff_code']  ?? ($this->inputs['reff_code'] ?? null);

            // Jika payment_term_id ada, ambil payment_term dan payment_due_days dari master
            if (!empty($headerData['payment_term_id'])) {
                $paymentTerm = ConfigConst::find($headerData['payment_term_id']);
                if ($paymentTerm) {
                    $headerData['payment_term'] = $paymentTerm->str1;
                    $headerData['payment_due_days'] = $paymentTerm->num1;
                }
            }

            if ($this->actionValue === 'Create') {
                $order = $this->orderService->addOrder($headerData, $detailData);
                $this->dispatch('success', 'Sales Order berhasil disimpan.');
                return redirect()->route(
                    $this->appCode . '.Transaction.SalesOrder.Detail',
                    [
                        'action'   => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey($order->id),
                    ]
                );
            } else if ($this->actionValue === 'Edit') {
                // Jika sudah ada delivery, update header saja, detailData dikosongkan
                $result = $this->isDeliv
                    ? $this->orderService->updOrder($this->object->id, $headerData, [])
                    : $this->orderService->updOrder($this->object->id, $headerData, $detailData);
                $this->dispatch('success', 'Sales Order berhasil diupdate.');
                return redirect()->route(
                    $this->appCode . '.Transaction.SalesOrder.Detail',
                    [
                        'action'   => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey($this->object->id),
                    ]
                );
            }
        } catch (Exception $e) {
            throw new Exception('Gagal menyimpan: ' . $e->getMessage());
        }
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
