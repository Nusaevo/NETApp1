<?php

namespace App\Livewire\TrdTire1\Tax\TaxInvoice;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Enums\TrdTire1\Status;
use Livewire\WithPagination;

class PrintPdf extends BaseComponent
{
    use WithPagination;

    public $orderIds = [];
    public $printDate;
    public $type;
    public $perPage = 50;

    // Bypass permission check for PrintPdf components
    public $bypassPermissions = true;

    public function getOrdersProperty()
    {
        try {
            if ($this->type === 'cetakProsesDate') {
                return OrderHdr::with(['OrderDtl', 'Partner'])
                    ->where('print_date', $this->printDate)
                    ->where('tr_type', 'SO')
                    ->whereNull('deleted_at')
                    ->paginate($this->perPage);
            } else if ($this->type === 'single') {
                return OrderHdr::with(['OrderDtl', 'Partner'])
                    ->where('id', $this->objectIdValue)
                    ->whereNull('deleted_at')
                    ->paginate($this->perPage);
            }

            return collect();
        } catch (\Exception $e) {
            $this->dispatch('error', 'Database error: ' . $e->getMessage());
            return collect();
        }
    }

    protected function onPreRender()
    {
        // Parse parameters
        if ($this->isEditOrView()) {
            // Handle single order print (when objectId is not empty)
            if (!empty($this->objectIdValue)) {
                $this->type = 'single';
            }
            // Handle bulk order print (when additionalParam contains order IDs and other data)
            else if (!empty($this->additionalParam)) {
                try {
                    // First decrypt the additionalParam
                    $decryptedParam = decryptWithSessionKey($this->additionalParam);

                    // Parse JSON array format
                    $decodedParam = json_decode($decryptedParam, true);
                    if (is_array($decodedParam) && json_last_error() === JSON_ERROR_NONE) {
                        // Handle JSON array structure
                        if (isset($decodedParam['type'])) {
                            $this->type = $decodedParam['type'];
                            if ($this->type === 'cetakProsesDate' && isset($decodedParam['selectedPrintDate'])) {
                                $this->printDate = $decodedParam['selectedPrintDate'];
                            }
                        }
                    } else {
                        $this->dispatch('error', 'Invalid parameter structure');
                        return;
                    }
                } catch (\Exception $e) {
                    $this->dispatch('error', 'Invalid additional parameters: ' . $e->getMessage());
                    return;
                }
            }

            // Validate based on type
            if ($this->type === 'cetakProsesDate') {
                if (empty($this->printDate)) {
                    $this->dispatch('error', 'Print date not provided for cetakProsesDate');
                    return;
                }
            } else if ($this->type === 'single') {
                if (empty($this->objectIdValue)) {
                    $this->dispatch('error', 'No order ID provided for single print');
                    return;
                }
            }
        }
        $this->getOrdersProperty();
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute, [
            'orders' => $this->orders
        ]);
    }
}
