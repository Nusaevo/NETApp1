<?php

namespace App\Livewire\TrdTire1\Transaction\SalesDelivery;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdTire1\Transaction\{DelivDtl, DelivHdr, OrderDtl, OrderHdr};
use Illuminate\Support\Facades\DB;
use App\Services\TrdTire1\Master\MasterService;
use Livewire\Attributes\On;

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
        $this->selectedItems = $selectedItems;
        $this->deliveryDate = '';
        $this->dispatch('open-modal-delivery-date');
    }

    public function submitDeliveryDate()
    {
        $this->validate([
            'tr_date' => 'required|date',
            'inputs.wh_code' => 'required',
        ]);

        DB::beginTransaction();

        $selectedOrders = OrderHdr::whereIn('id', $this->selectedOrderIds)->get();

        $warehouse = ConfigConst::where('str1', $this->inputs['wh_code'])->first();

        foreach ($selectedOrders as $order) {
            $delivHdr = DelivHdr::updateOrCreate(
                [
                    'tr_type' => 'SD',
                    'tr_code' => $order->tr_code,
                ],
                [
                    'tr_date' => $this->tr_date,
                    'partner_id' => $order->partner_id,
                    'partner_code' => $order->partner_code,
                    'status_code' => $order->status_code,
                    'wh_code' => $warehouse->str1,
                    'wh_id' => $warehouse->id,
                ]
            );

            // Create DelivDtl records
            $orderDetails = OrderDtl::where('tr_code', $order->tr_code)->get();
            foreach ($orderDetails as $detail) {
                DelivDtl::updateOrCreate(
                    [
                        'trhdr_id' => $delivHdr->id,
                        'tr_seq' => $detail->tr_seq,
                    ],
                    [
                        'tr_code' => $delivHdr->tr_code,
                        'trhdr_id' => $delivHdr->id,
                        'qty' => $detail->qty,
                        'tr_type' => $delivHdr->tr_type,
                        'matl_id' => $detail->matl_id,
                        'matl_code' => $detail->matl_code,
                        'matl_descr' => $detail->matl_descr,
                        'matl_uom' => $detail->matl_uom,
                        'reffdtl_id' => $detail->id,
                        'reffhdrtr_type' => $detail->OrderHdr->tr_type,
                        'reffhdrtr_code' => $order->tr_code,
                        'reffdtltr_seq' => $detail->tr_seq,
                        'wh_code' => $warehouse->str1,
                        'wh_id' => $warehouse->id,
                    ]
                );
            }
        }

        DB::commit();

        $this->dispatch('close-modal-delivery-date');
        $this->dispatch('showAlert', [
            'type' => 'success',
            'message' => 'Tanggal pengiriman berhasil disimpan'
        ]);

        $this->dispatch('refreshDatatable');
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
