<?php

namespace App\Livewire\TrdTire1\Transaction\PurchaseDelivery;

use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Enums\TrdTire1\Status;
use App\Livewire\Component\BaseComponent;

class PrintPdf extends BaseComponent
{
    public $masa; // Selected masa (month-year)
    public $orders = []; // Orders to be displayed
    public $object;
    public $objectIdValue;

    // Bypass permission check for PrintPdf components
    public $bypassPermissions = true;

    protected function onPreRender()
    {
        if ($this->isEditOrView()) {
            if (empty($this->additionalParam)) {
                $this->dispatch('error', 'Parameter tidak ditemukan.');
                return;
            }

            // Handle new query string format or old JSON structure
            try {
                // Parse additionalParam - format: "?selectedMasa=2025-09&type=cetakLaporanPenjualan" like URL query string
                if (strpos($this->additionalParam, '?') === 0) {
                    // Remove the '?' and parse as query string
                    $queryString = substr($this->additionalParam, 1);
                    parse_str($queryString, $params);

                    if (isset($params['type']) && $params['type'] === 'cetakLaporanPenjualan') {
                        if (isset($params['selectedMasa'])) {
                            $this->masa = $params['selectedMasa'];
                        }
                    } else {
                        $this->dispatch('error', 'Invalid type parameter');
                        return;
                    }
                }
                // Fallback for query string without ?
                else if (strpos($this->additionalParam, '=') !== false && strpos($this->additionalParam, '&') !== false) {
                    // Parse as query string
                    parse_str($this->additionalParam, $params);

                    if (isset($params['type']) && $params['type'] === 'cetakLaporanPenjualan') {
                        if (isset($params['selectedMasa'])) {
                            $this->masa = $params['selectedMasa'];
                        }
                    } else {
                        $this->dispatch('error', 'Invalid type parameter');
                        return;
                    }
                }
                // Fallback for JSON structure
                else {
                    $decodedParam = json_decode($this->additionalParam, true);

                    // Check if it's the new structure with type
                    if (is_array($decodedParam) && isset($decodedParam['type']) && $decodedParam['type'] === 'cetakLaporanPenjualan') {
                        if (isset($decodedParam['selectedMasa'])) {
                            $this->masa = $decodedParam['selectedMasa'];
                        }
                    } else {
                        // Fallback for old structure (simple string masa)
                        $this->masa = $this->additionalParam;
                    }
                }
            } catch (\Exception $e) {
                // If parsing fails, treat as simple string (backward compatibility)
                $this->masa = $this->additionalParam;
            }            if (empty($this->masa)) {
                $this->dispatch('error', 'Masa belum dipilih.');
                return;
            }

            // Always fetch orders based on masa
            $this->orders = OrderHdr::with(['OrderDtl', 'Partner'])
                ->whereRaw("TO_CHAR(tr_date, 'YYYY-MM') = ?", [$this->masa])
                ->where('tr_type', 'SO')
                ->whereNull('deleted_at')
                ->get();
        }
    }

    protected function onLoadForEdit()
    {
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));

        // Pass masa variable to the view
        return view($renderRoute, [
            'masa' => $this->masa
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
