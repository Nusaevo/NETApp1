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
    public $tanggalTagih; // Field untuk tanggal tagih
    protected $masterService;
    public $warehouses;

    protected $listeners = [
        'autoUpdateTanggalTagih', // Listener untuk auto update
    ];

    public function onPreRender()
    {
        $this->masterService = new MasterService();
        $this->warehouses = $this->masterService->getWarehouse();

        // Set tanggal tagih default ke hari ini jika belum ada
        if (empty($this->tanggalTagih)) {
            $this->tanggalTagih = now()->format('Y-m-d');
        }
    }

    /**
     * Auto update tanggal tagih ketika row dipilih
     */
    public function autoUpdateTanggalTagih($selectedIds)
    {
        if (empty($this->tanggalTagih)) {
            $this->dispatch('showAlert', [
                'type' => 'warning',
                'message' => 'Silakan pilih tanggal tagih terlebih dahulu.'
            ]);
            return;
        }

        if (count($selectedIds) > 0) {
            DB::beginTransaction();

            try {
                $selectedOrders = BillingHdr::whereIn('id', $selectedIds)->get();

                // Get old print dates before update untuk audit log
                $oldPrintDates = $selectedOrders->pluck('print_date', 'id')->toArray();

                // Update tanggal tagih
                foreach ($selectedOrders as $order) {
                    $order->update([
                        'print_date' => $this->tanggalTagih,
                        'status_code' => Status::PAID,
                    ]);
                }

                // Create audit logs
                try {
                    AuditLogService::createPrintDateAuditLogs(
                        $selectedIds,
                        $this->tanggalTagih,
                        $oldPrintDates[$selectedIds[0]] ?? null
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to create audit logs: ' . $e->getMessage());
                }

                DB::commit();

                $this->dispatch('showAlert', [
                    'type' => 'success',
                    'message' => 'Tanggal tagih berhasil diupdate untuk ' . count($selectedIds) . ' data.'
                ]);

                // REMOVED: Don't refresh datatable to keep selection intact
                // $this->dispatch('refreshDatatable');

            } catch (\Exception $e) {
                DB::rollback();
                Log::error('Failed to auto update tanggal tagih: ' . $e->getMessage());
                $this->dispatch('showAlert', [
                    'type' => 'error',
                    'message' => 'Gagal mengupdate tanggal tagih: ' . $e->getMessage()
                ]);
            }
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
