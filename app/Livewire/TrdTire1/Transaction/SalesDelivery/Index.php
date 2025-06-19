<?php

namespace App\Livewire\TrdTire1\Transaction\SalesDelivery;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdTire1\Transaction\{DelivDtl, DelivHdr, OrderDtl, OrderHdr};
use App\Models\TrdTire1\Inventories\IvtBal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Services\TrdTire1\Master\MasterService;
use App\Services\TrdTire1\DeliveryService;
use Livewire\Attributes\On;
use Exception;

class Index extends BaseComponent
{
    public $selectedOrderIds = [];
    public $deliveryDate = '';
    protected $masterService;
    public $warehouses;
    public $selectedItems = [];
    public $tr_date = ''; // Add this line

    protected $listeners = [
        'openDeliveryDateModal',
    ];

    public function openDeliveryDateModal($orderIds, $selectedItems)
    {
        $this->selectedOrderIds = $orderIds;
        $this->selectedItems    = $selectedItems;
        // ubah dari '' menjadi tanggal hari ini:
        $this->tr_date          = Carbon::now()->format('Y-m-d');
        $this->dispatch('open-modal-delivery-date');
    }

    public function submitDeliveryDate()
    {
        $this->validate([
            'tr_date' => 'required|date',
            'inputs.wh_code' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $selectedOrders = OrderHdr::whereIn('id', $this->selectedOrderIds)->get();
            $warehouse = ConfigConst::where('str1', $this->inputs['wh_code'])->first();

            foreach ($selectedOrders as $order) {
                // Prepare header data
                $headerData = [
                    'tr_type' => 'SD',
                    'tr_code' => $order->tr_code,
                    'tr_date' => $this->tr_date,
                    'partner_id' => $order->partner_id,
                    'partner_code' => $order->partner_code,
                    'status_code' => $order->status_code,
                    'wh_code' => $warehouse->str1,
                    'wh_id' => $warehouse->id,
                    'payment_term_id' => $order->payment_term_id ?? 0,
                    'payment_term' => $order->payment_term ?? null,
                    'payment_due_days' => $order->payment_due_days ?? 0,
                ];

                // Prepare detail data
                $detailData = [];
                $orderDetails = OrderDtl::where('tr_code', $order->tr_code)->get();
                foreach ($orderDetails as $detail) {
                    $qtyToDeliver = $detail->qty; // Quantity needed from the order detail

                    // Get available stock from IvtBal for the material and warehouse, ordered by batch_code
                    $availableBatches = IvtBal::where('matl_id', $detail->matl_id)
                        ->where('wh_id', $warehouse->id)
                        ->where('qty_oh', '>', 0) // Only consider batches with available on-hand quantity
                        ->orderBy('batch_code')
                        ->get();

                    foreach ($availableBatches as $ivtBalBatch) {
                        if ($qtyToDeliver <= 0) {
                            break; // All quantity for this order detail has been allocated
                        }

                        $qtyFromThisBatch = min($qtyToDeliver, $ivtBalBatch->qty_oh);

                        if ($qtyFromThisBatch > 0) {
                            $detailData[] = [
                                'tr_seq' => $detail->tr_seq,
                                'matl_id' => $detail->matl_id,
                                'matl_code' => $detail->matl_code,
                                'matl_descr' => $detail->matl_descr,
                                'matl_uom' => $detail->matl_uom,
                                'qty' => $qtyFromThisBatch, // Allocated quantity for this batch
                                'wh_id' => $warehouse->id,
                                'wh_code' => $warehouse->str1,
                                'reffdtl_id' => $detail->id,
                                'reffhdrtr_type' => $detail->OrderHdr->tr_type,
                                'reffhdrtr_code' => $order->tr_code,
                                'reffdtltr_seq' => $detail->tr_seq,
                                'batch_code' => $ivtBalBatch->batch_code // Specific batch code
                            ];
                            $qtyToDeliver -= $qtyFromThisBatch;
                        }
                    }

                    // // Optional: Handle if qtyToDeliver > 0 after checking all batches (insufficient stock)
                    // if ($qtyToDeliver > 0) {
                    //     // Anda bisa melempar exception atau memberikan pesan error di sini
                    //     throw new Exception('Stok tidak mencukupi untuk item ' . $detail->matl_code . ' sebanyak ' . $qtyToDeliver . ' PCS.');
                    // }
                }

                // Create delivery using service
                $deliveryService = app(DeliveryService::class);
                $result = $deliveryService->addDelivery($headerData, $detailData);
            }

            DB::commit();
            $this->dispatch('close-modal-delivery-date');
            $this->dispatch('success', 'Sales Delivery berhasil dibuat');
            $this->dispatch('refreshDatatable');

        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatch('error', 'Gagal membuat Sales Delivery: ' . $e->getMessage());
        }
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
