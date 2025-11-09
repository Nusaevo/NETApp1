<?php

namespace App\Livewire\TrdTire1\Transaction\SalesDelivery;

use Exception;
use Livewire\Attributes\On;
use App\Enums\TrdTire1\Status;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\SysConfig1\ConfigConst;
use App\Livewire\Component\BaseComponent;
use App\Services\TrdTire1\BillingService;
use App\Services\TrdTire1\DeliveryService;
use App\Services\TrdTire1\AuditLogService;
use App\Models\TrdTire1\Inventories\IvtBal;
use App\Services\TrdTire1\Master\MasterService;
use App\Models\TrdTire1\Master\Material;
use App\Models\TrdTire1\Transaction\{DelivDtl, DelivHdr, OrderDtl, OrderHdr};

class Index extends BaseComponent
{
    public $selectedOrderIds = [];
    public $deliveryDate = '';

    protected $masterService;
    public $warehouses;
    public $selectedItems = [];
    protected $listeners = [
        'openDeliveryDateModal',
        'closeDeliveryDateModal',
    ];
    public $inputs = [
        'tr_date' => '',
        'wh_code' => '',
    ];

    public $rules = [
        'inputs.tr_date' => 'required|date',
        'inputs.wh_code' => 'required',
    ];

    public function openDeliveryDateModal($orderIds, $selectedItems)
    {
        $this->selectedOrderIds = $orderIds;
        $this->selectedItems    = $selectedItems;
        // Set default tanggal kirim ke hari ini jika belum ada
        // $this->inputs['tr_date'] = Carbon::now()->format('Y-m-d');
        $this->dispatch('open-modal-delivery-date');
    }

    public function closeDeliveryDateModal()
    {
        // Clear selections when dialog is closed
        $this->selectedOrderIds = [];
        $this->selectedItems = [];

        // Dispatch to IndexDataTable to clear selections
        // $this->dispatch('clearSelections');
    }

