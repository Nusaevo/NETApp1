<?php

namespace App\Livewire\TrdTire1\Tax\TaxInvoice;

use App\Livewire\Component\BaseComponent;
use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Enums\TrdTire1\Status;

class PrintPdf extends BaseComponent
{
    public $orderIds = [];
    public $printDate;
    public $type;
    public $orders = [];
    
    // Bypass permission check for PrintPdf components
    public $bypassPermissions = true;

    protected function onPreRender()
    {
        if ($this->isEditOrView()) {
            // Handle single order print (when objectId is not empty)
            if (!empty($this->objectIdValue)) {
                $this->type = 'single';
                // Orders will be fetched directly in the loading section below
            }
            // Handle bulk order print (when additionalParam contains order IDs and other data)
            else if (!empty($this->additionalParam)) {
                try {
                    // Parse additionalParam - format: "?selectedPrintDate=2025-09-01&type=cetakProsesDate" like URL query string
                    if (strpos($this->additionalParam, '?') === 0) {
                        // Remove the '?' and parse as query string
                        $queryString = substr($this->additionalParam, 1);
                        parse_str($queryString, $params);

                        if (isset($params['type'])) {
                            $this->type = $params['type'];
                            if ($this->type === 'cetakProsesDate' && isset($params['selectedPrintDate'])) {
                                $this->printDate = $params['selectedPrintDate'];
                            } else if (isset($params['orderIds'])) {
                                $this->orderIds = explode(',', $params['orderIds']);
                                if (isset($params['selectedPrintDate'])) {
                                    $this->printDate = $params['selectedPrintDate'];
                                }
                            }
                        } else {
                            $this->dispatch('error', 'Type parameter not found');
                            return;
                        }
                    }
                    // Fallback for query string without ? prefix
                    else if (strpos($this->additionalParam, '=') !== false && strpos($this->additionalParam, '&') !== false) {
                        // Parse as query string directly
                        parse_str($this->additionalParam, $params);

                        if (isset($params['type'])) {
                            $this->type = $params['type'];

                            // Handle cetakProsesDate
                            if ($this->type === 'cetakProsesDate' && isset($params['selectedPrintDate'])) {
                                $this->printDate = $params['selectedPrintDate'];
                            }
                            // Handle other types with orderIds (if needed in future)
                            else if (isset($params['orderIds'])) {
                                $this->orderIds = explode(',', $params['orderIds']);
                                if (isset($params['selectedPrintDate'])) {
                                    $this->printDate = $params['selectedPrintDate'];
                                }
                            }
                        } else {
                            $this->dispatch('error', 'Type parameter not found');
                            return;
                        }
                    }
                    // Fallback for pipe format: "type|data"
                    else if (strpos($this->additionalParam, '|') !== false) {
                        list($type, $data) = explode('|', $this->additionalParam, 2);
                        $this->type = $type;
                        if ($this->type === 'cetakProsesDate') {
                            $this->printDate = $data;
                        }
                    }
                    // Fallback for old JSON structure
                    else {
                        $decodedParam = json_decode($this->additionalParam, true);
                        if (is_array($decodedParam) && isset($decodedParam['type'])) {
                            $this->type = $decodedParam['type'];
                            if ($this->type === 'cetakProsesDate' && isset($decodedParam['selectedPrintDate'])) {
                                $this->printDate = $decodedParam['selectedPrintDate'];
                            } else if (isset($decodedParam['orderIds'])) {
                                $this->orderIds = $decodedParam['orderIds'];
                                if (isset($decodedParam['selectedPrintDate'])) {
                                    $this->printDate = $decodedParam['selectedPrintDate'];
                                }
                            }
                        } else if (is_array($decodedParam) && !empty($decodedParam)) {
                            $this->orderIds = $decodedParam;
                            $this->type = 'bulk_legacy';
                        } else {
                            $this->dispatch('error', 'Invalid parameter structure');
                            return;
                        }
                    }
                } catch (\Exception $e) {
                    $this->dispatch('error', 'Invalid additional parameters: ' . $e->getMessage());
                    return;
                }
            }            // Validate based on type
            if ($this->type === 'cetakProsesDate') {
                // For cetakProsesDate, validate we have printDate
                if (empty($this->printDate)) {
                    $this->dispatch('error', 'Print date not provided for cetakProsesDate');
                    return;
                }
            } else if ($this->type === 'single') {
                // For single order print, validate we have objectIdValue
                if (empty($this->objectIdValue)) {
                    $this->dispatch('error', 'No order ID provided for single print');
                    return;
                }
            } else {
                // For other types, validate we have orderIds
                if (empty($this->orderIds)) {
                    $this->dispatch('error', 'No order IDs provided');
                    return;
                }
            }

            // Load orders based on type
            if ($this->type === 'cetakProsesDate') {
                // For cetakProsesDate, fetch orders directly by print_date
                $ordersCollection = OrderHdr::with(['OrderDtl', 'Partner'])
                    ->where('print_date', $this->printDate)
                    ->where('tr_type', 'SO')
                    ->whereNull('deleted_at')
                    ->get();
            } else if ($this->type === 'single') {
                // For single order print, fetch order directly by ID
                $ordersCollection = OrderHdr::with(['OrderDtl', 'Partner'])
                    ->where('id', $this->objectIdValue)
                    ->whereNull('deleted_at')
                    ->get();
            } else if (!empty($this->orderIds)) {
                // For other types, fetch orders by orderIds
                $ordersCollection = OrderHdr::with(['OrderDtl', 'Partner'])
                    ->whereIn('id', $this->orderIds)
                    ->whereNull('deleted_at')
                    ->get();
            }

            if (isset($ordersCollection) && $ordersCollection->isEmpty()) {
                $this->dispatch('error', 'No valid orders found');
                return;
            }

            if (isset($ordersCollection)) {
                $this->orders = $ordersCollection;
            }
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
