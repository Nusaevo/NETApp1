<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Master\MatlUom;
use App\Models\TrdTire1\Transaction\OrderDtl;
use App\Models\TrdTire1\Transaction\OrderHdr;
use Exception;

class OrderService
{
    protected $inventoryService;
    protected $materialService;

    public function __construct(InventoryService $inventoryService, MaterialService $materialService)
    {
        $this->inventoryService = $inventoryService;
        $this->materialService = $materialService;
    }

    #region Save Order
    public function saveOrder(array $headerData, array $detailData)
    {
        // dd($headerData, $detailData);
        try {
            // dd($headerData, $detailData);
            $header = $this->saveHeader($headerData);

            $headerData['id'] = $header->id;

            $details = $this->saveDetails($headerData, $detailData);

            return [
                'header' => $header,
                'details' => $details
            ];
        } catch (Exception $e) {
            throw new Exception('Error updating order: ' . $e->getMessage());
        }
    }

    private function saveHeader(array $headerData): OrderHdr
    {
        if (!isset($headerData['id']) || empty($headerData['id'])) {
            $headerData['print_date'] = null;
            $order = OrderHdr::create($headerData);
        } else {
            $order = OrderHdr::findOrFail($headerData['id']);

            $order->fill($headerData);

            if ($order->isDirty()) {
                $order->save();
            }
        }
        return $order;
    }

    private function saveDetails(array $headerData, array $detailData)
    {

        if (!isset($headerData['id']) || empty($headerData['id'])) {
            throw new Exception('Header ID tidak ditemukan. Pastikan header sudah tersimpan.');
        }
        $newDetail = null;
        $orderDetailIds = [];
        foreach ($detailData as $detail) {
            $detail['trhdr_id'] = $headerData['id'];
            $detail['tr_type'] = $headerData['tr_type'];
            $detail['tr_code'] = $headerData['tr_code'];
            if (!$detail['id']) {
                $detail['tr_seq'] = OrderDtl::getNextTrSeq($headerData['id']);
                $newDetail = OrderDtl::create($detail);
                if (str_starts_with($headerData['tr_code'], 'PO')) {
                    MatlUom::updLastBuyingPrice(
                        $newDetail->matl_id,
                        $newDetail->matl_uom,
                        $newDetail->price,
                        $headerData['tr_date'],
                    );
                }
                $this->inventoryService->addReservation($headerData, $newDetail->toArray());
                $orderDetailIds[] = $newDetail->id;
            } else {
                $existingDetail = OrderDtl::find($detail['id']);
                if ($existingDetail) {
                    $existingDetail->fill($detail);
                    // dd($detail,$existingDetail,$existingDetail->isDirty());
                    // Update hanya jika ada perubahan data
                    if ($existingDetail->isDirty()) {
                        $this->inventoryService->delIvtLog(0, $existingDetail->id);
                        $existingDetail->save();
                        $this->inventoryService->addReservation($headerData, $existingDetail->toArray());
                        if ($headerData['tr_code'] == 'PO') {
                            MatlUom::updLastBuyingPrice(
                                $existingDetail->matl_id,
                                $existingDetail->matl_uom,
                                $existingDetail->price,
                                $headerData['tr_date']
                            );
                        }
                    }
                }
                $orderDetailIds[] = $existingDetail->id;
            }
        }
        // Hapus detail yang tidak ada dalam array detailData
        $deletedDetails = OrderDtl::where('trhdr_id', $headerData['id'])
            ->whereNotIn('id', $orderDetailIds)
            ->get();
        foreach ($deletedDetails as $deletedDetail) {
            // Hapus ivt_logs untuk detail yang dihapus
            $this->inventoryService->delIvtLog(0, $deletedDetail->id);
            $deletedDetail->delete();
        }

    }

    #region Delete Order
    public function delOrder(int $orderId)
    {
        try {
            OrderDtl::where('trhdr_id', $orderId)->delete();
            $this->inventoryService->delIvtLog($orderId);
            OrderHdr::where('id', $orderId)->forceDelete();
        } catch (Exception $e) {
            throw new Exception('Error deleting order: ' . $e->getMessage());
        }
    }

    // Check if the order is editable
    public function isEditable(int $orderId): bool
    {
        $orderHdr = OrderHdr::find($orderId);
        if (!$orderHdr) {
            return false;
        }

        $orderHdr = OrderHdr::where('trhdr_id', $orderId)
            ->first();
        if ($orderHdr->qty_reff > 0) {
            return false;
        }

        // Check if the order is editable based on its status
        return true;
    }

    public function isDeletable(int $orderId): bool
    {
        $order = OrderHdr::find($orderId);
        if (!$order) {
            return false;
        }

        $orderHdr = OrderHdr::where('trhdr_id', $orderId)
            ->first();
        if ($orderHdr->qty_reff > 0) {
            return false;
        }
        // Check if the order is deletable based on its status
        return true;
    }

    public function getOutstandingPO()
    {
        $purchaseOrders = OrderDtl::whereColumn('qty', '>', 'qty_reff')
            ->where('tr_type', 'PO')
            ->distinct()
            ->get(['tr_code', 'trhdr_id']);
        return $purchaseOrders->map(fn($order) => [
            'label' => $order->tr_code,
            'value' => $order->tr_code,
        ])->toArray();
    }
}
