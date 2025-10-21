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
use Illuminate\Support\Facades\{DB, Log, Auth};
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
    public $canUpdateAfterPrint = true; // izin untuk save/print/delete setelah pernah dicetak
    public $hasBeenPrinted = false; // apakah pernah dicetak (revision > 0)
    public $canPrintNotaButton = true; // enable/disable tombol Cetak Nota Jual
    public $canPrintSuratJalanButton = true; // enable/disable tombol Cetak Surat Jalan
    public $canSaveButtonEnabled = true; // enable/disable tombol Simpan

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

    // Property untuk dialog box NPWP
    public $npwpDetails = [
        'npwp' => '',
        'wp_name' => '',
        'wp_location' => '',
    ];

    // Gabungan validasi untuk header dan detail (item)
    public $rules  = [
        'inputs.tr_code'       => 'required',
        'inputs.partner_id'    => 'required|min:1',
        'inputs.tax_code'      => 'required',
        'inputs.tr_date'       => 'required',
        'inputs.payment_term'  => 'required',
        'inputs.payment_term_id' => 'required',
        'inputs.payment_due_days' => 'required',
        'input_details.*.matl_id' => 'required',
        'input_details.*.qty' => 'required',
        'input_details.*.price' => 'required',

    ];

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'delete'        => 'delete',
        'updateAmount'  => 'updateAmount',
        'refreshData'   => 'refreshData',
        'onTaxPayerChanged' => 'onTaxPayerChanged',
        'salesTypeOnChanged' => 'salesTypeOnChanged', // tambahkan listener baru
        'onTrDateChanged' => 'onTrDateChanged', // listener untuk perubahan tr_date
    ];
    public function openNpwpDialogBox()
    {
        $this->dispatch('openNpwpDialogBox');
    }

    public function closeNpwpDialogBox()
    {
        $this->dispatch('closeNpwpDialogBox');

        // Refresh NPWP options ketika dialog ditutup
        $this->refreshNpwpOptions();
    }

    public function saveNpwp()
    {
        // Validasi input
        $this->validate([
            'npwpDetails.npwp' => 'required',
            'npwpDetails.wp_name' => 'required',
            'npwpDetails.wp_location' => 'required',
        ], [
            'npwpDetails.npwp.required' => 'NPWP/NIK harus diisi',
            'npwpDetails.wp_name.required' => 'Nama WP harus diisi',
            'npwpDetails.wp_location.required' => 'Alamat WP harus diisi',
        ]);

        try {
            // Ambil partner yang sedang dipilih
            $partner = Partner::find($this->inputs['partner_id']);
            if (!$partner) {
                throw new Exception('Partner tidak ditemukan');
            }

            // Ambil atau buat PartnerDetail
            $partnerDetail = $partner->PartnerDetail;
            if (!$partnerDetail) {
                $partnerDetail = new \App\Models\TrdTire1\Master\PartnerDetail();
                $partnerDetail->partner_id = $partner->id;
                $partnerDetail->partner_grp = $partner->grp;
                $partnerDetail->partner_code = $partner->code;
            }

            // Ambil data wp_details yang sudah ada
            $wpDetails = $partnerDetail->wp_details ?? [];
            if (is_string($wpDetails)) {
                $wpDetails = json_decode($wpDetails, true);
            }
            if (!is_array($wpDetails)) {
                $wpDetails = [];
            }

            // Tambahkan data NPWP baru
            $wpDetails[] = [
                'npwp' => $this->npwpDetails['npwp'],
                'wp_name' => $this->npwpDetails['wp_name'],
                'wp_location' => $this->npwpDetails['wp_location'],
            ];
            // Simpan ke database
            $partnerDetail->wp_details = $wpDetails;
            $result = $partnerDetail->save();


            // Refresh data dari database untuk memastikan data ter-update
            $partnerDetail->refresh();
            $wpDetails = $partnerDetail->wp_details;
            if (is_string($wpDetails)) {
                $wpDetails = json_decode($wpDetails, true);
            }

            // Debug: cek data yang tersimpan
            // Log::info('NPWP Data after save:', [
            //     'partner_id' => $partner->id,
            //     'wp_details' => $wpDetails,
            //     'npwpOptions_count' => count($this->npwpOptions ?? [])
            // ]);

            // Update npwpOptions dengan data yang sudah di-refresh
            if (is_array($wpDetails) && !empty($wpDetails)) {
                $this->npwpOptions = array_map(function ($item) {
                    return [
                        'label' => $item['npwp'] . ' - ' . $item['wp_name'] . ' - ' . $item['wp_location'],
                        'value' => $item['npwp'],
                        'name' => $item['wp_name'],
                        'address' => $item['wp_location'],
                    ];
                }, $wpDetails);
            } else {
                $this->npwpOptions = [];
            }

            // Debug: cek npwpOptions setelah update
            // Log::info('NPWP Options after update:', [
            //     'npwpOptions' => $this->npwpOptions
            // ]);

            // Set NPWP yang baru ditambahkan sebagai yang aktif
            $this->inputs['npwp_code'] = $this->npwpDetails['npwp'];
            $this->inputs['npwp_name'] = $this->npwpDetails['wp_name'];
            $this->inputs['npwp_addr'] = $this->npwpDetails['wp_location'];

            // Reset npwpDetails
            $this->npwpDetails['npwp'] = '';
            $this->npwpDetails['wp_name'] = '';
            $this->npwpDetails['wp_location'] = '';

            // Tutup dialog box
            $this->closeNpwpDialogBox();

            // Refresh NPWP options dari database
            $this->refreshNpwpOptions();

            // Debug: cek npwpOptions setelah refresh
            // Log::info('NPWP Options after refreshNpwpOptions:', [
            //     'npwpOptions' => $this->npwpOptions,
            //     'count' => count($this->npwpOptions ?? [])
            // ]);

            // Dispatch event untuk refresh Select2
            $this->dispatch('refreshSelect2', 'inputs_npwp_code');

            $this->dispatch('success', 'Data NPWP berhasil disimpan');
        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal menyimpan data NPWP: ' . $e->getMessage());
        }
    }

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs.tax'          => $this->trans('tax'),
            'inputs.tr_code'      => $this->trans('tr_code'),
            'inputs.partner_id'   => $this->trans('partner_id'),
            'inputs.send_to_name' => $this->trans('send_to_name'),
            'inputs.payment_term' => $this->trans('Termin Pembayaran'),
        ];

        $this->orderService = app(OrderService::class);
        $this->masterService = new MasterService();
        // $this->partners = $this->masterService->getCustomers();
        $this->SOTax = $this->masterService->getSOTaxData();
        $this->SOSend = $this->masterService->getSOSendData();
        $this->paymentTerms = $this->masterService->getPaymentTerm();
        // $this->warehouses = $this->masterService->getWarehouse();

        if ($this->isEditOrView()) {
            $this->object = OrderHdr::withTrashed()->find($this->objectIdValue);
            if ($this->object) {
                $this->inputs = $this->object->toArray();
                // dd($this->inputs);
                // $this->inputs['tax_doc_flag'] = $this->object->tax_doc_flag;
                $this->onTaxDocFlagChanged();
                $this->loadShippingOptions();
                $this->inputs['tr_code'] = $this->object->tr_code;
                $this->inputs['partner_name'] = $this->object->partner ? $this->object->partner->code : '';

                // Pastikan print_remarks adalah string/float, bukan array/object
                $trDate = $this->object->tr_date ? \Carbon\Carbon::parse($this->object->tr_date) : null;
                $paymentDueDays = is_numeric($this->object->payment_due_days) ? (int)$this->object->payment_due_days : 0;
                $this->inputs['due_date'] = ($trDate && $paymentDueDays > 0)
                    ? $trDate->copy()->addDays($paymentDueDays)->format('Y-m-d')
                    : ($trDate ? $trDate->format('Y-m-d') : null);

                $printRemarks = $this->object->getDisplayFormat();
                if (is_array($printRemarks)) {
                    $this->inputs['print_remarks'] = isset($printRemarks['nota']) ? $printRemarks['nota'] : '0.0';
                } else {
                    $this->inputs['print_remarks'] = $printRemarks;
                }

                // dd($this->materialQuery);
                $this->salesTypeOnChanged();
                $this->loadDetails();
                $this->updateAfterPrintPermission();
                $this->updateButtonStatesByCounter();
            } else {
                // Jika object tidak ditemukan, buat instance baru dan tampilkan error
                $this->object = new OrderHdr();
                $this->dispatch('error', 'Data tidak ditemukan');
            }
        } else {
            $this->object = new OrderHdr(); // Inisialisasi object untuk mode Create
            $this->isPanelEnabled = "true";
            $this->inputs['tax_doc_flag'] = true;
            $this->inputs['tax_code'] = 'I';
            $this->inputs['print_remarks'] = '0.0';

            // Set default payment term to COD
            $this->setDefaultPaymentTerm();
        }
        if (!empty($this->inputs['tax_code'])) {
            $this->onSOTaxChange();
        }
        $this->dispatch('updateTaxPayerEnabled', !empty($this->inputs['tax_doc_flag']));
    }

    private function getRevisionCount(): int
    {
        $rev = 0;
        if (isset($this->inputs['print_remarks'])) {
            if (is_array($this->inputs['print_remarks'])) {
                $rev = (int)($this->inputs['print_remarks']['nota'] ?? 0);
            } else {
                $rev = (int)$this->inputs['print_remarks'];
            }
        }
        return $rev;
    }

    private function updateAfterPrintPermission(): void
    {
        //Log::info('updateAfterPrintPermission called');

        // Cek apakah ada cetakan (nota atau surat jalan)
        $notaCount = 0;
        $suratCount = 0;
        if ($this->object && method_exists($this->object, 'getPrintCounterArray')) {
            $c = $this->object->getPrintCounterArray();
            $notaCount = (int)($c['nota'] ?? 0);
            $suratCount = (int)($c['surat_jalan'] ?? 0);
        }
        $this->hasBeenPrinted = ($notaCount > 0 || $suratCount > 0);

        if (!$this->hasBeenPrinted) {
            $this->canUpdateAfterPrint = true;
            return;
        }

        $userId = Auth::id();
        $num1 = ConfigConst::where('const_group', 'SEC_LEVEL')
            ->where('str2', 'UPDATE_AFTER_PRINT')
            ->where('user_id', $userId)
            ->value('num1');
        $this->canUpdateAfterPrint = ((int)($num1 ?? 0)) === 1;

        // Debug log untuk cek izin
        // Log::info('Permission Debug:', [
        //     'userId' => $userId,
        //     'num1' => $num1,
        //     'canUpdateAfterPrint' => $this->canUpdateAfterPrint,
        // ]);
    }

    private function updateButtonStatesByCounter(): void
    {
        // Ambil counter terbaru dari object jika tersedia
        $notaCount = 0;
        $suratCount = 0;
        if ($this->object) {
            if (method_exists($this->object, 'getPrintCounterArray')) {
                $c = $this->object->getPrintCounterArray();
                $notaCount = (int)($c['nota'] ?? 0);
                $suratCount = (int)($c['surat_jalan'] ?? 0);
            } else {
                // fallback dari inputs.print_remarks string "x.y"
                $display = $this->object->getDisplayFormat();
                if (is_string($display) && strpos($display, '.') !== false) {
                    [$n, $s] = explode('.', $display);
                    $notaCount = (int)$n;
                    $suratCount = (int)$s;
                }
            }
        } else if (!empty($this->inputs['print_remarks']) && is_string($this->inputs['print_remarks'])) {
            $display = $this->inputs['print_remarks'];
            if (strpos($display, '.') !== false) {
                [$n, $s] = explode('.', $display);
                $notaCount = (int)$n;
                $suratCount = (int)$s;
            }
        }

        $this->canPrintNotaButton = ($notaCount === 0);
        $this->canPrintSuratJalanButton = ($suratCount === 0);
        $this->canSaveButtonEnabled = ($notaCount === 0 && $suratCount === 0);

        // Debug log
        // Log::info('Button States Debug:', [
        //     'notaCount' => $notaCount,
        //     'suratCount' => $suratCount,
        //     'canUpdateAfterPrint' => $this->canUpdateAfterPrint,
        //     'canPrintNotaButton' => $this->canPrintNotaButton,
        //     'canPrintSuratJalanButton' => $this->canPrintSuratJalanButton,
        //     'canSaveButtonEnabled' => $this->canSaveButtonEnabled,
        // ]);

        // Jika SEC_LEVEL mengizinkan update setelah print, semua tombol diaktifkan
        if ($this->canUpdateAfterPrint) {
            $this->canPrintNotaButton = true;
            $this->canPrintSuratJalanButton = true;
            $this->canSaveButtonEnabled = true;
        }
    }

    public function onReset()
    {
        $this->reset('inputs', 'input_details', 'npwpDetails');
        $this->object = new OrderHdr();
        $this->inputs = populateArrayFromModel($this->object);
        $this->inputs['tax_process_date'] = null;
        $this->inputs['print_date'] = null;
        $this->inputs['tr_date']   = date('Y-m-d');
        $this->inputs['due_date']  = date('Y-m-d');
        $this->inputs['tr_type']   = $this->trType;
        $this->inputs['curr_code'] = "IDR";
        $this->inputs['curr_id'] = app(ConfigService::class)->getConstIdByStr1('BASE_CURRENCY', $this->inputs['curr_code']);
        $this->inputs['curr_rate'] = 1.00;
        $this->inputs['print_remarks'] = ['nota' => 0, 'surat_jalan' => 0];

        // Set default payment term to COD
        $this->setDefaultPaymentTerm();

        // Reset npwpDetails
        $this->npwpDetails = [
            'npwp' => '',
            'wp_name' => '',
            'wp_location' => '',
        ];
    }

    public function onValidateAndSave()
    {
        if (!$this->orderService) {
            $this->orderService = app(OrderService::class);
        }

        $this->validate();

        // Validasi duplikasi tr_code
        $this->validateTrCodeDuplicate();

        // Validasi duplikasi matl_id dalam detail
        $this->validateMatlIdDuplicate();

        // Guard: batasi update jika sudah pernah dicetak dan user tidak berizin
        $this->updateAfterPrintPermission();
        if ($this->actionValue !== 'Create' && $this->hasBeenPrinted && !$this->canUpdateAfterPrint) {
            $this->dispatch('error', 'Anda tidak memiliki izin untuk mengubah data setelah dicetak.');
            return;
        }

        if ($this->actionValue === 'Create') {
            $this->inputs['status_code'] = Status::OPEN;
        }

        // Jika sudah ada delivery, hanya boleh update header
        if ($this->isDeliv && $this->actionValue !== 'Create') {
            // Prepare data header saja
            $headerData = $this->prepareHeaderData();
            $detailData = []; // Kosongkan detail agar tidak diubah
            try {
                $result = $this->orderService->saveOrder($headerData, []);
                if (!$result) {
                    throw new Exception('Gagal mengubah Sales Order.');
                }

                // Update object dengan hasil terbaru
                if ($result['header']) {
                    $this->object = $result['header'];
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
        $headerData['amt_adjustdtl'] = $totals['amt_adjustdtl'];
         // dd($headerData, $detailData);
        if ($this->actionValue === 'Create') {
            $result = $this->orderService->saveOrder($headerData, $detailData);
            if (!$result) {
                throw new Exception('Gagal membuat Nota penjualan');
            }
            $this->object = $result['header']; // Ambil header object dari hasil array

            // Set status_code langsung pada object jika baru dibuat
            if ($this->object && $this->actionValue === 'Create') {
                $this->object->status_code = Status::OPEN;
                $this->object->save();
            }
        } else {
            $result = $this->orderService->saveOrder($headerData, $detailData);
            if (!$result) {
                throw new Exception('Gagal mengubah Nota penjualan');
            }

            // Update object dengan hasil terbaru
            if ($result['header']) {
                $this->object = $result['header'];
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

        // Set default values for NPWP fields to prevent null constraint violation
        $headerData['npwp_code'] = $headerData['npwp_code'] ?? '';
        $headerData['npwp_name'] = $headerData['npwp_name'] ?? '';
        $headerData['npwp_addr'] = $headerData['npwp_addr'] ?? '';

        // Set default values for shipping fields to prevent null constraint violation
        $headerData['ship_to_name'] = $headerData['ship_to_name'] ?? '';
        $headerData['ship_to_addr'] = $headerData['ship_to_addr'] ?? '';


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

    public function trCodeOnClick()
    {
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
        $taxDocFlag = !empty($this->inputs['tax_doc_flag']);
        $trDate = $this->inputs['tr_date'];
        $this->inputs['tr_code'] = app(MasterService::class)->getNewTrCode($this->trType,$salesType,$taxDocFlag,$trDate);
    }

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

    public function onTaxDocFlagChanged()
    {
        $this->payer = !empty($this->inputs['tax_doc_flag']) ? "true" : "false";

        // Hanya kosongkan tr_code jika dalam mode create, bukan edit
        if ($this->actionValue === 'Create') {
            $this->inputs['tr_code'] = '';
        }

        // Jika tax_doc_flag aktif dan ada partner_id, muat data NPWP
        if (!empty($this->inputs['tax_doc_flag']) && !empty($this->inputs['partner_id'])) {
            $partner = Partner::find($this->inputs['partner_id']);
            if ($partner && $partner->PartnerDetail && !empty($partner->PartnerDetail->wp_details)) {
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

                    // Simpan data NPWP yang sudah ada sebelum di-reset
                    $existingNpwpCode = $this->inputs['npwp_code'] ?? '';
                    $existingNpwpName = $this->inputs['npwp_name'] ?? '';
                    $existingNpwpAddr = $this->inputs['npwp_addr'] ?? '';

                    // Jika ada data NPWP yang sudah tersimpan, gunakan data tersebut
                    if (!empty($existingNpwpCode)) {
                        $this->inputs['npwp_code'] = $existingNpwpCode;
                        $this->inputs['npwp_name'] = $existingNpwpName;
                        $this->inputs['npwp_addr'] = $existingNpwpAddr;
                    } else {
                        // Jika tidak ada data tersimpan, gunakan data pertama
                        $first = reset($this->npwpOptions);
                        $this->inputs['npwp_code'] = $first['value'] ?? '';
                        $this->inputs['npwp_name'] = $first['name'] ?? '';
                        $this->inputs['npwp_addr'] = $first['address'] ?? '';
                    }
                }
            }
        } else {
            // Reset NPWP jika tax_doc_flag tidak aktif
            $this->inputs['npwp_code'] = '';
            $this->inputs['npwp_name'] = '';
            $this->inputs['npwp_addr'] = '';
        }

        // Dispatch event untuk refresh Select2
        $this->dispatch('refreshSelect2', 'inputs_npwp_code');
    }

    public function loadShippingOptions()
    {
        // Muat shipping options jika ada partner_id
        if (!empty($this->inputs['partner_id'])) {
            $partner = Partner::find($this->inputs['partner_id']);
            if ($partner && $partner->PartnerDetail && !empty($partner->PartnerDetail->shipping_address)) {
                $shipDetail = $partner->PartnerDetail->shipping_address;
                if (is_string($shipDetail)) {
                    $shipDetail = json_decode($shipDetail, true);
                }
                if (is_array($shipDetail) && !empty($shipDetail)) {
                    $this->shipOptions = array_map(function ($item) {
                        return [
                            'label' => $item['name'] . ' - ' . $item['address'],
                            'value' => $item['name'],
                            'address' => $item['address'],
                        ];
                    }, $shipDetail);
                }
            }
        }
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

    public function salesTypeOnChanged()
    {
        $salesType = $this->inputs['sales_type'] ?? null;
        $this->input_details = [];

        // Hanya kosongkan tr_code jika dalam mode create, bukan edit
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

        $this->materialQuery = "
            SELECT m.id, m.code, m.name, coalesce(b.qty_oh,0) qty_oh, coalesce(b.qty_fgi,0) qty_fgi
            FROM materials m
            LEFT OUTER JOIN (
                select matl_id, SUM(qty_oh)::int as qty_oh,SUM(qty_fgi)::int as qty_fgi
                from ivt_bals
                group by matl_id
                ) b on b.matl_id = m.id
            WHERE m.status_code = 'A'
            AND m.deleted_at IS NULL
            AND m.category IN ($categoryList)
        ";
    }

    public function isItemEditable($key)
    {
        if (!isset($this->input_details[$key])) return true;
        $detail = $this->input_details[$key];
        // Jika sudah ada delivery, field tidak bisa diedit
        return empty($detail['has_delivery']) || !$detail['has_delivery'];
    }

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

    /**
     * Get maximum SO item limit from ConfigConst
     */
    private function getMaxSoItemLimit()
    {
        try {
            $configConst = ConfigConst::where('const_group', 'APP_ENV')
                ->where('str1', 'MAX_SO_ITEM')
                ->first();

            if ($configConst && $configConst->num1) {
                return (int) $configConst->num1;
            }

            // Default limit jika tidak ada konfigurasi
            return 10;
        } catch (Exception $e) {
            Log::error('Error getting MAX_SO_ITEM from ConfigConst: ' . $e->getMessage());
            return 10; // Default limit
        }
    }

    public function addItem()
    {
        if (empty($this->inputs['sales_type'])) {
            $this->dispatch('error', 'Silakan pilih nota MOTOR atau MOBIL terlebih dahulu.');
            return;
        }

        // Cek batasan jumlah item
        $maxItems = $this->getMaxSoItemLimit();
        $currentItemCount = count($this->input_details);

        if ($currentItemCount >= $maxItems) {
            $this->dispatch('error', "Maksimal item yang dapat ditambahkan adalah {$maxItems} item. Saat ini sudah ada {$currentItemCount} item.");
            return;
        }

        try {
            $this->input_details[] = populateArrayFromModel(new OrderDtl());
            $key = count($this->input_details) - 1;
            $this->input_details[$key]['disc_pct'] = 0;
            $this->input_details[$key]['disc_amt'] = 0;
            $this->input_details[$key]['gt_process_date'] = null;
            // dd($this->input_details);
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

   public function onMaterialChanged($key, $matl_id)
    {
        if ($matl_id) {
            $material = Material::find($matl_id);
            if ($material) {
                // Cari data UOM untuk material ini
                $matlUom = MatlUom::where('matl_id', $matl_id)
                    ->where('matl_uom', $material->uom)->first();

                // Set data material terlebih dahulu
                $this->input_details[$key]['matl_id'] = $material->id;
                $this->input_details[$key]['matl_code'] = $material->code;
                $this->input_details[$key]['matl_uom'] = $material->uom;
                $this->input_details[$key]['matl_descr'] = $material->name;
                $this->input_details[$key]['disc_pct'] = 0;
                $this->input_details[$key]['disc_amt'] = 0;

                // Set harga berdasarkan data UOM jika ditemukan
                if ($matlUom) {
                    $this->input_details[$key]['price'] = $matlUom->selling_price;
                } else {
                    // Jika data UOM tidak ditemukan, set harga default atau 0
                    $this->input_details[$key]['price'] = 0;
                    // Tampilkan warning bahwa data UOM tidak ditemukan
                    $this->dispatch('warning', 'Data UOM untuk material ' . $material->code . ' tidak ditemukan. Harga diset ke 0.');
                }

                $this->calcItemAmount($key);
            } else {
                $this->dispatch('error', __('generic.error.material_not_found'));
            }
        }
    }

    public function calcItemAmount($key)
    {
        if (!empty($this->input_details[$key]['qty']) && !empty($this->input_details[$key]['price'])) {
            // Calculate basic amount with discount
            $qty = $this->input_details[$key]['qty'];
            $price = $this->input_details[$key]['price'];
            $discount = $this->input_details[$key]['disc_pct'] / 100;
            $taxValue = $this->inputs['tax_pct'] / 100;
            $priceAfterDisc = round($price * (1 - $discount), 0);
            $priceBeforeTax = round($priceAfterDisc / (1 + $taxValue), 0);
            // dd($this->inputs['tax_code'], $price, $priceAfterDisc, $priceBeforeTax, $taxValue);
            $this->input_details[$key]['disc_amt'] = round($qty * $price * $discount, 0);

            $this->input_details[$key]['amt'] = 0;
            $this->input_details[$key]['amt_beforetax'] = 0;
            $this->input_details[$key]['amt_tax'] = 0;
            if ($this->inputs['tax_code'] === 'I') {
                $this->input_details[$key]['price_beforetax'] = $priceBeforeTax;
                // Catatan: khusus untuk yang include PPN
                // DPP dihitung dari harga setelah disc dikurangi PPN dibulatkan ke rupiah * qty
                $this->input_details[$key]['amt_beforetax'] = round($priceBeforeTax * $qty, 0);
                // PPN dihitung dari DPP * PPN dibulatkan ke rupiah
                $this->input_details[$key]['amt_tax'] = round($this->input_details[$key]['amt_beforetax'] * $taxValue, 0);
            } else if ($this->inputs['tax_code'] === 'E') {
                $this->input_details[$key]['price_beforetax'] = $priceAfterDisc;
                $this->input_details[$key]['amt_beforetax'] = round($priceAfterDisc * $qty, 0);
                $this->input_details[$key]['amt_tax'] = round($priceAfterDisc * $qty * $taxValue, 0);
            } else if ($this->inputs['tax_code'] === 'N') {
                $this->input_details[$key]['price_beforetax'] = $priceAfterDisc;
                $this->input_details[$key]['amt_beforetax'] = round($priceAfterDisc * $qty, 0);
                $this->input_details[$key]['amt_tax'] = 0;
            }
            // amt selalu dihitung tanpa dipengaruhi tax_code: (price after discount) * qty
            $this->input_details[$key]['amt'] = round($priceAfterDisc * $qty, 0);
            $this->input_details[$key]['price_afterdisc'] = $priceAfterDisc;
            $this->input_details[$key]['amt_adjustdtl'] = round($this->input_details[$key]['amt'] - $this->input_details[$key]['amt_beforetax'] - $this->input_details[$key]['amt_tax'], 0);

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

    protected function loadDetails()
    {
        if (!empty($this->object)) {
            $this->object_detail = OrderDtl::GetByOrderHdr($this->object->id, $this->object->tr_type)
                ->orderBy('tr_seq')
                ->get();

            $this->input_details = $this->object_detail->toArray();
            foreach ($this->object_detail as $key => $detail) {
                $this->input_details[$key]['matl_id'] = $detail->matl_id;
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
        $amtAdjustDtl = 0;

        foreach ($detailData as $detail) {
            $amt += $detail['amt'] ?? 0;
            $amtBeforeTax += $detail['amt_beforetax'] ?? 0;
            $amtTax += $detail['amt_tax'] ?? 0;
            $amtAdjustDtl += $detail['amt_adjustdtl'] ?? 0;
        }

        return [
            'amt' => round($amt, 0),
            'amt_beforetax' => round($amtBeforeTax, 0),
            'amt_tax' => round($amtTax, 0),
            'amt_adjustdtl' => round($amtAdjustDtl, 0)
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
    // public function delete()
    // {
    //     try {
    //         // Guard: batasi delete jika sudah pernah dicetak dan user tidak berizin
    //         $this->updateAfterPrintPermission();
    //         if ($this->hasBeenPrinted && !$this->canUpdateAfterPrint) {
    //             $this->dispatch('error', 'Anda tidak memiliki izin untuk menghapus data setelah dicetak.');
    //             return;
    //         }
    //         // Jika mode Create, object belum disimpan ke database
    //         if ($this->actionValue === 'Create') {
    //             $this->dispatch('warning', 'Tidak ada data untuk dihapus pada mode Create');
    //             return;
    //         }

    //         if ($this->object->isOrderCompleted()) {
    //             $this->dispatch('warning', 'Nota ini tidak bisa dihapus karena status sudah Completed');
    //             return;
    //         }
    //         if (!$this->object->isOrderEnableToDelete()) {
    //             $this->dispatch('warning', 'Nota ini tidak bisa dihapus karena memiliki material yang sudah dijual.');
    //             return;
    //         }
    //         $this->object->status_code = Status::NONACTIVE;
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
            // Guard: batasi delete jika sudah pernah dicetak dan user tidak berizin
            $this->updateAfterPrintPermission();
            if ($this->hasBeenPrinted && !$this->canUpdateAfterPrint) {
                $this->dispatch('error', 'Anda tidak memiliki izin untuk menghapus data setelah dicetak.');
                return;
            }
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

            // Reset shipping info completely when partner changes
            $this->inputs['ship_to_name'] = '';
            $this->inputs['ship_to_addr'] = '';
            $this->shipOptions = [];

            // Reset NPWP info completely when partner changes
            $this->inputs['npwp_code'] = '';
            $this->inputs['npwp_name'] = '';
            $this->inputs['npwp_addr'] = '';
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
                            'label' => $item['name'] . ' - ' . $item['address'],
                            'value' => $item['name'],
                            'address' => $item['address'],
                        ];
                    }, $shipDetail);

                    // Set default shipping address to first option
                    $first = reset($this->shipOptions);
                    if ($first) {
                        $this->inputs['ship_to_name'] = $first['value'] ?? '';
                        $this->inputs['ship_to_addr'] = $first['address'] ?? '';
                    }
                }
            }

            // Handle NPWP Options - load wp_details from selected partner
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

                    // Set default NPWP to first option if tax_doc_flag is active
                    if (!empty($this->inputs['tax_doc_flag'])) {
                        $first = reset($this->npwpOptions);
                        $this->inputs['npwp_code'] = $first['value'] ?? '';
                        $this->inputs['npwp_name'] = $first['name'] ?? '';
                        $this->inputs['npwp_addr'] = $first['address'] ?? '';
                    }
                }
            } else {
                // Reset NPWP options jika partner tidak memiliki wp_details
                $this->npwpOptions = [];
                $this->inputs['npwp_code'] = '';
                $this->inputs['npwp_name'] = '';
                $this->inputs['npwp_addr'] = '';

                // Validasi: jika tax_doc_flag aktif tapi partner tidak memiliki NPWP
                if (!empty($this->inputs['tax_doc_flag'])) {
                    $this->dispatch('error', 'Partner ' . $partner->name . ' tidak memiliki NPWP. Silahkan tambahkan NPWP terlebih dahulu.');
                }
            }

            // Dispatch event untuk refresh Select2
            $this->dispatch('refreshSelect2', 'inputs_npwp_code');
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
                        $this->inputs['npwp_code'] = $detail['npwp'];
                        $this->inputs['npwp_name'] = $detail['wp_name'];
                        $this->inputs['npwp_addr'] = $detail['wp_location'];
                        break;
                    }
                }
            }
        }
    }

    public function onTrDateChanged()
    {
        // Reset tr_code jika tr_date diubah, karena tr_code bergantung pada bulan
        if ($this->actionValue === 'Create' && !empty($this->inputs['tr_code'])) {
            // Cek apakah bulan dari tr_date berbeda dengan bulan saat ini
            $currentMonth = date('n');
            $trDateMonth = null;

            if (!empty($this->inputs['tr_date'])) {
                $trDateMonth = \Carbon\Carbon::parse($this->inputs['tr_date'])->month;
            }

            // Reset tr_code jika bulan berbeda atau jika belum ada tr_date
            if ($trDateMonth !== $currentMonth) {
                $this->inputs['tr_code'] = '';
                $this->dispatch('info', 'Nomor transaksi di-reset karena tanggal berubah ke bulan yang berbeda.');
            }
        }

        // Update due_date berdasarkan payment_term yang dipilih
        if (!empty($this->inputs['payment_term_id'])) {
            $this->onPaymentTermChanged();
        }
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

    public function onNpwpSelectionChanged($npwpCode)
    {
        $partner = Partner::find($this->inputs['partner_id']);
        if ($partner && $partner->PartnerDetail && !empty($partner->PartnerDetail->wp_details)) {
            $wpDetails = $partner->PartnerDetail->wp_details;
            if (is_string($wpDetails)) {
                $wpDetails = json_decode($wpDetails, true);
            }
            if (is_array($wpDetails)) {
                foreach ($wpDetails as $detail) {
                    if ($detail['npwp'] == $npwpCode) {
                        $this->inputs['npwp_code'] = $detail['npwp'];
                        $this->inputs['npwp_name'] = $detail['wp_name'];
                        $this->inputs['npwp_addr'] = $detail['wp_location'];
                        break;
                    }
                }
            }
        }
    }

    public function onShipToChanged($shipToName)
    {
        // Cari alamat yang sesuai dengan nama yang dipilih
        foreach ($this->shipOptions as $option) {
            if ($option['value'] === $shipToName) {
                $this->inputs['ship_to_name'] = $option['value'];
                $this->inputs['ship_to_addr'] = $option['address'];
                break;
            }
        }
    }

    public function refreshNpwpOptions()
    {
        if (!empty($this->inputs['partner_id'])) {
            $partner = Partner::find($this->inputs['partner_id']);
            if ($partner && $partner->PartnerDetail && !empty($partner->PartnerDetail->wp_details)) {
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
                } else {
                    $this->npwpOptions = [];
                }
            } else {
                $this->npwpOptions = [];
            }
        }
    }

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

    public function refreshData()
    {
        if ($this->object && $this->object->id) {
            $this->object->refresh();
            $this->inputs['print_remarks'] = $this->object->getDisplayFormat();
            $this->dispatch('printCounterUpdated', $this->inputs['print_remarks']);
            // Update izin setelah nilai revision berubah
            $this->updateAfterPrintPermission();
            $this->updateButtonStatesByCounter();
        }
    }

    public function goToPrintNota()
    {
        $this->updateAfterPrintPermission();
        $this->updateButtonStatesByCounter();
        if (!$this->canPrintNotaButton && !$this->canUpdateAfterPrint) {
            $this->dispatch('error', 'Cetak Nota Jual dinonaktifkan sesuai aturan counter.');
            return;
        }
        // Cek counter khusus Nota: cetak ulang membutuhkan izin
        $notaCount = 0;
        if ($this->object && method_exists($this->object, 'getPrintCounterArray')) {
            $c = $this->object->getPrintCounterArray();
            $notaCount = (int)($c['nota'] ?? 0);
        }
        if ($notaCount > 0 && !$this->canUpdateAfterPrint) {
            $this->dispatch('error', 'Anda tidak memiliki izin untuk mencetak ulang.');
            return;
        }
        if ($this->object && $this->object->id) {
            return redirect()->route(
                'TrdTire1.Transaction.SalesOrder.PrintPdf',
                [
                    'action'   => encryptWithSessionKey('Edit'),
                    'objectId' => encryptWithSessionKey($this->object->id),
                ]
            );
        }
    }

    public function goToPrintSuratJalan()
    {
        $this->updateAfterPrintPermission();
        $this->updateButtonStatesByCounter();
        if (!$this->canPrintSuratJalanButton && !$this->canUpdateAfterPrint) {
            $this->dispatch('error', 'Cetak Surat Jalan dinonaktifkan sesuai aturan counter.');
            return;
        }
        // Cek counter khusus Surat Jalan: cetak ulang membutuhkan izin
        $suratCount = 0;
        if ($this->object && method_exists($this->object, 'getPrintCounterArray')) {
            $c = $this->object->getPrintCounterArray();
            $suratCount = (int)($c['surat_jalan'] ?? 0);
        }
        if ($suratCount > 0 && !$this->canUpdateAfterPrint) {
            $this->dispatch('error', 'Anda tidak memiliki izin untuk mencetak ulang surat jalan.');
            return;
        }
        if ($this->object && $this->object->id) {
            return redirect()->route(
                'TrdTire1.Transaction.SalesDelivery.PrintPdf',
                [
                    'action'   => encryptWithSessionKey('Edit'),
                    'objectId' => encryptWithSessionKey($this->object->id),
                ]
            );
        }
    }

    // Livewire lifecycle hooks
    public function updated($propertyName)
    {
        // $this->validateOnly($propertyName);
        if ($propertyName === 'input_details') {
            $this->checkDeliveryStatus();
        }

        // Deteksi perubahan tr_date dan reset tr_code jika bulan berubah
        if ($propertyName === 'inputs.tr_date') {
            $this->onTrDateChanged();
        }
    }

    // Constructor untuk menginisialisasi services
    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue, $additionalParam);

        $this->orderService = app(OrderService::class);
    }

    /**
     * Validasi duplikasi tr_code
     */
    private function validateTrCodeDuplicate()
    {
        $trCode = $this->inputs['tr_code'] ?? null;
        if (empty($trCode)) return;

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

    /*
     * Render view berdasarkan path yang telah ditentukan.
     */
    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
