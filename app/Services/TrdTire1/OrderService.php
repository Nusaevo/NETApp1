<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Models\TrdTire1\Transaction\OrderDtl;
use App\Models\TrdTire1\Master\Material;
use App\Models\TrdTire1\Inventories\IvtLog;
use Illuminate\Support\Facades\DB;
use Exception;
use phpDocumentor\Reflection\PseudoTypes\Numeric_;

class OrderService
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function addOrder(array $headerData, array $detailData): OrderHdr
    {
        // Simpan header terlebih dahulu
        $order = $this->saveHeader($headerData);

        // Set ID header ke headerData untuk digunakan di saveDetails
        $headerData['id'] = $order->id;

        // Simpan detail
        $this->saveDetails($headerData, $detailData);

        return $order;
    }

    public function modOrder(int $orderId, array $headerData, array $detailData): OrderHdr
    {
        // Update header
        $order = $this->saveHeader($headerData, $orderId);

        // Set ID header ke headerData untuk digunakan di saveDetails
        $headerData['id'] = $order->id;

        // Delete existing details
        $this->deleteDetails($orderId);

        // Save new details
        $this->saveDetails($headerData, $detailData);

        return $order;
    }

     public function delOrder(int $orderId)
     {
         $this->deleteDetails($orderId);
         $this->deleteHeader($orderId);
    }


    public function updOrderQtyReff(string $mode, float $qtyDeliv, int $orderDtlId)
    {
        // dd($qtyDeliv, $orderDtlId);
        // Update qty_reff di OrderDtl
        $orderDtl = OrderDtl::find($orderDtlId);
        if ($orderDtl) {
            // dd($orderDtl);
            if ($mode === '+') {
                $orderDtl->qty_reff += $qtyDeliv;
            } else if ($mode === '-') {
                $orderDtl->qty_reff -=$qtyDeliv;
            }
            $orderDtl->save();
        }
    }


    private function saveHeader(array $headerData, ?int $orderId = null): OrderHdr
    {
        if ($orderId) {
            $order = OrderHdr::findOrFail($orderId);
            $order->update($headerData);
            return $order;
        }

        return OrderHdr::create($headerData);
    }
    private function saveDetails(array $headerData, array $detailData): array
    {
        // Pastikan header sudah tersimpan dan memiliki ID
        if (!isset($headerData['id']) || empty($headerData['id'])) {
            throw new Exception('Header ID tidak ditemukan. Pastikan header sudah tersimpan.');
        }

        $savedDetails = [];
        foreach ($detailData as $detail) {
            // Set field wajib dari header
            $detail['trhdr_id'] = $headerData['id'];
            $detail['tr_code'] = $headerData['tr_code'];

            // Pastikan disc_pct ada dan dalam format yang benar
            if (isset($detail['disc_pct'])) {
                $detail['disc_pct'] = (float)$detail['disc_pct'];
            } else {
                $detail['disc_pct'] = 0;
            }

            // Simpan detail terlebih dahulu
            $savedDetail = OrderDtl::create($detail);
            $savedDetails[] = $savedDetail;

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
        OrderDtl::where('trhdr_id', $orderID)->forceDelete();
        $this->inventoryService->delIvtLog($orderID);
    }

}
