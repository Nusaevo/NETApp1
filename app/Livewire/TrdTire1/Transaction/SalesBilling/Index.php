<?php

namespace App\Livewire\TrdTire1\Transaction\SalesBilling;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigConst;
use App\Models\TrdTire1\Transaction\{DelivDtl, DelivHdr, OrderDtl, OrderHdr, BillingHdr};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\TrdTire1\Master\MasterService;
use App\Services\TrdTire1\AuditLogService;
use Livewire\Attributes\On;
use App\Enums\TrdTire1\Status;

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
        ]);

        DB::beginTransaction();

        try {
            $selectedOrders = BillingHdr::whereIn('id', $this->selectedOrderIds)->get();

            // Get old print dates before update
            $oldPrintDates = $selectedOrders->pluck('print_date', 'id')->toArray();

            foreach ($selectedOrders as $order) {
                $order->update([
                    'print_date' => $this->tr_date,
                    'status_code' => Status::PAID, // gunakan constant status
                ]);
            }

            // Create audit logs for each billing
            try {
                AuditLogService::createPrintDateAuditLogs(
                    $this->selectedOrderIds,
                    $this->tr_date,
                    $oldPrintDates[$this->selectedOrderIds[0]] ?? null
                );
            } catch (\Exception $e) {
                Log::error('Failed to create audit logs: ' . $e->getMessage());
            }

            DB::commit();

            $this->dispatch('close-modal-delivery-date');
            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => 'Tanggal penagihan berhasil disimpan'
            ]);

            $this->dispatch('refreshDatatable');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Failed to update delivery date: ' . $e->getMessage());
            $this->dispatch('showAlert', [
                'type' => 'error',
                'message' => 'Gagal menyimpan tanggal penagihan: ' . $e->getMessage()
            ]);
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
