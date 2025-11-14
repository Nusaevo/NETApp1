<?php

namespace App\Livewire\TrdTire1\Transaction\SalesDelivery;

use Livewire\Attributes\On;
use App\Enums\TrdTire1\Status;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

    public function openDeliveryDateModal($orderIds, $selectedItems, $tanggalKirim = null, $warehouse = null)
    {
        $this->selectedOrderIds = $orderIds;
        $this->selectedItems    = $selectedItems;

        // Set tanggal kirim dan warehouse dari parameter (yang sudah diisi di bawah filter)
        if ($tanggalKirim) {
            $this->inputs['tr_date'] = $tanggalKirim;
        } else {
            // Fallback ke hari ini jika tidak ada
            $this->inputs['tr_date'] = Carbon::now()->format('Y-m-d');
        }

        if ($warehouse) {
            $this->inputs['wh_code'] = $warehouse;
        }

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
                        ->where('matl_uom', $detail->matl_uom)
                        ->where('wh_id', $warehouse->id)
                        ->sum('qty_oh');

                    if ($totalStock < $detail->qty) {
                        $orderStockErrors[] = [
                            'matl_code' => $detail->matl_code,
                            'wh_code' => $warehouse->str1,
                            'stock' => rtrim(rtrim(number_format($totalStock, 3, '.', ''), '0'), '.'),
                            'required' => $detail->qty
                        ];
                        $hasStockError = true;
                    }
                }
            }

            // Jika ada error stok untuk order ini, skip dan catat
            if ($hasStockError) {
                $failedOrders[] = [
                    'tr_code' => $order->tr_code,
                    'stock_errors' => $orderStockErrors,
                    'errors' => [] // Untuk error non-stock
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
                            'stock_errors' => [],
                            'errors' => ['Gagal membuat Billing']
                        ];
                    }
                } else {
                    // Delivery gagal dibuat
                    $failedOrders[] = [
                        'tr_code' => $order->tr_code,
                        'stock_errors' => [],
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
                $message .= $this->formatFailedOrder($failed);
            }
            $type = 'warning';
        } elseif (empty($successOrders) && !empty($failedOrders)) {
            // Semua gagal
            $message = '<strong>Gagal!</strong><br><br>';
            $message .= 'Semua nota gagal diproses:<br><br>';
            foreach ($failedOrders as $failed) {
                $message .= $this->formatFailedOrder($failed);
            }
            $type = 'error';
        }

        $this->dispatch('notify-swal', [
            'type' => $type,
            'message' => $message,
            'width' => '600px'
        ]);
    }

    /**
     * Format failed order dengan tabel untuk stock errors
     */
    private function formatFailedOrder($failed)
    {
        $output = '<div style="margin-bottom: 20px;">';
        $output .= '<div style="font-weight: bold; margin-bottom: 10px; color: #dc3545;">• ' . htmlspecialchars($failed['tr_code']) . ':</div>';

        // Tampilkan stock errors dalam tabel
        if (!empty($failed['stock_errors']) && count($failed['stock_errors']) > 0) {
            $output .= '<div style="margin-top: 8px;">';
            $output .= '<table style="width: 100%; border-collapse: collapse; margin: 0; font-size: 0.8rem; border: 1px solid #dee2e6;">';
            $output .= '<thead>';
            $output .= '<tr style="background-color: #f8f9fa;">';
            $output .= '<th style="padding: 6px 8px; text-align: left; border: 1px solid #dee2e6; font-weight: 600; font-size: 0.8rem;">Barang</th>';
            $output .= '<th style="padding: 6px 8px; text-align: left; border: 1px solid #dee2e6; font-weight: 600; font-size: 0.8rem;">Gudang</th>';
            $output .= '<th style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600; font-size: 0.8rem;">Stok</th>';
            $output .= '<th style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600; font-size: 0.8rem;">Butuh</th>';
            $output .= '</tr>';
            $output .= '</thead>';
            $output .= '<tbody>';

            foreach ($failed['stock_errors'] as $error) {
                $output .= '<tr>';
                $output .= '<td style="padding: 5px 8px; border: 1px solid #dee2e6; font-size: 0.8rem;">' . htmlspecialchars($error['matl_code']) . '</td>';
                $output .= '<td style="padding: 5px 8px; border: 1px solid #dee2e6; font-size: 0.8rem;">' . htmlspecialchars($error['wh_code']) . '</td>';
                $output .= '<td style="padding: 5px 8px; border: 1px solid #dee2e6; text-align: right; font-size: 0.8rem;">' . htmlspecialchars($error['stock']) . '</td>';
                $output .= '<td style="padding: 5px 8px; border: 1px solid #dee2e6; text-align: right; font-size: 0.8rem;">' . htmlspecialchars($error['required']) . '</td>';
                $output .= '</tr>';
            }

            $output .= '</tbody>';
            $output .= '</table>';
            $output .= '</div>';
        }

        // Tampilkan error lainnya (non-stock)
        if (!empty($failed['errors']) && count($failed['errors']) > 0) {
            foreach ($failed['errors'] as $error) {
                $output .= '<div style="margin-top: 8px; color: #dc3545;">' . htmlspecialchars($error) . '</div>';
            }
        }

        $output .= '</div>';
        return $output;
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
