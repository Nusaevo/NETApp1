<?php

namespace App\Livewire\TrdTire1\Transaction\SalesDelivery;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdTire1\Transaction\{DelivDtl, DelivHdr, OrderDtl, OrderHdr};
use App\Models\TrdTire1\Inventories\IvtBal;
use App\Services\TrdTire1\BillingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Services\TrdTire1\Master\MasterService;
use App\Services\TrdTire1\DeliveryService;
use Livewire\Attributes\On;
use Exception;
use Illuminate\Support\Facades\Log;

class Index extends BaseComponent
{
    public $selectedOrderIds = [];
    public $deliveryDate = '';
    public $inputs = [
        'tr_date' => '',
        'wh_code' => '',
        'amt_shipcost' => null,
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
        $this->inputs['tr_date'] = Carbon::now()->format('Y-m-d');
        $this->dispatch('open-modal-delivery-date');
    }

    public function onValidateAndSave()
    {
        if (empty($this->selectedOrderIds) || count($this->selectedOrderIds) === 0) {
            $this->dispatch('error', 'Silakan pilih minimal satu nota untuk dikirim.');
            return;
        }

        $this->validate([
            'inputs.tr_date' => 'required|date',
            'inputs.wh_code' => 'required',
            'inputs.amt_shipcost' => 'nullable|numeric|min:0',
        ]);

        try {
            $selectedOrders = OrderHdr::whereIn('id', $this->selectedOrderIds)->get();
            $warehouse = ConfigConst::where('str1', $this->inputs['wh_code'])->first();

            $successCount = 0;
            $errorMessages = [];

            foreach ($selectedOrders as $order) {
                // Prepare header data
                $headerData = [
                    'tr_type' => 'SD',
                    'tr_code' => $order->tr_code,
                    'tr_date' => $this->inputs['tr_date'],
                    'partner_id' => $order->partner_id,
                    'partner_code' => $order->partner_code,
                    'status_code' => $order->status_code,
                    'wh_code' => $warehouse ? $warehouse->str1 : null,
                    'wh_id' => $warehouse ? $warehouse->id : null,
                    'payment_term_id' => $order->payment_term_id ?? 0,
                    'payment_term' => $order->payment_term ?? null,
                    'payment_due_days' => $order->payment_due_days ?? 0,
                    'note' => '',
                    'reff_date' => null,
                    'amt_shipcost' => $this->inputs['amt_shipcost'],
                ];

                // Prepare detail data
                $detailData = [];
                $trSeq = 0;
                $orderDetails = OrderDtl::where('tr_code', $order->tr_code)->get();
                foreach ($orderDetails as $detail) {
                    $availableBatches = $warehouse ? IvtBal::where('matl_id', $detail->matl_id)
                        ->where('wh_id', $warehouse->id)
                        ->orderBy('batch_code')
                        ->get() : collect();

                    if ($availableBatches->isEmpty()) {
                        $errorMessages[] = __('Tidak ada stok tersedia untuk material: ' . $detail->matl_code . ' pada order ' . $order->tr_code);
                        continue 2; // skip ke order berikutnya
                    } else {
                        $qtyOrder =  $detail->qty;
                        foreach ($availableBatches as $ivtBalBatch) {
                            $qtyShip = 0;
                            if  ($ivtBalBatch->qty_oh >= $qtyOrder) {
                                $qtyShip = $qtyOrder;
                                $qtyOrder = 0;
                            } else {
                                $qtyShip = $ivtBalBatch->qty_oh;
                                $qtyOrder -= $ivtBalBatch->qty_oh;
                            }
                            if ($qtyShip > 0) {
                                $detailData[] = [
                                    'tr_seq' => $trSeq += 1,
                                    'matl_id' => $detail->matl_id,
                                    'matl_code' => $detail->matl_code,
                                    'matl_descr' => $detail->matl_descr,
                                    'matl_uom' => $detail->matl_uom,
                                    'qty' => $qtyShip,
                                    'wh_id' => $warehouse ? $warehouse->id : null,
                                    'wh_code' => $warehouse ? $warehouse->str1 : null,
                                    'reffdtl_id' => $detail->id,
                                    'reffhdr_id' => $order->id,
                                    'reffhdrtr_type' => $detail->OrderHdr->tr_type,
                                    'reffhdrtr_code' => $order->tr_code,
                                    'reffdtltr_seq' => $detail->tr_seq,
                                    'ivt_id' => $ivtBalBatch->id,
                                    'batch_code' => $ivtBalBatch->batch_code,
                                ];
                            }
                            if ($qtyOrder == 0) {
                                break;
                            }
                        }
                        if ($qtyOrder > 0) {
                            $errorMessages[] = __('Stok tidak mencukupi untuk material: ' . $detail->matl_code . ' pada order ' . $order->tr_code);
                            continue 2; // skip ke order berikutnya
                        }
                    }
                }

                // Create delivery using service
                $deliveryService = app(DeliveryService::class);
                $result = $deliveryService->addDelivery($headerData, $detailData);
                if (empty($result['header'])) {
                    $errorMessages[] = 'Gagal membuat Delivery untuk order ' . $order->tr_code . ': Data header tidak valid atau ada constraint DB.';
                    continue;
                }

                // Update headerData dengan id dari SD yang baru
                $headerData['id'] = $result['header']->id;

                // Hitung total_amt dari detailData (price dari OrderDtl dikurangi disc_pct, dikali qty dari delivdtl)
                $total_amt = 0;
                foreach ($detailData as $detail) {
                    if (isset($detail['reffdtl_id']) && isset($detail['qty'])) {
                        $orderDtl = OrderDtl::find($detail['reffdtl_id']);
                        if ($orderDtl) {
                            $price = $orderDtl->price;
                            $disc_pct = $orderDtl->disc_pct ?? 0;
                            $qty = $detail['qty'];
                            $price_after_disc = $price - ($price * $disc_pct / 100);
                            $total_amt += $price_after_disc * $qty;
                        }
                    }
                }
                $headerData['total_amt'] = $total_amt;

                // Update juga trhdr_id pada setiap detail
                foreach ($detailData as &$detail) {
                    $detail['trhdr_id'] = $result['header']->id;
                }
                unset($detail);
                $successCount++;
            }

            // Setelah loop, tampilkan hasil
            if ($successCount > 0) {
                $this->dispatch('success', $successCount . ' Sales Delivery berhasil dibuat');
            }
            if (!empty($errorMessages)) {
                $this->dispatch('error', implode(', ', $errorMessages));
            }
            $this->dispatch('close-modal-delivery-date');
            $this->dispatch('refreshDatatable');
            $this->dispatch('refresh-page');

        } catch (Exception $e) {
            $this->dispatch('error', 'Gagal membuat Sales Delivery: ' . $e->getMessage());
            $this->dispatch('refresh-page');
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
