<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseDelivery;

use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Models\Util\GenericExcelExport;
use App\Enums\TrdTire1\Status;
use App\Livewire\Component\BaseComponent;
use Livewire\WithPagination;

class PrintPdf extends BaseComponent
{
    use WithPagination;

    public $masa; // Selected masa (month-year)
    public $object;
    public $objectIdValue;
    public $perPage = 50;

    // Bypass permission check for PrintPdf components
    public $bypassPermissions = true;

    public function getOrdersProperty()
    {
        try {
            if (empty($this->masa)) {
                return collect();
            }

            return OrderHdr::with(['OrderDtl', 'Partner'])
                ->whereRaw("TO_CHAR(tr_date, 'YYYY-MM') = ?", [$this->masa])
                ->where('tr_type', 'SO')
                ->whereNull('deleted_at')
                ->paginate($this->perPage);
        } catch (\Exception $e) {
            $this->dispatch('error', 'Database error: ' . $e->getMessage());
            return collect();
        }
    }


    protected function onPreRender()
    {
         if ($this->isEditOrView()) {
            if (empty($this->additionalParam)) {
                $this->dispatch('error', 'Parameter tidak ditemukan.');
                return;
            }

            // Handle new query string format or old JSON structure
            try {
                // First decrypt the additionalParam
                $decryptedParam = decryptWithSessionKey($this->additionalParam);

                // Try JSON decode first (new array format)
                $decodedParam = json_decode($decryptedParam, true);
                if (is_array($decodedParam) && json_last_error() === JSON_ERROR_NONE) {
                    // Handle JSON array structure
                    if (isset($decodedParam['type']) && $decodedParam['type'] === 'cetakLaporanPenjualan') {
                        if (isset($decodedParam['selectedMasa'])) {
                            $this->masa = $decodedParam['selectedMasa'];
                        }
                    } else {
                        // Fallback for old structure (simple array)
                        if (!empty($decodedParam) && is_string($decodedParam)) {
                            $this->masa = $decodedParam;
                        }
                    }
                }
            } catch (\Exception $e) {
                // If parsing fails, treat as simple string (backward compatibility)
                try {
                    $this->masa = decryptWithSessionKey($this->additionalParam);
                } catch (\Exception $e2) {
                    $this->masa = $this->additionalParam;
                }
            }

            if (empty($this->masa)) {
                $this->dispatch('error', 'Masa belum dipilih.');
                return;
            }
        }
        $this->getOrdersProperty();
    }

    protected function onLoadForEdit()
    {
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));

        // Pass masa variable to the view
        return view($renderRoute, [
            'masa' => $this->masa,
            'orders' => $this->orders
        ]);
    }

    protected function onPopulateDropdowns()
    {

    }

    protected function onReset()
    {
    }

    public function onValidateAndSave()
    {
    }

}
