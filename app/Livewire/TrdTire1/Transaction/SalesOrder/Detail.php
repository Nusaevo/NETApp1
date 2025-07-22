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
    // public $suppliers = [];
    // public $partnerSearchText = '';
    // public $selectedPartners = [];
    public $warehouses;
    // public $partners;
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
    public $matl_action = 'Create';
    public $matl_objectId = null;
    public $currency = [];
    public $returnIds = [];
    public $currencyRate = 0;
    public $npwpOptions = [];
    public $shipOptions = [];
    public $isPanelEnabled = "false";
    public $payer = "true";
    public $isDeliv = false; // True jika ada delivery pada salah satu detail
    public $object;
    public $object_detail;

    public $ddPartner = [
        'placeHolder' => "Ketik untuk cari customer ...",
        'optionLabel' => "code,name,address,city",
        'query' => "SELECT id,code,name,address,city
                    FROM partners
                    WHERE deleted_at IS NULL AND grp = 'C'",
    ];

    // Detail (item) properties
    public $input_details = [];
    public $materialQuery = "";
    public $materialCategory = null;
    public $materials;
    protected $masterService;
    protected $orderService;

    // Gabungan validasi untuk header dan detail (item)
    public $rules  = [
        'inputs.tr_code'       => 'required',
        'inputs.partner_id'    => 'required',
        'inputs.tax_code'      => 'required',
        // 'input_details.*.qty' => 'required',
        'input_details.*.matl_id' => 'required',
    ];

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'delete'        => 'delete',
        'updateAmount'  => 'updateAmount',
        // 'salesTypeOnChanged' => 'salesTypeOnChanged', // tambahkan listener baru
        'refreshData'   => 'refreshData',
    ];

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
        // $this->partners = $this->masterService->getCustomers();
        $this->SOTax = $this->masterService->getSOTaxData();
        $this->SOSend = $this->masterService->getSOSendData();
        $this->paymentTerms = $this->masterService->getPaymentTerm();
        // $this->warehouses = $this->masterService->getWarehouse();

        // --- Perbaikan urutan: panggil salesTypeOnChanged() sebelum loadDetails() ---
        if ($this->isEditOrView()) {
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            $this->inputs = $this->object->toArray();
            $this->inputs['status_code_text'] = $this->object->status_Code_text;
            $this->inputs['tax_doc_flag'] = $this->object->tax_doc_flag;
            $this->inputs['partner_name'] = $this->object->partner->code;
            // Pastikan print_remarks adalah string/float, bukan array/object
            $printRemarks = $this->object->getDisplayFormat();
            if (is_array($printRemarks)) {
                $this->inputs['print_remarks'] = isset($printRemarks['nota']) ? $printRemarks['nota'] : '0.0';
            } else {
                $this->inputs['print_remarks'] = $printRemarks;
            }
            // Hitung due_date berdasarkan tr_date dan payment_due_days
            $trDate = $this->object->tr_date ? \Carbon\Carbon::parse($this->object->tr_date) : null;
            $paymentDueDays = is_numeric($this->object->payment_due_days) ? (int)$this->object->payment_due_days : 0;
            $this->inputs['due_date'] = ($trDate && $paymentDueDays > 0)
                ? $trDate->copy()->addDays($paymentDueDays)->format('Y-m-d')
                : ($trDate ? $trDate->format('Y-m-d') : null);
            $this->onPartnerChanged();
            $this->salesTypeOnChanged();
            $this->loadDetails();
        } else {
            $this->isPanelEnabled = "true";
            $this->inputs['tax_doc_flag'] = true;
            $this->inputs['tax_code'] = 'I';
            $this->inputs['print_remarks'] = '0.0';
        }
        if (!empty($this->inputs['tax_code'])) {
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
        $this->inputs['print_remarks'] = ['nota' => 0, 'surat_jalan' => 0];
    }

    public function onValidateAndSave()
    {
        if (!$this->orderService) {
            $this->orderService = app(OrderService::class);
        }

        $this->validate();

        // Jika sudah ada delivery, hanya boleh update header
        if ($this->isDeliv) {
            // Prepare data header saja
            $headerData = $this->prepareHeaderData();
            $detailData = []; // Kosongkan detail agar tidak diubah
            try {
                $result = $this->orderService->updOrder($this->object->id, $headerData, []);
                if (!$result) {
                    throw new Exception('Gagal mengubah Sales Order.');
                }
                $this->dispatch('warning', 'Ada pengiriman (delivery) pada salah satu item. Hanya header yang bisa diupdate.');
                // return $this->redirectToEdit();
            } catch (Exception $e) {
                $this->dispatch('error', $e->getMessage());
                throw new Exception('Gagal memperbarui Sales Order: ' . $e->getMessage());
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
        $totals = $this->calcTotalFromDetails($detailData);
        $headerData['amt'] = $totals['amt'];
        $headerData['amt_beforetax'] = $totals['amt_beforetax'];
        $headerData['amt_tax'] = $totals['amt_tax'];
        // dd($headerData, $detailData);
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
            if ($this->actionValue === 'Create') {
                $detail['status_code'] = Status::OPEN;
            }
        }
        unset($detail);
        return $detailData;
    }

    /*
     * Method untuk generate kode transaksi.
     */
    public function trCodeOnClick()
    {
        // Tambahkan pengecekan sales_type
        if (empty($this->inputs['sales_type'])) {
            $this->dispatch('error', 'Silakan pilih Tipe Kendaraan terlebih dahulu sebelum generate Nomor.');
            return;
        }
        $salesType = $this->inputs['sales_type'];
        $taxDocFlag = !empty($this->inputs['tax_doc_flag']);
        $this->inputs['tr_code'] = app(MasterService::class)->getNewTrCode($this->trType,$salesType,$taxDocFlag);
    }

    /*
     * Mengatur perhitungan pajak (DPP dan PPN) berdasarkan tax_code dan tax_value.
     */
    public function onSOTaxChange()
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
    /*
     * Update flag NPWP (payer) ketika tax_doc_flag berubah.
     */
    public function onTaxDocFlagChanged()
    {
        $this->payer = !empty($this->inputs['tax_doc_flag']) ? "true" : "false";
        // Clear tr_code when tax_doc_flag changes
        $this->inputs['tr_code'] = null;
    }

    public function onPaymentTermChanged()
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
     * Handle sales_type change and filter materials accordingly
     */
    public function salesTypeOnChanged()
    {
        $salesType = $this->inputs['sales_type'] ?? null;
        $this->input_details = [];

        // Clear tr_code when sales type changes
        $this->inputs['tr_code'] = null;

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

        // Jika dalam mode edit, jangan reset input_details
        if (!$this->isEditOrView()) {
            $this->input_details = [];
        }
    }

    /*
     * Proses inisialisasi data pada render (pre-render).
     * Bila mode edit/view, load data header (OrderHdr) dan detail (OrderDtl).
     */

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
        if (empty($this->inputs['sales_type'])) {
            $this->dispatch('error', 'Silakan pilih nota MOTOR atau MOBIL terlebih dahulu.');
            return;
        }
        try {
            $this->input_details[] = populateArrayFromModel(new OrderDtl());
            // $this->input_details[] = [
            //     'matl_id' => null,
            //     'qty' => null,
            //     'price' => null,
            //     'disc_pct' => null,
            //     'amt' => null,
            //     'disc_amt' => null,
            // ];
            // $this->dispatch('success', __('generic.string.add_item'));
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
                $matlUom = MatlUom::where('matl_id', $matl_id)
                    ->where('matl_uom', $material->uom)->first();
                if ($matlUom) {
                    $this->input_details[$key]['price'] = $matlUom->selling_price;
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

    /*
     * Hitung ulang jumlah per item dan total order.
     */
    public function calcItemAmount($key)
    {
        if (!empty($this->input_details[$key]['qty']) && !empty($this->input_details[$key]['price'])) {
            // Calculate basic amount with discount
            $qty = $this->input_details[$key]['qty'];
            $price = $this->input_details[$key]['price'];
            $discount = $this->input_details[$key]['disc_pct'] / 100;
            $taxValue = $this->inputs['tax_pct'] / 100;
            $priceAfterDisc = $price * (1 - $discount);

            $amtDiscount = $qty * $price * $discount;
            $this->input_details[$key]['disc_amt'] = $amtDiscount;

            // Calculate tax amounts
            // dd($this->inputs['tax_code'], $taxValue);
            $this->input_details[$key]['amt'] = 0;
            $this->input_details[$key]['amt_beforetax'] = 0;
            $this->input_details[$key]['amt_tax'] = 0;
            if ($this->inputs['tax_code'] === 'I') {
                $this->input_details[$key]['amt'] = $priceAfterDisc * $qty;
                $this->input_details[$key]['amt_beforetax'] = round($priceAfterDisc * $qty / (1 + $taxValue), 0);
                $this->input_details[$key]['amt_tax'] = $this->input_details[$key]['amt'] - $this->input_details[$key]['amt_beforetax'];
            } else if ($this->inputs['tax_code'] === 'E') {
                $this->input_details[$key]['amt'] = round($priceAfterDisc * $qty * (1 + $taxValue), 0);
                $this->input_details[$key]['amt_beforetax'] = $priceAfterDisc * $qty;
                $this->input_details[$key]['amt_tax'] = $priceAfterDisc * $qty * $taxValue;
            } else if ($this->inputs['tax_code'] === 'N') {
                $this->input_details[$key]['amt'] = $priceAfterDisc * $qty;
                $this->input_details[$key]['amt_beforetax'] = $priceAfterDisc * $qty;
                $this->input_details[$key]['amt_tax'] = 0;
            }
            $this->total_amount = 0;
            $this->total_discount = 0;
            $this->total_dpp = 0;
            $this->total_tax = 0;
            // dd($this->input_details, $this->input_details[$key]['disc_amt']);
            foreach ($this->input_details as $detail) {
                $this->total_amount += $detail['amt'];
                $this->total_discount += $detail['disc_amt'];
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
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete_item', ['message' => $e->getMessage()]));
        }
    }

    /*
     * Load detail item (OrderDtl) jika header sudah tersimpan.
     */
    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id, $this->object->tr_type)
                ->orderBy('tr_seq')
                ->get();

            $this->input_details = $this->object_detail->toArray();
            foreach ($this->object_detail as $key => $detail) {
                $this->calcItemAmount($key);
            }
            $this->checkDeliveryStatus();
        }
    }

    private function calcTotalFromDetails($detailData)
    {
        $amt = 0;
        $amtBeforeTax = 0;
        $amtTax = 0;

        foreach ($detailData as $detail) {
            $amt += $detail['amt'] ?? 0;
            $amtBeforeTax += $detail['amt_beforetax'] ?? 0;
            $amtTax += $detail['amt_tax'] ?? 0;
        }

        return [
            'amt' => $amt,
            'amt_beforetax' => $amtBeforeTax,
            'amt_tax' => $amtTax
        ];
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

    // /**
    //  * Normalize discount percentage (handle string format with comma)
    //  */
    // private function normalizeDiscountPercent($discountPercent)
    // {
    //     if (is_string($discountPercent)) {
    //         $discountPercent = str_replace(',', '.', $discountPercent);
    //     }
    //     return (float)$discountPercent;
    // }

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

            // Pastikan orderService sudah diinisialisasi
            if (!$this->orderService) {
                $this->orderService = app(OrderService::class);
            }

            $this->orderService->delOrder($this->object->id);
            $this->dispatch('success', __('Data berhasil terhapus'));

            return redirect()->route(str_replace('.Detail', '', $this->baseRoute));
        } catch (Exception $e) {
            $this->dispatch('error', __('generic.error.delete', ['message' => $e->getMessage()]));
        }
    }

    /*
     * Cetak invoice dan surat jalan.
     */


    // /*
    //  * Metode tambahan untuk pencarian dan pemilihan partner (supplier/customer)
    //  */
    // public function openPartnerDialogBox()
    // {
    //     $this->partnerSearchText = '';
    //     $this->suppliers = [];
    //     $this->selectedPartners = [];
    //     $this->dispatch('openPartnerDialogBox');
    // }

    // public function searchPartners()
    // {
    //     if (!empty($this->partnerSearchText)) {
    //         $searchTerm = strtoupper($this->partnerSearchText);
    //         $this->suppliers = Partner::where('grp', Partner::CUSTOMER)
    //             ->where(function ($query) use ($searchTerm) {
    //                 $query->whereRaw("UPPER(code) LIKE ?", ["%{$searchTerm}%"])
    //                     ->orWhereRaw("UPPER(name) LIKE ?", ["%{$searchTerm}%"]);
    //             })
    //             ->get();
    //     } else {
    //         $this->dispatch('error', "Mohon isi kode atau nama supplier");
    //     }
    // }


    public function onPartnerChanged()
    {
        $partner = Partner::find($this->inputs['partner_id']);
        if ($partner) {
            // Set basic partner info
            $this->inputs['partner_code'] = $partner->code;
            $this->inputs['partner_name'] = $partner->code;
            $this->inputs['partner_real_name'] = $partner->name;
            $this->inputs['partner_address'] = $partner->address;
            $this->inputs['partner_city'] = $partner->city;

            // Reset shipping info
            $this->inputs['ship_to_name'] = null;
            $this->inputs['ship_to_address'] = null;
            $this->shipOptions = [];

            // Reset NPWP info
            $this->inputs['npwp_code'] = null;
            $this->inputs['npwp_name'] = null;
            $this->inputs['npwp_addr'] = null;
            $this->npwpOptions = [];

            // Handle Shipping Options
            if ($partner->PartnerDetail && !empty($partner->PartnerDetail->shipping_address)) {
                $shipDetail = $partner->PartnerDetail->shipping_address;
                if (is_string($shipDetail)) {
                    $shipDetail = json_decode($shipDetail, true);
                }
                if (is_array($shipDetail) && !empty($shipDetail)) {
                    $this->shipOptions = array_map(function ($item) {
                        return [
                            'label' => $item['name'] . ' - ' . $item['address'] . (isset($item['city']) ? ' - ' . $item['city'] : ''),
                            'value' => $item['name'],
                            'address' => $item['address'],
                        ];
                    }, $shipDetail);

                    // Auto-select the first option
                    foreach ($this->shipOptions as $opt) {
                        $this->inputs['ship_to_name'] = $opt['value'];
                        $this->inputs['ship_to_address'] = $opt['address'];
                        break;
                    }
                }
            }

            // Handle NPWP Options if tax_doc_flag is set
            if (!empty($this->inputs['tax_doc_flag'])) {
                if ($partner->PartnerDetail && !empty($partner->PartnerDetail->wp_details)) {
                    $wpDetails = $partner->PartnerDetail->wp_details;
                    if (is_string($wpDetails)) {
                        $wpDetails = json_decode($wpDetails, true);
                    }
                    if (is_array($wpDetails) && !empty($wpDetails)) {
                        $this->npwpOptions = array_map(function ($item) {
                            return [
                                'label' => $item['npwp'] . ' - ' . $item['wp_name'] . ' - ' . $item['wp_location'],
                                'value' => $item['npwp'],
                                'name' => $item['wp_name'],
                                'address' => $item['wp_location'],
                            ];
                        }, $wpDetails);

                        // Auto-select the first option
                        foreach ($this->npwpOptions as $opt) {
                            $this->inputs['npwp_code'] = $opt['value'];
                            $this->inputs['npwp_name'] = $opt['name'];
                            $this->inputs['npwp_addr'] = $opt['address'];
                            break;
                        }
                    }
                }
            }
        }

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
                    $this->isDeliv = true; // Ada delivery, field tidak aktif
                    $this->input_details[$key]['has_delivery'] = true;
                } else {
                    $this->input_details[$key]['has_delivery'] = false;
                }
            } else {
                $this->input_details[$key]['has_delivery'] = false;
            }
        }
    }

    /**
     * Refresh data setelah print counter diupdate
     */
    public function refreshData()
    {
        if ($this->object && $this->object->id) {
            $this->object->refresh();
            $this->inputs['print_remarks'] = $this->object->getDisplayFormat();
            $this->dispatch('printCounterUpdated', $this->inputs['print_remarks']);
        }
    }

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

    /*
     * Render view berdasarkan path yang telah ditentukan.
     */
    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
