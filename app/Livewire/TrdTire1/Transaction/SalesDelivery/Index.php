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
                    'wh_code' => $warehouse ? $warehouse->str1 : null,
                    'wh_id' => $warehouse ? $warehouse->id : null,
                    'payment_term_id' => $order->payment_term_id ?? 0,
                    'payment_term' => $order->payment_term ?? null,
                    'payment_due_days' => $order->payment_due_days ?? 0,
                    'note' => '',
                    'reff_date' => $this->tr_date,
                ];
                // dd($headerData);

                // Prepare detail data
                $detailData = [];
                $trSeq = 0;
                $orderDetails = OrderDtl::where('tr_code', $order->tr_code)->get();
                foreach ($orderDetails as $detail) {

                    // Get available stock from IvtBal for the material and warehouse, ordered by batch_code
                    $availableBatches = $warehouse ? IvtBal::where('matl_id', $detail->matl_id)
                        ->where('wh_id', $warehouse->id)
                        ->orderBy('batch_code')
                        ->get() : collect();

                    if ($availableBatches->isEmpty()) {
                        throw new Exception(__('Tidak ada stok tersedia untuk material: ' . $detail->matl_code));
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
                                    'reffhdrtr_id' => $order->id,
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
                            // $this->dispatch('error', 'Stok tidak mencukupi untuk material: ' . $detail->matl_code);
                            throw new Exception(__('Stok tidak mencukupi untuk material: ' . $detail->matl_code));

                        }
                    }
                }

                // VALIDASI DATA sebelum kirim ke service
                // if (!$warehouse) {
                //     $this->dispatch('error', 'Warehouse tidak ditemukan!');
                //     DB::rollBack();
                //     return;
                // }
                // if (empty($detailData)) {
                //     $this->dispatch('error', 'Tidak ada detail barang yang valid untuk dikirim!');
                //     DB::rollBack();
                //     return;
                // }
                // if (empty($headerData['partner_id']) || empty($headerData['tr_code'])) {
                //     $this->dispatch('error', 'Data header tidak lengkap!');
                //     DB::rollBack();
                //     return;
                // }

            }
            // Create delivery using service
            $deliveryService = app(DeliveryService::class);
            $result = $deliveryService->addDelivery($headerData, $detailData);
            if (empty($result['header'])) {
                $this->dispatch('error', 'Gagal membuat Delivery: Data header tidak valid atau ada constraint DB.');
                DB::rollBack();
                return;
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

            // Tambahkan pembuatan BillingHdr
            // app(BillingService::class)->addBilling($headerData, $detailData);

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
