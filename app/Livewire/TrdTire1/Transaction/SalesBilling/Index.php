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
        'bulkActionExecuted', // Listener untuk bulk action
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
     * Handle bulk action executed event
     */
    public function bulkActionExecuted($action, $selectedIds)
    {
        // This listener can be used for additional processing after bulk actions
        // The actual bulk action processing is handled in IndexDataTable.php
        Log::info("Bulk action '{$action}' executed for IDs: " . implode(', ', $selectedIds));
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
