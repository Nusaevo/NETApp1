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

    public function addOrder(array $headerData, array $detailData): OrderHdr
    {
        try{
            // Simpan header terlebih dahulu
            $order = $this->saveHeader($headerData);
            // throw new Exception('Gagal menyimpan detail pesanan. Periksa data yang diberikan.');

            // Set ID header ke headerData untuk digunakan di saveDetails
            $headerData['id'] = $order->id;

            // Simpan detail
            $this->saveDetails($order->id, $headerData, $detailData);

            return $order;
        } catch (Exception $e) {
            throw new Exception('Error updating order: ' . $e->getMessage());
        }
    }

    public function updOrder(int $orderId, array $headerData, array $detailData): OrderHdr
    {
        try {
            // Update header
            $order = $this->saveHeader($headerData, $orderId);

            // Set ID header ke headerData untuk digunakan di saveDetails
            $headerData['id'] = $order->id;

            // Hanya update detail jika $detailData tidak kosong
            if (!empty($detailData)) {
                $this->saveDetails($orderId, $headerData, $detailData);
            }

            return $order;
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
    private function saveHeader(array $headerData, ?int $orderId = null): OrderHdr
    {
        if ($orderId) {
            $order = OrderHdr::findOrFail($orderId);

            // Set data baru ke model untuk pengecekan isDirty
            $order->fill($headerData);

            // Update hanya jika ada perubahan data
            if ($order->isDirty()) {
                $order->save();
            }
        } else {
            // Pastikan print_date selalu null saat create order baru
            $headerData['print_date'] = null;
            // throw new Exception('Gagal menyimpan detail pesanan. Periksa data yang diberikan.');
            $order = OrderHdr::create($headerData);
        }
        return $order;
    }

    private function saveDetails(int $orderId, array $headerData, array $detailData): array
    {
        if (!isset($headerData['id']) || empty($headerData['id'])) {
            throw new Exception('Header ID tidak ditemukan. Pastikan header sudah tersimpan.');
        }

        $this->inventoryService->delIvtLog($orderId);

        $updatedDetails = [];
        $existingDetailIds = [];

        foreach ($detailData as $detail) {
            $detail['trhdr_id'] = $headerData['id'];
            $detail['tr_type'] = $headerData['tr_type'];
            $detail['tr_code'] = $headerData['tr_code'];

            // Jika ada ID detail, update existing record tanpa mengubah tr_seq
            if (isset($detail['id']) && !empty($detail['id'])) {
                $existingDetail = OrderDtl::withTrashed()->find($detail['id']);

                if ($existingDetail) {
                    // Hapus ivt_logs untuk detail ini terlebih dahulu
                    $this->inventoryService->delIvtLog(0, $existingDetail->id);

                    // Hapus tr_seq dari array update agar tidak berubah
                    unset($detail['tr_seq']);

                    // Set data baru ke model untuk pengecekan isDirty
                    $existingDetail->fill($detail);

                    // Update hanya jika ada perubahan data
                    if ($existingDetail->isDirty()) {
                        $existingDetail->save();
                    }

                    $updatedDetails[] = $existingDetail;
                    $existingDetailIds[] = $existingDetail->id;

                    // Update last buying price jika PO
                    if (str_starts_with($headerData['tr_code'], 'PO')) {
                        $this->materialService->updLastBuyingPrice(
                            $existingDetail->matl_id,
                            $existingDetail->matl_uom,
                            $existingDetail->price,
                            $headerData['tr_date']
                        );
                    }

                    // Add reservation inventory untuk detail yang diupdate
                    $this->inventoryService->addReservation($headerData, $existingDetail->toArray());
                    continue;
                }
            }

            // Jika tidak ada ID atau record tidak ditemukan, create new record dengan tr_seq baru
            $detail['tr_seq'] = $this->getNextSequence($orderId);
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

            // Add new reservation
            $this->inventoryService->addReservation($headerData, $newDetail->toArray());
        }

        // Hapus detail yang tidak ada dalam array detailData
        $deletedDetails = OrderDtl::where('trhdr_id', $orderId)
            ->whereNotIn('id', $existingDetailIds)
            ->get();

        foreach ($deletedDetails as $deletedDetail) {
            // Hapus ivt_logs untuk detail yang dihapus
            $this->inventoryService->delIvtLog(0, $deletedDetail->id);
            $deletedDetail->delete();
        }

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