    public function processDelivery()
    {
        try {
            // Validasi input
            if (empty($this->selectedOrderIds) || count($this->selectedOrderIds) === 0) {
                $this->dispatch('notify-swal', [
                    'type' => 'error',
                    'message' => 'Silakan pilih minimal satu nota untuk dikirim.'
                ]);
                return;
            }

            // Validasi tanggal kirim tidak boleh lebih besar dari tanggal sekarang
            $deliveryDate = Carbon::parse($this->inputs['tr_date']);
            $today = Carbon::now()->startOfDay();

            if ($deliveryDate->gt($today)) {
                $this->dispatch('notify-swal', [
                    'type' => 'error',
                    'message' => 'Tanggal kirim tidak boleh lebih besar dari tanggal sekarang.'
                ]);
                return;
            }

            $selectedOrders = OrderHdr::whereIn('id', $this->selectedOrderIds)->get();
            $warehouse = ConfigConst::where('str1', $this->inputs['wh_code'])->first();

            if (!$warehouse) {
                $this->dispatch('notify-swal', [
                    'type' => 'error',
                    'message' => 'Warehouse tidak ditemukan.'
                ]);
                return;
            }

            $successCount = 0;
            $successOrders = [];
            $failedOrders = [];
            $stockErrors = [];

            // Proses setiap order secara individual
            foreach ($selectedOrders as $order) {
                $orderDetails = OrderDtl::where('tr_code', $order->tr_code)->get();
                $hasStockError = false;
                $orderStockErrors = [];

                // Validasi stok untuk order ini (skip untuk material JASA)
                foreach ($orderDetails as $detail) {
                    // Skip validasi stok jika material category adalah JASA
                    $material = Material::find($detail->matl_id);
                    $isJasa = $material && strtoupper(trim($material->category ?? '')) === 'JASA';

                    if (!$isJasa) {
                        $totalStock = IvtBal::where('matl_id', $detail->matl_id)
                            ->where('wh_id', $warehouse->id)
                            ->sum('qty_oh');

                        if ($totalStock < $detail->qty) {
                            $stockError = 'Barang: ' . $detail->matl_code . ' - Gudang: ' . $warehouse->str1 . ' - Stok: ' . rtrim(rtrim(number_format($totalStock, 3, '.', ''), '0'), '.') . ' - Dibutuhkan: ' . $detail->qty;
                            $orderStockErrors[] = $stockError;
                            $hasStockError = true;
                        }
                    }
                }

                // Jika ada error stok untuk order ini, skip dan catat
                if ($hasStockError) {
                    $failedOrders[] = [
                        'tr_code' => $order->tr_code,
                        'errors' => $orderStockErrors
                    ];
                    continue;
                }

                // Jika tidak ada error stok, lanjutkan proses delivery untuk order ini
                // Persiapan array inputs
                $inputs = [
                    'tr_type' => 'SD',
                    'tr_code' => $order->tr_code,
                    'tr_date' => $this->inputs['tr_date'],
                    'partner_id' => $order->partner_id,
                    'partner_code' => $order->partner_code,
                    // 'status_code' => $order->status_code,
                    'wh_code' => $warehouse->str1,
                    'wh_id' => $warehouse->id,
                    'payment_term_id' => $order->payment_term_id,
                    'payment_term' => $order->payment_term,
                    'payment_due_days' => $order->payment_due_days,
                    'note' => '',
                    'reff_date' => null,
                    'amt_shipcost' => $order->amt_shipcost ?? 0,
                    'status_code' => Status::OPEN,
                ];

                // Persiapan array input_details
                $input_details = [];
                $orderDetails = OrderDtl::where('tr_code', $order->tr_code)->get();

                foreach ($orderDetails as $detail) {
                    $input_details[] = [
                        'matl_id' => $detail->matl_id,
                        'matl_code' => $detail->matl_code,
                        'matl_descr' => $detail->matl_descr,
                        'matl_uom' => $detail->matl_uom,
                        'qty' => $detail->qty,
                        'wh_id' => $warehouse->id,
                        'wh_code' => $warehouse->str1,
                        'reffdtl_id' => $detail->id,
                        'reffhdr_id' => $order->id,
                        'reffhdrtr_type' => $detail->OrderHdr->tr_type,
                        'reffhdrtr_code' => $order->tr_code,
                        'reffdtltr_seq' => $detail->tr_seq,
                    ];
                }

                // Panggil DeliveryService dengan array inputs dan input_details
                if (!empty($input_details)) {
                    $deliveryService = app(DeliveryService::class);
                    $result = $deliveryService->saveDelivery($inputs, $input_details);

                    // Persiapan data untuk BillingService
                    $billingHeaderData = [
                        'id' => 0,
                        'tr_type' => 'ARB',
                        'tr_code' => $order->tr_code,
                        'tr_date' => $this->inputs['tr_date'],
                    ];

                    // Ambil delivery_id dari hasil saveDelivery
                    $deliveryDetails = [];
                    if (!empty($result['header'])) {
                        $deliveryDetails[] = [
                            'deliv_id' => $result['header']->id,
                        ];
                    }

                    $billingService = app(BillingService::class);
                    $billingResult = $billingService->saveBilling($billingHeaderData, $deliveryDetails);

                    if (!empty($result['header'])) {
                        AuditLogService::createDeliveryKirim([$result['header']->id]);
                        // Audit log for Sales Delivery KIRIM

                        // Cek hasil billing
                        if (!empty($billingResult['billing_hdr'])) {
                            // Billing berhasil dibuat
                            $successOrders[] = $order->tr_code;
                            $successCount++;
                        } else {
                            // Billing gagal dibuat
                            $failedOrders[] = [
                                'tr_code' => $order->tr_code,
                                'errors' => ['Gagal membuat Billing']
                            ];
                        }
                    } else {
                        // Delivery gagal dibuat
                        $failedOrders[] = [
                            'tr_code' => $order->tr_code,
                            'errors' => ['Gagal membuat Delivery']
                        ];
                    }
                }
            }

            // Tampilkan hasil dengan detail
            $this->showProcessResults($successOrders, $failedOrders, $successCount);

            // Clear selections after successful completion
            $this->dispatch('clearSelections');
            $this->dispatch('close-modal-delivery-date');
            $this->dispatch('refreshDatatable');

        } catch (Exception $e) {
            Log::error("Method processDelivery : " . $e->getMessage());
            $this->dispatch('notify-swal', [
                'type' => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Tampilkan hasil proses delivery dengan detail
     */
    private function showProcessResults($successOrders, $failedOrders, $successCount)
    {
        $message = '';
        $type = 'info';

        if ($successCount > 0 && empty($failedOrders)) {
            // Semua berhasil
            $message = '<strong>Berhasil!</strong><br><br>';
            $message .= $successCount . ' Sales Delivery berhasil dibuat:<br>';
            $message .= '• ' . implode('<br>• ', $successOrders);
            $type = 'success';
        } elseif ($successCount > 0 && !empty($failedOrders)) {
            // Sebagian berhasil
            $message = '<strong>Hasil Proses Delivery</strong><br><br>';
            $message .= '<strong>✅ Berhasil (' . $successCount . ' nota):</strong><br>';
            $message .= '• ' . implode('<br>• ', $successOrders) . '<br><br>';

            $message .= '<strong>❌ Gagal (' . count($failedOrders) . ' nota):</strong><br>';
            foreach ($failedOrders as $failed) {
                $message .= '• ' . $failed['tr_code'] . ': ' . implode(', ', $failed['errors']) . '<br>';
            }
            $type = 'warning';
        } elseif (empty($successOrders) && !empty($failedOrders)) {
            // Semua gagal
            $message = '<strong>Gagal!</strong><br><br>';
            $message .= 'Semua nota gagal diproses:<br>';
            foreach ($failedOrders as $failed) {
                $message .= '• ' . $failed['tr_code'] . ': ' . implode(', ', $failed['errors']) . '<br>';
            }
            $type = 'error';
        }

        $this->dispatch('notify-swal', [
            'type' => $type,
            'message' => $message
        ]);
    }

    public function onPrerender()
    {
        $this->masterService = new MasterService();
        $this->warehouses = $this->masterService->getWarehouse();
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
