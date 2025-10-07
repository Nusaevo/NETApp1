<?php

namespace App\Livewire\TrdTire1\Migration\Migration;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session, Log};
use App\Services\TrdTire1\Master\MasterService;
use App\Services\TrdTire1\InventoryService;
use App\Services\TrdTire1\BillingService;
use App\Services\TrdTire1\PartnerTrxService;
use App\Services\TrdTire1\PartnerBalanceService;
use App\Services\TrdTire1\PaymentService;
use App\Enums\Constant;
use App\Models\TrdTire1\Master\SalesReward;
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivPacking, DelivPicking, BillingHdr, PaymentHdr, PaymentDtl, PaymentSrc, PaymentAdv, OrderHdr, OrderDtl};
use App\Models\TrdTire1\Inventories\{IvttrHdr, IvttrDtl};

class Index extends BaseComponent
{
    protected $masterService;
    protected $inventoryService;
    protected $billingService;
    protected $partnerTrxService;
    protected $paymentService;
    protected $partnerBalanceService;
    protected $listeners = [];

    protected function onPreRender()
    {
        $this->masterService = new MasterService();
        $this->inventoryService = new InventoryService();
    }


    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    public function migrateAll()
    {
        // Set execution time limit to 3 hours
        set_time_limit(10800);
        ini_set('max_execution_time', 10800);
        ini_set('memory_limit', '512M');

        // Disable time limit for this specific operation
        ignore_user_abort(true);

        // Set database timeout for PostgreSQL
        DB::statement('SET statement_timeout = 10800000'); // 10800 seconds in milliseconds
        DB::statement('SET idle_in_transaction_session_timeout = 10800000'); // 10800 seconds in milliseconds


        // Send headers to prevent web server timeout
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        }

