<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Models\TrdTire1\Transaction\OrderDtl;
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
            $this->saveDetails($headerData, $detailData);

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
                $this->deleteDetails($orderId);
                $this->saveDetails($headerData, $detailData);
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
        // dd($headerData, $orderId);
        if ($orderId) {
            $order = OrderHdr::findOrFail($orderId);
            $order->update($headerData);
        } else {
            // throw new Exception('Gagal menyimpan detail pesanan. Periksa data yang diberikan.');
            $order = OrderHdr::create($headerData);
        }
        // throw new Exception('Gagal menyimpan detail pesanan. Periksa data yang diberikan.');
        return $order;
    }
    private function saveDetails(array $headerData, array $detailData): array
    {
        //throw new Exception('Gagal menyimpan detail pesanan. Periksa data yang diberikan.');
        if (!isset($headerData['id']) || empty($headerData['id'])) {
            throw new Exception('Header ID tidak ditemukan. Pastikan header sudah tersimpan.');
        }

        $savedDetails = [];
        foreach ($detailData as $detail) {
            $detail['trhdr_id'] = $headerData['id'];
            $detail['tr_code'] = $headerData['tr_code'];

            $savedDetail = OrderDtl::create($detail);
            $savedDetails[] = $savedDetail;

                        // if PO (check if tr_code starts with 'PO')
            if (str_starts_with($headerData['tr_code'], 'PO')) {
                $this->materialService->updLastBuyingPrice(
                    $savedDetail->matl_id,
                    $savedDetail->matl_uom,
                    $savedDetail->price,
                    $headerData['tr_date']
                );
            }
            // Kirim detail yang baru disimpan ke addReservation
            $this->inventoryService->addReservation('+', $headerData, $savedDetail->toArray());
        }

        return $savedDetails;
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

}
