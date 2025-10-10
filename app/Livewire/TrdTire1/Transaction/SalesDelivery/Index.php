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
use App\Models\TrdTire1\Inventories\IvtBal;
use App\Services\TrdTire1\Master\MasterService;
use App\Models\TrdTire1\Transaction\{DelivDtl, DelivHdr, OrderDtl, OrderHdr};

class Index extends BaseComponent
{
    public $selectedOrderIds = [];
    public $deliveryDate = '';
    public $inputs = [
        'tr_date' => '',
        'wh_code' => '',
    ];
    protected $masterService;
    public $warehouses;
    public $selectedItems = [];
    protected $listeners = [
        'openDeliveryDateModal',
    ];

    public function openDeliveryDateModal($orderIds, $selectedItems)
    {
        $this->selectedOrderIds = $orderIds;
        $this->selectedItems    = $selectedItems;
        // Set default tanggal kirim ke hari ini jika belum ada
        // $this->inputs['tr_date'] = Carbon::now()->format('Y-m-d');
        $this->dispatch('open-modal-delivery-date');
    }

    public function onValidateAndSave()
    {
        // Validasi input
        if (empty($this->selectedOrderIds) || count($this->selectedOrderIds) === 0) {
            $this->dispatch('error', 'Silakan pilih minimal satu nota untuk dikirim.');
            return;
        }

        $this->validate([
            'inputs.tr_date' => 'required|date',
            'inputs.wh_code' => 'required',
        ]);

        // Validasi tanggal kirim tidak boleh lebih besar dari tanggal sekarang
        $deliveryDate = Carbon::parse($this->inputs['tr_date']);
        $today = Carbon::now()->startOfDay();

        if ($deliveryDate->gt($today)) {
            throw new Exception('Tanggal kirim tidak boleh lebih besar dari tanggal sekarang.');
        }

        $selectedOrders = OrderHdr::whereIn('id', $this->selectedOrderIds)->get();
        $warehouse = ConfigConst::where('str1', $this->inputs['wh_code'])->first();

        if (!$warehouse) {
            throw new Exception('Warehouse tidak ditemukan.');
        }

        $successCount = 0;
        $stockErrors = [];

        foreach ($selectedOrders as $order) {
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
                // Cek stok tersedia di warehouse (hanya untuk validasi)
                $totalStock = IvtBal::where('matl_id', $detail->matl_id)
                    ->where('wh_id', $warehouse->id)
                    ->where('qty_oh', '>', 0)
                    ->sum('qty_oh');

                if ($totalStock < $detail->qty) {
                    $stockErrors[] = 'Nota: ' . $order->tr_code . ' - Barang: ' . $detail->matl_code . ' - Gudang: ' . $warehouse->str1 . ' - Stok: ' . (int)$totalStock . ' - Dibutuhkan: ' . $detail->qty;
                    continue 2;
                }

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
                    $successCount++;
                } else {
                    throw new Exception('Gagal membuat Delivery untuk order ' . $order->tr_code);
                }

                // Cek hasil billing
                if (!empty($billingResult['billing_hdr'])) {
                    // Billing berhasil dibuat
                } else {
                    throw new Exception('Gagal membuat Billing untuk delivery order ' . $order->tr_code);
                }
            }
        }

        // Tampilkan error stok jika ada
        if (!empty($stockErrors)) {
            $this->dispatch('notify-swal', [
                'type' => 'error',
                'message' => '<strong>Stock Tidak Cukup</strong><br><br>' . implode('<br>', $stockErrors)
            ]);
        }

        // Tampilkan hasil
        if ($successCount > 0) {
            $this->dispatch('success', $successCount . ' Sales Delivery berhasil dibuat');
            $this->dispatch('refresh-page');
        }

        $this->dispatch('close-modal-delivery-date');
        // $this->dispatch('refreshDatatable');
        // $this->dispatch('refresh-page');
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