        $this->migrateToInventory();
        $this->migrateToBilling();
        $this->migratePaymentToPartner();
        $this->migrateOrderToInventory();
        $this->migrateIvttrToInventory();
    }

    public function migrateToInventory()
    {
        // Set execution time limit to 1 hour
        set_time_limit(3600);
        ini_set('max_execution_time', 3600);
        ini_set('memory_limit', '512M');

        // Disable time limit for this specific operation
        ignore_user_abort(true);

        // Set database timeout for PostgreSQL
        DB::statement('SET statement_timeout = 3600000'); // 3600 seconds in milliseconds
        DB::statement('SET idle_in_transaction_session_timeout = 3600000'); // 3600 seconds in milliseconds

        // Send headers to prevent web server timeout
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        }

        if (!$this->inventoryService) {
            $this->inventoryService = new InventoryService();
        }

        // Log mulai proses migrasi
        Log::info('=== MULAI MIGRASI INVENTORY ===', [
            'timestamp' => now(),
            'memory_usage' => memory_get_usage(true),
            'memory_limit' => ini_get('memory_limit')
        ]);

        try {
            $prepare = DelivPicking::join('deliv_packings as a', function($join) {
                $join->on('a.id', '=', 'deliv_pickings.trpacking_id')
                     ->whereIn('a.tr_type', ['PD', 'SD']);
            })
            ->select('deliv_pickings.*', 'a.tr_type', 'a.trhdr_id', 'a.tr_code', 'a.tr_seq', 'a.reffdtl_id')
            ->get();

            $totalRecords = $prepare->count();
            $processedCount = 0;
            $errorCount = 0;
            $successCount = 0;

            Log::info('Data yang akan diproses', [
                'total_records' => $totalRecords,
                'query_execution_time' => microtime(true)
            ]);

            foreach ($prepare as $index => $picking) {
                $processedCount++;

                try {
                    // Log progress setiap 100 record
                    if ($processedCount % 100 == 0) {
                        Log::info("Progress migrasi: {$processedCount}/{$totalRecords}", [
                            'percentage' => round(($processedCount / $totalRecords) * 100, 2),
                            'memory_usage' => memory_get_usage(true),
                            'success_count' => $successCount,
                            'error_count' => $errorCount
                        ]);
                    }

                    // Ambil data header delivery
                    $delivHdr = DelivHdr::find($picking->trhdr_id);
                    if (!$delivHdr) {
                        Log::warning('DelivHdr tidak ditemukan', [
                            'picking_id' => $picking->id,
                            'trhdr_id' => $picking->trhdr_id,
                            'tr_code' => $picking->tr_code
                        ]);
                        $errorCount++;
                        continue;
                    }

                    $headerData = [
                        'id' => $delivHdr->id,
                        'tr_date' => $delivHdr->tr_date,
                        'tr_type' => $delivHdr->tr_type,
                        'tr_code' => $delivHdr->tr_code,
                        // 'reff_id' => $delivHdr->id,
                    ];

                    // Log detail data yang akan diproses
                    Log::debug('Memproses picking', [
                        'picking_id' => $picking->id,
                        'tr_code' => $picking->tr_code,
                        'matl_code' => $picking->matl_code,
                        'qty' => $picking->qty,
                        'wh_code' => $picking->wh_code,
                        'batch_code' => $picking->batch_code
                    ]);

                    // Proses addReservation
                    try {
                        $packingData = [
                            'id' => $picking->trpacking_id,
                            'trhdr_id' => $picking->trhdr_id,
                            'tr_code' => $picking->tr_code,
                            'tr_seq' => $picking->tr_seq,
                            'matl_id' => $picking->matl_id,
                            'matl_code' => $picking->matl_code,
                            'matl_uom' => $picking->matl_uom,
                            'wh_id' => 0,
                            'wh_code' => '',
                            'batch_code' => '',
                            'qty' => $picking->qty,
                            'reffdtl_id' => $picking->reffdtl_id
                        ];

                        $this->inventoryService->addReservation($headerData, $packingData);

                        Log::debug('addReservation berhasil', [
                            'picking_id' => $picking->id,
                            'tr_code' => $picking->tr_code,
                            'matl_code' => $picking->matl_code
                        ]);

                    } catch (\Exception $e) {
                        Log::error('Error pada addReservation', [
                            'picking_id' => $picking->id,
                            'tr_code' => $picking->tr_code,
                            'matl_code' => $picking->matl_code,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        $errorCount++;
                        continue; // Skip ke record berikutnya jika addReservation gagal
                    }

                    // Proses addOnhand
                    try {
                        $pickingData = [
                            'id' => $picking->id,
                            'trhdr_id' => $picking->trhdr_id,
                            'tr_code' => $picking->tr_code,
                            'tr_seq' => $picking->tr_seq,
                            'tr_seq2' => $picking->tr_seq2 ?? 0,
                            'matl_id' => $picking->matl_id,
                            'matl_code' => $picking->matl_code,
                            'matl_uom' => $picking->matl_uom,
                            'wh_id' => $picking->wh_id,
                            'wh_code' => $picking->wh_code,
                            'batch_code' => $picking->batch_code,
                            'qty' => $picking->qty,
                            'reffdtl_id' => $picking->reffdtl_id
                        ];

                        $this->inventoryService->addOnhand($headerData, $pickingData);

                        Log::debug('addOnhand berhasil', [
                            'picking_id' => $picking->id,
                            'tr_code' => $picking->tr_code,
                            'matl_code' => $picking->matl_code
                        ]);

                        $successCount++;

                    } catch (\Exception $e) {
                        Log::error('Error pada addOnhand', [
                            'picking_id' => $picking->id,
                            'tr_code' => $picking->tr_code,
                            'matl_code' => $picking->matl_code,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        $errorCount++;
                    }

                } catch (\Exception $e) {
                    Log::error('Error umum pada record', [
                        'picking_id' => $picking->id ?? 'unknown',
                        'index' => $index,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $errorCount++;
                }
            }

            // Log hasil akhir
            Log::info('=== MIGRASI INVENTORY SELESAI ===', [
                'total_records' => $totalRecords,
                'processed_count' => $processedCount,
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'success_rate' => $totalRecords > 0 ? round(($successCount / $totalRecords) * 100, 2) : 0,
                'final_memory_usage' => memory_get_usage(true),
                'peak_memory_usage' => memory_get_peak_usage(true),
                'execution_time' => microtime(true),
                'timestamp' => now()
            ]);

            // Tampilkan notifikasi ke user
            $this->dispatch('showAlert', [
                'type' => $errorCount > 0 ? 'warning' : 'success',
                'message' => "Migrasi selesai! Berhasil: {$successCount}, Error: {$errorCount} dari {$totalRecords} record"
            ]);

        } catch (\Exception $e) {
            Log::error('FATAL ERROR pada migrasi inventory', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'memory_usage' => memory_get_usage(true),
                'timestamp' => now()
            ]);

            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Terjadi error fatal pada migrasi: ' . $e->getMessage()
            ]);

            throw $e; // Re-throw untuk debugging jika diperlukan
        }
    }

    public function migrateToBilling()
    {
        // Set execution time limit to 1 hour
        set_time_limit(3600);
        ini_set('max_execution_time', 3600);
        ini_set('memory_limit', '512M');

        // Disable time limit for this specific operation
        ignore_user_abort(true);

        // Set database timeout for PostgreSQL
        DB::statement('SET statement_timeout = 3600000'); // 3600 seconds in milliseconds
        DB::statement('SET idle_in_transaction_session_timeout = 3600000'); // 3600 seconds in milliseconds

        // Send headers to prevent web server timeout
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        }

        if (!$this->billingService) {
            $deliveryService = new \App\Services\TrdTire1\DeliveryService(new InventoryService());
            $partnerBalanceService = new PartnerBalanceService();
            $this->billingService = new BillingService($deliveryService, $partnerBalanceService);
        }

        // Ambil data delivery PD dan SD
        $deliveries = DelivHdr::whereIn('tr_type', ['PD', 'SD'])
            ->with(['DelivPacking.OrderDtl'])
            ->get();

        $processedCount = 0;
        foreach ($deliveries as $delivery) {
            try {
                // Tentukan tr_type billing berdasarkan delivery type
                $billingType = $delivery->tr_type === 'PD' ? 'APB' : 'ARB';

                // Siapkan data header billing
                $headerData = [
                    'id' => 0, // New billing
                    'tr_type' => $billingType,
                    'tr_code' => $delivery->tr_code,
                    'tr_date' => $delivery->tr_date,
                ];

                // Siapkan data detail billing - BillingService mengharapkan deliv_id
                $detailData = [
                    [
                        'deliv_id' => $delivery->id
                    ]
                ];

                // Simpan billing
                $this->billingService->saveBilling($headerData, $detailData);

                // Update amt_reff setelah partner balance berhasil dibuat (seperti di PaymentService)
                // if (isset($billingResult['billing_hdr']) && $billingResult['billing_hdr']) {
                //     $billingHdr = $billingResult['billing_hdr'];
                //     $this->billingService->updAmtReff('+', $billingHdr->amt, $billingHdr->id);
                // }

                $processedCount++;

            } catch (\Exception $e) {
                Log::error("Error creating billing for delivery {$delivery->tr_code}: " . $e->getMessage());
                continue;
            }
        }

        $this->dispatch('show-message', [
            'type' => 'success',
            'message' => "Migrasi data delivery ke billing berhasil dilakukan! Total: {$processedCount} records"
        ]);
    }

    public function migratePaymentToPartner()
    {
        // Set execution time limit to 1 hour
        set_time_limit(3600);
        ini_set('max_execution_time', 3600);
        ini_set('memory_limit', '512M');

        // Disable time limit for this specific operation
        ignore_user_abort(true);

        // Set database timeout for PostgreSQL
        DB::statement('SET statement_timeout = 3600000'); // 3600 seconds in milliseconds
        DB::statement('SET idle_in_transaction_session_timeout = 3600000'); // 3600 seconds in milliseconds

        // Send headers to prevent web server timeout
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        }

        if (!$this->partnerBalanceService) {
            $this->partnerBalanceService = new PartnerBalanceService();
        }

        // Ambil data payment yang belum di-migrate ke partner balance
        $payments = PaymentHdr::with(['PaymentDtl', 'paymentSrc', 'PaymentAdv'])
            ->whereIn('tr_type', ['ARP']) // ARP = Accounts Receivable Payment (APP belum didukung)
            ->get();

        $processedCount = 0;
        $errorCount = 0;

        foreach ($payments as $payment) {
            try {
                // Siapkan data header payment
                $headerData = [
                    'id' => $payment->id,
                    'tr_date' => $payment->tr_date,
                    'tr_type' => $payment->tr_type,
                    'tr_code' => $payment->tr_code,
                    'curr_id' => $payment->curr_id,
                    'curr_code' => $payment->curr_code,
                    'curr_rate' => $payment->curr_rate,
                    'partner_id' => $payment->partner_id,
                    'partner_code' => $payment->partner_code,
                ];

                // Proses PaymentDtl (detail pembayaran ke billing)
                foreach ($payment->PaymentDtl as $paymentDtl) {
                    // Skip jika tipe tidak dikenali oleh updFromPayment
                    if (!in_array($paymentDtl->tr_type, ['ARP', 'ARPS', 'ARPA'])) {
                        continue;
                    }

                    $detailData = [
                        'id' => $paymentDtl->id,
                        'trhdr_id' => $paymentDtl->trhdr_id,
                        'tr_type' => $paymentDtl->tr_type,
                        'tr_code' => $paymentDtl->tr_code,
                        'tr_seq' => $paymentDtl->tr_seq,
                        'partner_id' => $payment->partner_id,
                        'partner_code' => $payment->partner_code,
                        'billhdr_id' => $paymentDtl->billhdr_id,
                        'billhdrtr_type' => $paymentDtl->billhdrtr_type,
                        'billhdrtr_code' => $paymentDtl->billhdrtr_code,
                        'amt' => $paymentDtl->amt,
                    ];

                    $this->partnerBalanceService->updFromPayment($headerData, $detailData);

                    // Update amt_reff di BillingHdr setelah partner balance dibuat
                    BillingHdr::updAmtReff($detailData['billhdr_id'], $detailData['amt']);
                }

                // Proses PaymentSrc (sumber pembayaran)
                foreach ($payment->paymentSrc as $paymentSrc) {
                    // Skip jika tipe tidak dikenali oleh updFromPayment
                    if (!in_array($paymentSrc->tr_type, ['ARP', 'ARPS', 'ARPA'])) {
                        continue;
                    }

                    $sourceData = [
                        'id' => $paymentSrc->id,
                        'trhdr_id' => $paymentSrc->trhdr_id,
                        'tr_type' => $paymentSrc->tr_type,
                        'tr_code' => $paymentSrc->tr_code,
                        'tr_seq' => $paymentSrc->tr_seq,
                        'partner_id' => $payment->partner_id,
                        'partner_code' => $payment->partner_code,
                        'reff_id' => $paymentSrc->reff_id,
                        'reff_type' => $paymentSrc->reff_type,
                        'reff_code' => $paymentSrc->reff_code,
                        'bank_id' => $paymentSrc->bank_id,
                        'bank_code' => $paymentSrc->bank_code,
                        'bank_reff' => $paymentSrc->bank_reff,
                        'bank_duedt' => $paymentSrc->bank_duedt,
                        'bank_note' => $paymentSrc->bank_note,
                        'amt' => $paymentSrc->amt,
                    ];

                    $this->partnerBalanceService->updFromPayment($headerData, $sourceData);
                }

                // Proses PaymentAdv (pembayaran advance)
                foreach ($payment->PaymentAdv as $paymentAdv) {
                    // Skip jika tipe tidak dikenali oleh updFromPayment
                    if (!in_array($paymentAdv->tr_type, ['ARP', 'ARPS', 'ARPA'])) {
                        continue;
                    }

                    $advanceData = [
                        'id' => $paymentAdv->id,
                        'trhdr_id' => $paymentAdv->trhdr_id,
                        'tr_type' => $paymentAdv->tr_type,
                        'tr_code' => $paymentAdv->tr_code,
                        'tr_seq' => $paymentAdv->tr_seq,
                        'partner_id' => $payment->partner_id,
                        'partner_code' => $payment->partner_code,
                        'reff_id' => $paymentAdv->reff_id,
                        'reff_type' => $paymentAdv->reff_type,
                        'reff_code' => $paymentAdv->reff_code,
                        'amt' => $paymentAdv->amt,
                    ];

                    $this->partnerBalanceService->updFromPayment($headerData, $advanceData);
                }

                $processedCount++;

            } catch (\Exception $e) {
                Log::error("Error migrating payment {$payment->tr_code} to partner balance: " . $e->getMessage());
                $errorCount++;
                continue;
            }
        }

        $this->dispatch('show-message', [
            'type' => 'success',
            'message' => "Migrasi data payment ke partner balance berhasil dilakukan! Total: {$processedCount} records, Error: {$errorCount} records"
        ]);
    }

    public function migrateOrderToInventory()
    {
        // Set execution time limit to 1 hour
        set_time_limit(3600);
        ini_set('max_execution_time', 3600);
        ini_set('memory_limit', '512M');

        // Disable time limit for this specific operation
        ignore_user_abort(true);

        // Set database timeout for PostgreSQL
        DB::statement('SET statement_timeout = 3600000'); // 3600 seconds in milliseconds
        DB::statement('SET idle_in_transaction_session_timeout = 3600000'); // 3600 seconds in milliseconds

        // Send headers to prevent web server timeout
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        }

        if (!$this->inventoryService) {
            $this->inventoryService = new InventoryService();
        }

        // Debug: Cek semua tipe order yang ada
        $allOrderTypes = OrderHdr::select('tr_type')->distinct()->pluck('tr_type');
        Log::info("Available order types: " . $allOrderTypes->implode(', '));

        // Ambil data order yang belum di-migrate ke inventory dengan batch processing
        $totalOrders = OrderHdr::whereIn('tr_type', ['PO', 'SO'])->count();
        Log::info("Total orders found with PO/SO: " . $totalOrders);

        $batchSize = 100; // Process 100 orders at a time
        $offset = 0;

        $processedCount = 0;
        $errorCount = 0;

        // Process orders in batches to avoid memory issues
        while ($offset < $totalOrders) {
            Log::info("Processing batch: offset {$offset}, limit {$batchSize}");

            $orders = OrderHdr::with(['OrderDtl'])
                ->whereIn('tr_type', ['PO', 'SO'])
                ->offset($offset)
                ->limit($batchSize)
                ->get();

            foreach ($orders as $order) {
                try {
                    Log::info("Processing order: " . $order->tr_code . " - Type: " . $order->tr_type);

                    // Siapkan data header order
                    $headerData = [
                        'id' => $order->id,
                        'tr_date' => $order->tr_date,
                        'tr_type' => $order->tr_type,
                        'tr_code' => $order->tr_code,
                        // 'reff_id' => $order->reff_code ?? 0
                    ];

                    // Proses OrderDtl (detail order)
                    Log::info("OrderDtl count: " . $order->OrderDtl->count());
                    foreach ($order->OrderDtl as $orderDtl) {
                        $detailData = [
                            'id' => $orderDtl->id,
                            'trhdr_id' => $orderDtl->trhdr_id,
                            'tr_type' => $orderDtl->tr_type,
                            'tr_code' => $orderDtl->tr_code,
                            'tr_seq' => $orderDtl->tr_seq,
                            'matl_id' => $orderDtl->matl_id,
                            'matl_code' => $orderDtl->matl_code,
                            'matl_uom' => $orderDtl->matl_uom,
                            'wh_id' => 0,
                            'wh_code' => '',
                            'batch_code' => '',
                            'qty' => $orderDtl->qty,
                            'price_beforetax' => $orderDtl->price_beforetax,
                            'reffdtl_id' => $orderDtl->id
                        ];

                        Log::info("Processing OrderDtl: " . json_encode($detailData));
                        // Tambahkan reservation untuk order
                        $this->inventoryService->addReservation($headerData, $detailData);
                    }

                    $processedCount++;

                } catch (\Exception $e) {
                    Log::error("Error migrating order {$order->tr_code} to inventory: " . $e->getMessage());
                    $errorCount++;
                    continue;
                }
            }

            $offset += $batchSize;

            // Clear memory
            unset($orders);
            gc_collect_cycles();

            Log::info("Batch completed. Processed: {$processedCount}, Errors: {$errorCount}");
        }

        $this->dispatch('show-message', [
            'type' => 'success',
            'message' => "Migrasi data order ke inventory berhasil dilakukan! Total: {$processedCount} records, Error: {$errorCount} records"
        ]);
    }

    public function migrateIvttrToInventory()
    {
        // Set execution time limit to 1 hour
        set_time_limit(3600);
        ini_set('max_execution_time', 3600);
        ini_set('memory_limit', '1024M');

        // Disable time limit for this specific operation
        ignore_user_abort(true);

        // Set database timeout for PostgreSQL
        DB::statement('SET statement_timeout = 3600000');
        DB::statement('SET idle_in_transaction_session_timeout = 3600000');

        // Send headers to prevent web server timeout
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        }

        if (!$this->inventoryService) {
            $this->inventoryService = new InventoryService();
        }

        // Ambil data IvttrHdr yang belum di-migrate
        $totalHeaders = IvttrHdr::count();
        Log::info("Found {$totalHeaders} IvttrHdr to migrate");

        $processedCount = 0;
        $chunkSize = 20; // Process 20 headers at a time

        IvttrHdr::with(['IvttrDtl'])
            ->chunk($chunkSize, function ($headers) use (&$processedCount) {
                foreach ($headers as $header) {
                    try {
                        Log::info("Processing IvttrHdr: {$header->tr_code} (ID: {$header->id})");
                        Log::info("Header has " . $header->IvttrDtl->count() . " details");

                        // Siapkan data header
                        $headerData = [
                            'id' => $header->id,
                            'tr_type' => $header->tr_type,
                            'tr_code' => $header->tr_code,
                            'tr_date' => $header->tr_date,
                            'reff_id' => $header->reff_id ?? 0,
                            'descr' => $header->descr ?? '',
                        ];

                        // Proses setiap detail
                        foreach ($header->IvttrDtl as $detail) {
                            $detailData = [
                                'id' => $detail->id,
                                'trhdr_id' => $detail->trhdr_id,
                                'tr_seq' => $detail->tr_seq,
                                'tr_seq2' => $detail->tr_seq2 ?? 0,
                                'matl_id' => $detail->matl_id,
                                'matl_code' => $detail->matl_code ?? '',
                                'matl_uom' => $detail->matl_uom ?? '',
                                'wh_id' => $detail->wh_id ?? 0,
                                'wh_code' => $detail->wh_code ?? '',
                                'batch_code' => $detail->batch_code ?? '',
                                'qty' => $detail->qty,
                                'price_beforetax' => $detail->price_beforetax ?? 0,
                                'reffdtl_id' => $detail->reffdtl_id ?? 0,
                            ];

                            // Panggil method yang sesuai berdasarkan tr_type
                            if ($header->tr_type === 'IA') {
                                // Inventory Adjustment - gunakan addOnhand
                                $this->inventoryService->addOnhand($headerData, $detailData);
                            } else if ($header->tr_type === 'TW') {
                                // Transfer Warehouse - gunakan addOnhand untuk kedua warehouse
                                $this->inventoryService->addOnhand($headerData, $detailData);
                            } else if (in_array($header->tr_type, ['PO', 'SO', 'PD', 'SD'])) {
                                // Purchase/Sales Order/Delivery - gunakan addReservation
                                $this->inventoryService->addReservation($headerData, $detailData);
                            }
                        }

                        $processedCount++;
                        Log::info("Successfully migrated IvttrHdr: {$header->tr_code}");

                    } catch (\Exception $e) {
                        Log::error("Error migrating IvttrHdr {$header->tr_code}: " . $e->getMessage());
                        Log::error("Stack trace: " . $e->getTraceAsString());
                        continue;
                    }
                }

                // Force garbage collection after each chunk
                gc_collect_cycles();
            });

        $this->dispatch('show-message', [
            'type' => 'success',
            'message' => "Migrasi data IvttrHdr ke inventory berhasil dilakukan! Total: {$processedCount} records dari {$totalHeaders} header"
        ]);
    }

    /**
     * Migrasi khusus untuk Inventory Adjustment (tr_type = 'IA')
     */
    public function migrateInventoryAdjustment()
    {
        // Set execution time limit to 1 hour
        set_time_limit(3600);
        ini_set('max_execution_time', 3600);
        ini_set('memory_limit', '1024M');

        // Disable time limit for this specific operation
        ignore_user_abort(true);

        // Set database timeout for PostgreSQL
        DB::statement('SET statement_timeout = 3600000');
        DB::statement('SET idle_in_transaction_session_timeout = 3600000');

        // Send headers to prevent web server timeout
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        }

        if (!$this->inventoryService) {
            $this->inventoryService = new InventoryService();
        }

        // Ambil data IvttrHdr dengan tr_type = 'IA' saja
        $totalHeaders = IvttrHdr::where('tr_type', 'IA')->count();
        Log::info("Found {$totalHeaders} Inventory Adjustment (IA) records to migrate");

        if ($totalHeaders == 0) {
            $this->dispatch('show-message', [
                'type' => 'info',
                'message' => "Tidak ada data Inventory Adjustment (IA) yang perlu di-migrate."
            ]);
            return;
        }

        $processedCount = 0;
        $errorCount = 0;
        $chunkSize = 50; // Process 50 IA records at a time (IA biasanya lebih sederhana)

        IvttrHdr::where('tr_type', 'IA')
            ->with(['IvttrDtl'])
            ->chunk($chunkSize, function ($headers) use (&$processedCount, &$errorCount) {
                foreach ($headers as $header) {
                    try {
                        Log::info("Processing IA Header: {$header->tr_code} (ID: {$header->id})");
                        Log::info("IA Header has " . $header->IvttrDtl->count() . " details");

                        // Validasi data header IA
                        if (empty($header->tr_code) || empty($header->tr_date)) {
                            Log::warning("Skipping IA Header {$header->id}: Missing required fields");
                            $errorCount++;
                            continue;
                        }

                        // Siapkan data header untuk IA
                        $headerData = [
                            'id' => $header->id,
                            'tr_type' => $header->tr_type,
                            'tr_code' => $header->tr_code,
                            'tr_date' => $header->tr_date,
                            'reff_id' => $header->reff_id ?? 0,
                            'descr' => $header->descr ?? 'Inventory Adjustment',
                        ];

                        // Proses setiap detail IA
                        foreach ($header->IvttrDtl as $detail) {
                            try {
                                // Validasi data detail IA
                                if (empty($detail->matl_id) || $detail->qty == 0) {
                                    Log::warning("Skipping IA Detail {$detail->id}: Invalid material or quantity");
                                    continue;
                                }

                                $detailData = [
                                    'id' => $detail->id,
                                    'trhdr_id' => $detail->trhdr_id,
                                    'tr_seq' => $detail->tr_seq,
                                    'tr_seq2' => $detail->tr_seq2 ?? 0,
                                    'matl_id' => $detail->matl_id,
                                    'matl_code' => $detail->matl_code ?? '',
                                    'matl_uom' => $detail->matl_uom ?? 'PCS',
                                    'wh_id' => $detail->wh_id ?? 0,
                                    'wh_code' => $detail->wh_code ?? '',
                                    'batch_code' => $detail->batch_code ?? '',
                                    'qty' => $detail->qty,
                                    'price_beforetax' => $detail->price_beforetax ?? 0,
                                    'reffdtl_id' => $detail->reffdtl_id ?? 0,
                                ];

                                // Untuk IA, gunakan addOnhand untuk menyesuaikan stok
                                $result = $this->inventoryService->addOnhand($headerData, $detailData);

                                if ($result) {
                                    Log::info("Successfully processed IA Detail: Material {$detail->matl_code}, Qty: {$detail->qty}");
                                } else {
                                    Log::warning("Failed to process IA Detail: Material {$detail->matl_code}");
                                }

                            } catch (\Exception $e) {
                                Log::error("Error processing IA Detail {$detail->id}: " . $e->getMessage());
                                $errorCount++;
                                continue;
                            }
                        }

                        $processedCount++;
                        Log::info("Successfully migrated IA Header: {$header->tr_code}");

                    } catch (\Exception $e) {
                        Log::error("Error migrating IA Header {$header->tr_code}: " . $e->getMessage());
                        Log::error("Stack trace: " . $e->getTraceAsString());
                        $errorCount++;
                        continue;
                    }
                }

                // Force garbage collection after each chunk
                gc_collect_cycles();
            });

        $message = "Migrasi Inventory Adjustment (IA) berhasil! Diproses: {$processedCount} records, Error: {$errorCount} records dari total {$totalHeaders} header";

        $this->dispatch('show-message', [
            'type' => 'success',
            'message' => $message
        ]);

        session()->flash('migration_success', $message);
    }

    /**
     * Migrasi khusus untuk Sales Order dari OrderHdr dan OrderDtl dengan reff_code = 'baru'
     */
    public function migrateSalesOrder()
    {
        // Set execution time limit to 1 hour
        set_time_limit(3600);
        ini_set('max_execution_time', 3600);
        ini_set('memory_limit', '1024M');

        // Disable time limit for this specific operation
        ignore_user_abort(true);

        // Set database timeout for PostgreSQL
        DB::statement('SET statement_timeout = 3600000');
        DB::statement('SET idle_in_transaction_session_timeout = 3600000');

        // Send headers to prevent web server timeout
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        }

        if (!$this->inventoryService) {
            $this->inventoryService = new InventoryService();
        }

        // Ambil data OrderHdr dengan tr_type = 'SO' dan reff_code = 'baru'
        $totalHeaders = OrderHdr::where('tr_type', 'SO')
            ->where('reff_code', 'baru')
            ->count();

        Log::info("Found {$totalHeaders} Sales Order (SO) records from OrderHdr with reff_code = 'baru' to migrate");

        if ($totalHeaders == 0) {
            $this->dispatch('show-message', [
                'type' => 'info',
                'message' => "Tidak ada data Sales Order (SO) dengan reff_code = 'baru' yang perlu di-migrate."
            ]);
            return;
        }

        $processedCount = 0;
        $errorCount = 0;
        $chunkSize = 30; // Process 30 SO records at a time

        OrderHdr::where('tr_type', 'SO')
            ->where('reff_code', 'baru')
            ->with(['OrderDtl'])
            ->chunk($chunkSize, function ($headers) use (&$processedCount, &$errorCount) {
                foreach ($headers as $header) {
                    try {
                        Log::info("Processing SO Header: {$header->tr_code} (ID: {$header->id}) - Reff Code: {$header->reff_code}");
                        Log::info("SO Header has " . $header->OrderDtl->count() . " details");

                        // Validasi data header SO
                        if (empty($header->tr_code) || empty($header->tr_date) || $header->reff_code !== 'baru') {
                            Log::warning("Skipping SO Header {$header->id}: Missing required fields or reff_code is not 'baru'");
                            $errorCount++;
                            continue;
                        }

                        // Siapkan data header untuk SO
                        $headerData = [
                            'id' => $header->id,
                            'tr_type' => $header->tr_type,
                            'tr_code' => $header->tr_code,
                            'tr_date' => $header->tr_date,
                            'reff_id' => $header->id, // Gunakan ID order sebagai reff_id
                            'reff_code' => $header->reff_code,
                            'descr' => $header->note ?? 'Sales Order',
                        ];

                        // Proses setiap detail SO
                        foreach ($header->OrderDtl as $detail) {
                            try {
                                // Validasi data detail SO
                                if (empty($detail->matl_id) || $detail->qty <= 0) {
                                    Log::warning("Skipping SO Detail {$detail->id}: Invalid material or quantity");
                                    continue;
                                }

                                $detailData = [
                                    'id' => $detail->id,
                                    'trhdr_id' => $detail->trhdr_id,
                                    'tr_code' => $header->tr_code, // Tambahkan tr_code dari header
                                    'tr_seq' => $detail->tr_seq,
                                    'tr_seq2' => 0, // OrderDtl tidak memiliki tr_seq2
                                    'matl_id' => $detail->matl_id,
                                    'matl_code' => $detail->matl_code ?? '',
                                    'matl_uom' => $detail->matl_uom ?? 'PCS',
                                    'wh_id' => 0, // OrderDtl tidak memiliki wh_id, default ke 0
                                    'wh_code' => '', // OrderDtl tidak memiliki wh_code
                                    'batch_code' => '', // OrderDtl tidak memiliki batch_code
                                    'qty' => $detail->qty,
                                    'price_beforetax' => $detail->price_beforetax ?? 0,
                                    'reffdtl_id' => $detail->id, // Gunakan ID detail sebagai reffdtl_id
                                ];

                                // Untuk SO, gunakan addReservation untuk membuat reservasi stok
                                $this->inventoryService->addReservation($headerData, $detailData);

                                Log::info("Successfully processed SO Detail: Material {$detail->matl_code}, Qty: {$detail->qty}, Price: {$detail->price_beforetax}");

                            } catch (\Exception $e) {
                                Log::error("Error processing SO Detail {$detail->id}: " . $e->getMessage());
                                Log::error("Stack trace: " . $e->getTraceAsString());
                                $errorCount++;
                                continue;
                            }
                        }

                        $processedCount++;
                        Log::info("Successfully migrated SO Header: {$header->tr_code} with reff_code: {$header->reff_code}");

                    } catch (\Exception $e) {
                        Log::error("Error migrating SO Header {$header->tr_code}: " . $e->getMessage());
                        Log::error("Stack trace: " . $e->getTraceAsString());
                        $errorCount++;
                        continue;
                    }
                }

                // Force garbage collection after each chunk
                gc_collect_cycles();
            });

        $message = "Migrasi Sales Order (SO) dari OrderHdr/OrderDtl dengan reff_code = 'baru' berhasil! Diproses: {$processedCount} records, Error: {$errorCount} records dari total {$totalHeaders} header";

        $this->dispatch('show-message', [
            'type' => 'success',
            'message' => $message
        ]);

        session()->flash('migration_success', $message);
    }

    /**
     * Migrasi khusus untuk Sales Delivery dari DelivHdr dan DelivPacking dengan reff_code = 'baru'
     */
    public function migrateSalesDelivery()
    {
        // Set execution time limit to 1 hour
        set_time_limit(3600);
        ini_set('max_execution_time', 3600);
        ini_set('memory_limit', '1024M');

        // Disable time limit for this specific operation
        ignore_user_abort(true);

        // Set database timeout for PostgreSQL
        DB::statement('SET statement_timeout = 3600000');
        DB::statement('SET idle_in_transaction_session_timeout = 3600000');

        // Send headers to prevent web server timeout
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        }

        if (!$this->inventoryService) {
            $this->inventoryService = new InventoryService();
        }

        // Ambil data DelivHdr dengan tr_type = 'SD' dan reff_code = 'baru'
        $totalHeaders = DelivHdr::where('tr_type', 'SD')
            ->where('reff_code', 'baru')
            ->count();

        Log::info("Found {$totalHeaders} Sales Delivery (SD) records from DelivHdr with reff_code = 'baru' to migrate");

        if ($totalHeaders == 0) {
            $this->dispatch('show-message', [
                'type' => 'info',
                'message' => "Tidak ada data Sales Delivery (SD) dengan reff_code = 'baru' yang perlu di-migrate."
            ]);
            return;
        }

        $processedCount = 0;
        $errorCount = 0;
        $chunkSize = 30; // Process 30 SD records at a time

        DelivHdr::where('tr_type', 'SD')
            ->where('reff_code', 'baru')
            ->chunk($chunkSize, function ($headers) use (&$processedCount, &$errorCount) {
                foreach ($headers as $header) {
                    try {
                        Log::info("Processing SD Header: {$header->tr_code} (ID: {$header->id}) - Reff Code: {$header->reff_code}");
                        Log::info("SD Header has " . $header->DelivPacking->count() . " details");

                        // Debug: Cek data DelivPacking langsung
                        $delivPackingCount = DelivPacking::where('trhdr_id', $header->id)->count();
                        Log::info("Direct DelivPacking count for header {$header->id}: {$delivPackingCount}");

                        // Debug: Cek data DelivPacking dengan tr_type
                        $delivPackingWithType = DelivPacking::where('trhdr_id', $header->id)
                            ->where('tr_type', $header->tr_type)
                            ->count();
                        Log::info("DelivPacking with tr_type '{$header->tr_type}' count: {$delivPackingWithType}");

                        // Validasi data header SD
                        if (empty($header->tr_code) || empty($header->tr_date) || $header->reff_code !== 'baru') {
                            Log::warning("Skipping SD Header {$header->id}: Missing required fields or reff_code is not 'baru'");
                            $errorCount++;
                            continue;
                        }

                        // Siapkan data header untuk SD
                        $headerData = [
                            'id' => $header->id,
                            'tr_type' => $header->tr_type,
                            'tr_code' => $header->tr_code,
                            'tr_date' => $header->tr_date,
                            'reff_id' => $header->id, // Gunakan ID delivery sebagai reff_id
                            'reff_code' => $header->reff_code,
                            'descr' => $header->note ?? 'Sales Delivery',
                        ];

                        // Ambil data DelivPacking langsung tanpa relasi
                        $delivPackingDetails = DelivPacking::where('trhdr_id', $header->id)
                            ->where('tr_type', $header->tr_type)
                            ->orderBy('tr_seq')
                            ->get();

                        Log::info("Found {$delivPackingDetails->count()} DelivPacking details for processing");

                        // Proses setiap detail SD
                        foreach ($delivPackingDetails as $detail) {
                            try {
                                // Validasi data detail SD
                                if (empty($detail->reffdtl_id) || $detail->qty <= 0) {
                                    Log::warning("Skipping SD Detail {$detail->id}: Invalid reffdtl_id or quantity");
                                    continue;
                                }

                                // Ambil data material dari OrderDtl yang direferensikan
                                $orderDtl = OrderDtl::find($detail->reffdtl_id);
                                if (!$orderDtl) {
                                    Log::warning("Skipping SD Detail {$detail->id}: OrderDtl not found for reffdtl_id {$detail->reffdtl_id}");
                                    continue;
                                }

                                // Ambil data warehouse dan batch dari DelivPicking
                                $delivPicking = DelivPicking::where('trpacking_id', $detail->id)->first();
                                $whId = $delivPicking ? $delivPicking->wh_id : 0;
                                $whCode = $delivPicking ? $delivPicking->wh_code : '';
                                $batchCode = $delivPicking ? $delivPicking->batch_code : '';

                                Log::info("DelivPacking {$detail->id} -> DelivPicking: wh_id={$whId}, wh_code={$whCode}, batch_code={$batchCode}");

                                $detailData = [
                                    'id' => $detail->id,
                                    'trhdr_id' => $detail->trhdr_id,
                                    'tr_code' => $header->tr_code, // Tambahkan tr_code dari header
                                    'tr_seq' => $detail->tr_seq,
                                    'tr_seq2' => 0, // DelivPacking tidak memiliki tr_seq2
                                    'matl_id' => $orderDtl->matl_id,
                                    'matl_code' => $orderDtl->matl_code ?? '',
                                    'matl_uom' => $orderDtl->matl_uom ?? 'PCS',
                                    'wh_id' => $whId, // Dari DelivPicking
                                    'wh_code' => $whCode, // Dari DelivPicking
                                    'batch_code' => $batchCode, // Dari DelivPicking
                                    'qty' => $detail->qty,
                                    'price_beforetax' => $orderDtl->price_beforetax ?? 0,
                                    'reffdtl_id' => $detail->reffdtl_id, // ID dari OrderDtl yang direferensikan
                                ];

                                // Untuk SD, gunakan addOnhand untuk mengurangi stok
                                $this->inventoryService->addOnhand($headerData, $detailData);

                                Log::info("Successfully processed SD Detail: Material {$orderDtl->matl_code}, Qty: {$detail->qty}, Price: {$orderDtl->price_beforetax}");

                            } catch (\Exception $e) {
                                Log::error("Error processing SD Detail {$detail->id}: " . $e->getMessage());
                                Log::error("Stack trace: " . $e->getTraceAsString());
                                $errorCount++;
                                continue;
                            }
                        }

                        $processedCount++;
                        Log::info("Successfully migrated SD Header: {$header->tr_code} with reff_code: {$header->reff_code}");

                    } catch (\Exception $e) {
                        Log::error("Error migrating SD Header {$header->tr_code}: " . $e->getMessage());
                        Log::error("Stack trace: " . $e->getTraceAsString());
                        $errorCount++;
                        continue;
                    }
                }

                // Force garbage collection after each chunk
                gc_collect_cycles();
            });

        $this->dispatch('show-message', [
            'type' => 'success',
            'message' => "Migrasi Sales Delivery (SD) dari DelivHdr/DelivPacking dengan reff_code = 'baru' berhasil! Diproses: {$processedCount} records, Error: {$errorCount} records dari total {$totalHeaders} header"
        ]);
    }

    public function migrateSalesBillingSDbaru()
    {
        // Set execution time limit to 1 hour
        set_time_limit(3600);
        ini_set('max_execution_time', 3600);
        ini_set('memory_limit', '512M');

        // Disable time limit for this specific operation
        ignore_user_abort(true);

        // Set database timeout for PostgreSQL
        DB::statement('SET statement_timeout = 3600000'); // 3600 seconds in milliseconds
        DB::statement('SET idle_in_transaction_session_timeout = 3600000'); // 3600 seconds in milliseconds

        // Send headers to prevent web server timeout
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        }

        if (!$this->billingService) {
            $deliveryService = new \App\Services\TrdTire1\DeliveryService(new InventoryService());
            $partnerBalanceService = new PartnerBalanceService();
            $this->billingService = new BillingService($deliveryService, $partnerBalanceService);
        }

        // Ambil data delivery SD dengan reff_code = 'baru'
        $deliveries = DelivHdr::where('tr_type', 'SD')
            ->where('reff_code', 'baru')
            ->with(['DelivPacking.OrderDtl'])
            ->get();

        Log::info("Found {$deliveries->count()} Sales Delivery (SD) records with reff_code = 'baru' to migrate to billing");

        if ($deliveries->count() == 0) {
            $message = "Tidak ada data Sales Delivery (SD) dengan reff_code = 'baru' yang perlu di-migrate ke billing.";
            $this->dispatch('show-message', [
                'type' => 'info',
                'message' => $message
            ]);
            session()->flash('migration_info', $message);
            return;
        }

        $processedCount = 0;
        $errorCount = 0;
        foreach ($deliveries as $delivery) {
            try {
                Log::info("Processing SD Delivery for billing: {$delivery->tr_code} (ID: {$delivery->id}) - Reff Code: {$delivery->reff_code}");
                Log::info("SD Delivery has " . $delivery->DelivPacking->count() . " packing details");

                // Untuk SD, billing type adalah ARB
                $billingType = $delivery->tr_type === 'PD' ? 'APB' : 'ARB';
                // Siapkan data header billing
                $headerData = [
                    'id' => 0, // New billing
                    'tr_type' => $billingType,
                    'tr_code' => $delivery->tr_code,
                    'tr_date' => $delivery->tr_date,
                ];

                // Siapkan data detail billing - BillingService mengharapkan deliv_id
                $detailData = [
                    [
                        'deliv_id' => $delivery->id
                    ]
                ];

                Log::info("Creating billing for delivery {$delivery->tr_code} with type {$billingType}");

                // Simpan billing
                $this->billingService->saveBilling($headerData, $detailData);

                Log::info("Successfully created billing for delivery {$delivery->tr_code}");

                // Update amt_reff setelah partner balance berhasil dibuat (seperti di PaymentService)
                // if (isset($billingResult['billing_hdr']) && $billingResult['billing_hdr']) {
                //     $billingHdr = $billingResult['billing_hdr'];
                //     $this->billingService->updAmtReff('+', $billingHdr->amt, $billingHdr->id);
                // }

                $processedCount++;
                Log::info("Successfully migrated delivery {$delivery->tr_code} to billing");

            } catch (\Exception $e) {
                Log::error("Error creating billing for delivery {$delivery->tr_code}: " . $e->getMessage());
                Log::error("Stack trace: " . $e->getTraceAsString());
                $errorCount++;
                continue;
            }
        }

        $message = "Migrasi data Sales Delivery (SD) ke billing dengan reff_code = 'baru' berhasil! Diproses: {$processedCount} records, Error: {$errorCount} records";

        $this->dispatch('show-message', [
            'type' => 'success',
            'message' => $message
        ]);

        session()->flash('migration_success', $message);
    }

    public function migrateFromSDBaruToBilling()
    {
        // Set execution time limit to 1 hour
        set_time_limit(3600);
        ini_set('max_execution_time', 3600);
        ini_set('memory_limit', '512M');

        // Disable time limit for this specific operation
        ignore_user_abort(true);

        // Set database timeout for PostgreSQL
        DB::statement('SET statement_timeout = 3600000'); // 3600 seconds in milliseconds
        DB::statement('SET idle_in_transaction_session_timeout = 3600000'); // 3600 seconds in milliseconds

        // Send headers to prevent web server timeout
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: no-cache, must-revalidate');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        }

        if (!$this->billingService) {
            $deliveryService = new \App\Services\TrdTire1\DeliveryService(new InventoryService());
            $partnerBalanceService = new PartnerBalanceService();
            $this->billingService = new BillingService($deliveryService, $partnerBalanceService);
        }

        // Ambil data delivery PD dan SD
        $deliveries = DelivHdr::whereIn('tr_type', ['SD'])
            ->where('reff_code', 'baru')
            ->with(['DelivPacking.OrderDtl'])
            ->get();

        Log::info("Found {$deliveries->count()} Sales Delivery (SD) records with reff_code = 'baru' to migrate to billing");

        if ($deliveries->count() == 0) {
            $message = "Tidak ada data Sales Delivery (SD) dengan reff_code = 'baru' yang perlu di-migrate ke billing.";
            $this->dispatch('show-message', [
                'type' => 'info',
                'message' => $message
            ]);
            session()->flash('migration_info', $message);
            return;
        }

        $processedCount = 0;
        $errorCount = 0;
        foreach ($deliveries as $delivery) {
            try {
                // Tentukan tr_type billing berdasarkan delivery type
                $billingType = 'ARB';

                // Siapkan data header billing
                $headerData = [
                    'id' => 0, // New billing
                    'tr_type' => $billingType,
                    'tr_code' => $delivery->tr_code,
                    'tr_date' => $delivery->tr_date,
                ];

                // Siapkan data detail billing - BillingService mengharapkan deliv_id
                $detailData = [
                    [
                        'deliv_id' => $delivery->id
                    ]
                ];

                // Simpan billing
                $this->billingService->saveBilling($headerData, $detailData);

                // Update amt_reff setelah partner balance berhasil dibuat (seperti di PaymentService)
                // if (isset($billingResult['billing_hdr']) && $billingResult['billing_hdr']) {
                //     $billingHdr = $billingResult['billing_hdr'];
                //     $this->billingService->updAmtReff('+', $billingHdr->amt, $billingHdr->id);
                // }

                $processedCount++;
                Log::info("Successfully migrated delivery {$delivery->tr_code} to billing");
            } catch (\Exception $e) {
                Log::error("Error creating billing for delivery {$delivery->tr_code}: " . $e->getMessage());
                Log::error("Stack trace: " . $e->getTraceAsString());
                $errorCount++;
                continue;
            }
        }

        $message = "Migrasi data Sales Delivery (SD) ke billing dengan reff_code = 'baru' berhasil! Diproses: {$processedCount} records, Error: {$errorCount} records";

        $this->dispatch('show-message', [
            'type' => 'success',
            'message' => $message
        ]);

        session()->flash('migration_success', $message);
    }
}
