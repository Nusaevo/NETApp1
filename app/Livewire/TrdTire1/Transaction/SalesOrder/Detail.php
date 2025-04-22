<?php

namespace App\Livewire\TrdTire1\Transaction\SalesOrder;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\{OrderHdr, OrderDtl, DelivHdr, DelivDtl, BillingHdr, BillingDtl};
use App\Models\TrdTire1\Master\{Partner, Material, MatlUom};
use App\Models\SysConfig1\ConfigConst;
use App\Enums\Status;
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
    public $payer = "true"; // Default true, akan dipengaruhi oleh tax_doc_flag

    // Detail (item) properties
    public $input_details = [];
    public $materials;
    protected $masterService;

    // Gabungan validasi untuk header dan detail (item)
    public $rules  = [
        'inputs.tr_code'       => 'required',
        'inputs.partner_id'    => 'required',
        'input_details.*.qty'  => 'required',
        'input_details.*.matl_id'  => 'required',
    ];

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'delete'        => 'delete',
        'updateAmount'  => 'updateAmount',
    ];

    /*
     * Method untuk generate kode transaksi.
     */
    public function getTransactionCode()
    {
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

        $this->masterService = new MasterService();
        $this->partners = $this->masterService->getCustomers();
        $this->SOTax = $this->masterService->getSOTaxData();
        $this->SOSend = $this->masterService->getSOSendData();
        $this->paymentTerms = $this->masterService->getPaymentTerm();
        $this->warehouses = $this->masterService->getWarehouse();
        $this->materials = $this->masterService->getMaterials();

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
            $this->onPartnerChanged();
            $this->loadDetails();
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

    /*
     * TAMBAH ITEM (detail) pada sales order.
     */
    public function addItem()
    {
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

    /*
     * Save semua data: bila hanya header yang terisi maka hanya OrderHdr yang disimpan,
     * dan bila terdapat item (detail) maka OrderDtl juga akan tersimpan.
     */
    public function onValidateAndSave()
    {
        // Validasi input header dan detail
        $this->validate([
            'inputs.tr_code'         => 'required',
            'inputs.partner_id'      => 'required',
            'input_details.*.qty'    => 'required',
            'input_details.*.matl_id'=> 'required',
        ]);

        // Jika dalam mode edit dan status order sudah Completed, batalkan penyimpanan
        if ($this->actionValue === 'Edit' && $this->object->isOrderCompleted()) {
            $this->dispatch('warning', 'Nota ini tidak bisa di-edit karena status sudah Completed');
            return;
        }

        try {
            // Mulai transaksi database
            DB::beginTransaction();

            // Update partner_code bila partner telah dipilih
            if (!isNullOrEmptyNumber($this->inputs['partner_id'])) {
                $partner = Partner::find($this->inputs['partner_id']);
                if ($partner) {
                    $this->inputs['partner_code'] = $partner->code;
                }
            }

            // Set data payment term bila ada
            if (!empty($this->inputs['payment_term_id'])) {
                $paymentTerm = ConfigConst::find($this->inputs['payment_term_id']);
                if ($paymentTerm) {
                    $this->inputs['payment_term'] = $paymentTerm->str1;
                    $this->inputs['payment_due_days'] = $paymentTerm->num1;
                }
            }

            // Jika NPWP dinonaktifkan, kosongkan npwp_code
            if ($this->payer === "false") {
                $this->inputs['npwp_code'] = '';
            }

            // Simpan data header menggunakan method yang sudah ada
            // Pastikan method saveOrderHeader() tersedia pada model atau komponen Anda
            $this->object->saveOrderHeader($this->appCode, $this->trType, $this->inputs, 'SALESORDER_LASTID');

            // Simpan detail order jika ada input item
            if (!empty($this->input_details)) {
                // Ambil data detail yang sudah tersimpan (jika ada) untuk perbandingan
                $existingDetails = OrderDtl::where('trhdr_id', $this->object->id)
                    ->where('tr_type', $this->object->tr_type)
                    ->get()
                    ->keyBy('tr_seq')
                    ->toArray();

                // Hapus detail yang sudah tidak ada di form
                $itemsToDelete = array_diff_key($existingDetails, $this->input_details);
                foreach ($itemsToDelete as $tr_seq => $detail) {
                    $orderDtl = OrderDtl::find($detail['id']);
                    if ($orderDtl) {
                        $orderDtl->forceDelete();
                    }
                }

                // Simpan atau perbarui setiap detail item
                foreach ($this->input_details as $key => $detail) {
                    $tr_seq = $key + 1;
                    $orderDtl = OrderDtl::firstOrNew([
                        'tr_code' => $this->object->tr_code,
                        'tr_seq'  => $tr_seq,
                    ]);

                    $detail['tr_code']   = $this->object->tr_code;
                    $detail['trhdr_id']  = $this->object->id;
                    $detail['tr_type']   = $this->object->tr_type;

                    // Pastikan disc_pct disimpan dalam format desimal
                    $detail['disc_pct'] = isset($detail['disc_pct']) ? round((float)$detail['disc_pct'], 2) : 0;

                    // Ambil data material untuk mengisi matl_code dan satuan (uom)
                    $material = Material::find($detail['matl_id']);
                    if ($material) {
                        $detail['matl_code'] = $material->code;
                        $detail['matl_uom']  = $material->uom;
                        $detail['price_uom'] = $material->uom;
                    }

                    $orderDtl->fill($detail);
                    $orderDtl->save();
                }
            }

            // Hitung ulang total_amt dari semua detail yang tersimpan
            $totalAmt = 0;
            foreach ($this->input_details as $detail) {
                $qty = $detail['qty'] ?? 0;
                $price = $detail['price'] ?? 0;
                $disc_pct = isset($detail['disc_pct']) ? $detail['disc_pct'] : 0;
                $amount = $qty * $price;
                $discount = $amount * ($disc_pct / 100);
                $totalAmt += ($amount - $discount);
            }

            // Update field total_amt dan total_amt_tax pada OrderHdr
            $this->object->total_amt = round($totalAmt, 2);
            // Logika perhitungan pajak:
            // jika tax_flag 'I' (inclusive) maka total_amt_tax sama dengan total_amt,
            // jika tax_flag 'E' (exclusive) maka total_amt_tax = total_amt * (1 + (tax_pct/100))
            // dan jika lainnya, total_amt_tax = total_amt.
            if ($this->object->tax_flag === 'I') {
                $this->object->total_amt_tax = $this->object->total_amt;
            } elseif ($this->object->tax_flag === 'E') {
                $taxPct = ($this->object->tax_pct ?? 0) / 100;
                $this->object->total_amt_tax = round($this->object->total_amt * (1 + $taxPct), 2);
            } else {
                $this->object->total_amt_tax = $this->object->total_amt;
            }

            $this->object->save();

            // Commit transaksi jika semua proses berhasil
            DB::commit();

            // Redirect jika mode create agar user melihat hasil penyimpanan (mode edit)
            if ($this->actionValue === 'Create') {
                return redirect()->route($this->appCode . '.Transaction.SalesOrder.Detail', [
                    'action'   => encryptWithSessionKey('Edit'),
                    'objectId' => encryptWithSessionKey($this->object->id)
                ]);
            }

            $this->dispatch('success', 'Sales Order berhasil disimpan.');
        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Gagal menyimpan: ' . $e->getMessage());
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
                OrderDtl::where('trhdr_id', $this->object->id)
                    ->where('tr_type', $this->object->tr_type)
                    ->forceDelete();
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
