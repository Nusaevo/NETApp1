<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Models\TrdTire1\Transaction\OrderDtl;
use Illuminate\Database\Eloquent\SoftDeletes;
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

    public function saveOrder(array $headerData, array $detailData)
    {
        try{
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

     public function delOrder(int $orderId)
     {
        try {
             $this->deleteDetails($orderId);
             $this->deleteHeader($orderId);
        } catch (Exception $e) {
             throw new Exception('Error deleting order: ' . $e->getMessage());
        }
    }

    public function updOrderQtyReff(string $mode, float $qtyDeliv, int $orderDtlId)
    {
        try {
            // Update qty_reff di OrderDtl
            $orderDtl = OrderDtl::find($orderDtlId);
            if ($orderDtl) {
                if ($mode === '+') {
                    $orderDtl->qty_reff += $qtyDeliv;
                } else if ($mode === '-') {
                    $orderDtl->qty_reff -= $qtyDeliv;
                }
                $orderDtl->save();
            }
        } catch (Exception $e) {
            throw new Exception('Error updating order quantity reference: ' . $e->getMessage());
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

    private function saveDetails(array $headerData, array $detailData): array
    {
        if (!isset($headerData['id']) || empty($headerData['id'])) {
            throw new Exception('Header ID tidak ditemukan. Pastikan header sudah tersimpan.');
        }

        // $this->inventoryService->delIvtLog($orderId);

        $updatedDetails = [];
        $existingDetailIds = [];

        foreach ($detailData as $detail) {
            $detail['trhdr_id'] = $headerData['id'];
            $detail['tr_type'] = $headerData['tr_type'];
            $detail['tr_code'] = $headerData['tr_code'];

            if (!isset($detail['id']) || empty($detail['id'])) {
                // Jika tidak ada ID atau record tidak ditemukan, create new record dengan tr_seq baru
                $detail['tr_seq'] = $this->getNextSequence($headerData['id']);
                $newDetail = OrderDtl::create($detail);
                $updatedDetails[] = $newDetail;
                $existingDetailIds[] = $newDetail->id;

                // Update last buying price jika PO
                if (str_starts_with($headerData['tr_code'], 'PO')) {
                    $this->materialService->updLastBuyingPrice(
                        $newDetail->matl_id,
                        $newDetail->matl_uom,
                        $newDetail->price,
                        $headerData['tr_date']
                    );
                }

                $this->inventoryService->addReservation($headerData, $newDetail->toArray());

            } else {

                $existingDetail = OrderDtl::withTrashed()->find($detail['id']);

                if ($existingDetail) {

                    // Hapus tr_seq dari array update agar tidak berubah
                    // unset($detail['tr_seq']);

                    // Set data baru ke model untuk pengecekan isDirty
                    $existingDetail->fill($detail);

                    // Update hanya jika ada perubahan data
                    if ($existingDetail->isDirty()) {
                        $this->inventoryService->delIvtLog(0, $existingDetail->id);
                        $existingDetail->save();
                        if (str_starts_with($headerData['tr_code'], 'PO')) {
                            $this->materialService->updLastBuyingPrice(
                                $existingDetail->matl_id,
                                $existingDetail->matl_uom,
                                $existingDetail->price,
                                $headerData['tr_date']
                            );
                        }
                        $this->inventoryService->addReservation($headerData, $existingDetail->toArray());
                    }

                    $updatedDetails[] = $existingDetail;
                    $existingDetailIds[] = $existingDetail->id;
                }
            }
        }
        // dd($headerData, $detailData);


        // Hapus detail yang tidak ada dalam array detailData
        $deletedDetails = OrderDtl::where('trhdr_id', $headerData['id'])
            ->whereNotIn('id', $existingDetailIds)
            ->get();

        foreach ($deletedDetails as $deletedDetail) {
            // Hapus ivt_logs untuk detail yang dihapus
            $this->inventoryService->delIvtLog(0, $deletedDetail->id);
            $deletedDetail->delete();
        }

        // dd($updatedDetails);
        return $updatedDetails;
    }

    private function deleteHeader(int $orderID)
    {
        OrderHdr::where('id', $orderID)->forceDelete();
    }

    private function deleteDetails(int $orderID): void
    {
        // Then delete the details
        OrderDtl::where('trhdr_id', $orderID)->delete();
        $this->inventoryService->delIvtLog($orderID);
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
    private function getNextSequence(int $orderId): int
    {
        // withTrashed() agar termasuk baris soft-deleted
        $max = OrderDtl::withTrashed()
            ->where('trhdr_id', $orderId)
            ->max('tr_seq');

        return ($max ?? 0) + 1;
    }
}
