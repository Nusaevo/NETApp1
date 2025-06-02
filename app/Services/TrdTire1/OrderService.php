<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Models\TrdTire1\Transaction\OrderDtl;
use App\Models\TrdTire1\Master\Material;
use App\Models\TrdTire1\Inventories\IvtLog;
use Illuminate\Support\Facades\DB;
use Exception;

class OrderService
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function addOrder(array $headerData, array $detailData): OrderHdr
    {
        return DB::transaction(function () use ($headerData, $detailData) {
            // Save header and get OrderHdr instance
            $order = $this->saveHeader($headerData);

            // Save all details
            $savedDetails = $this->saveDetails($order, $detailData);

            // Create inventory reservation
            foreach ($savedDetails as $detail) {
                $detailArray = $detail->toArray();
                $detailArray['tr_amt'] = $detailArray['amt'] ?? 0;
                $detailArray['tr_qty'] = $detailArray['qty'] ?? 0;

                $this->inventoryService->addReservation([
                    'id' => $order->id,
                    'tr_type' => $order->tr_type,
                    'tr_code' => $order->tr_code,
                    'wh_id' => $order->wh_id,
                    'wh_code' => $order->wh_code,
                    'tr_date' => $order->tr_date,
                    'tr_desc' => $order->tr_desc,
                    'process_flag' => $order->process_flag
                ], $detailArray);
            }

            return $order;
        });
    }

    public function modOrder(int $orderId, array $headerData, array $detailData): OrderHdr
    {
        return DB::transaction(function () use ($orderId, $headerData, $detailData) {
            // Get existing order
            $order = OrderHdr::findOrFail($orderId);

            // Cancel existing inventory reservations
            $existingDetails = OrderDtl::where('trhdr_id', $orderId)->get();
            foreach ($existingDetails as $detail) {
                $ivtLog = IvtLog::where('trdtl_id', $detail->id)->first();
                if ($ivtLog) {
                    $this->inventoryService->delReservation($ivtLog->id);
                }
            }

            // Update header
            $order = $this->saveHeader($headerData, $orderId);

            // Update detail
            $savedDetails = $this->saveDetails($order, $detailData);

            // Create new inventory reservations
            foreach ($savedDetails as $detail) {
                $detailArray = $detail->toArray();
                $detailArray['tr_amt'] = $detailArray['amt'] ?? 0;
                $detailArray['tr_qty'] = $detailArray['qty'] ?? 0;

                $this->inventoryService->addReservation([
                    'id' => $order->id,
                    'tr_type' => $order->tr_type,
                    'tr_code' => $order->tr_code,
                    'wh_id' => $order->wh_id,
                    'wh_code' => $order->wh_code,
                    'tr_date' => $order->tr_date,
                    'tr_desc' => $order->tr_desc,
                    'process_flag' => $order->process_flag
                ], $detailArray);
            }

            return $order;
        });
    }

    public function delOrder(int $orderId): bool
    {
        return DB::transaction(function () use ($orderId) {
            $order = OrderHdr::findOrFail($orderId);

            // Cancel inventory reservations
            $existingDetails = OrderDtl::where('trhdr_id', $orderId)->get();
            foreach ($existingDetails as $detail) {
                $ivtLog = IvtLog::where('trdtl_id', $detail->id)->first();
                if ($ivtLog) {
                    $this->inventoryService->delReservation($ivtLog->id);
                }
            }

            $this->deleteDetails($order);
            return $this->deleteHeader($orderId);
        });
    }

    private function saveHeader(array $data, int $orderId = null): OrderHdr
    {
        if ($orderId) {
            $order = OrderHdr::findOrFail($orderId);
            $order->update($data);
        } else {
            $order = OrderHdr::create($data);
        }

        return $order;
    }

    private function deleteHeader(int $orderId): bool
    {
        $order = OrderHdr::findOrFail($orderId);
        return (bool) $order->delete();
    }

    private function saveDetails(OrderHdr $order, array $details): array
    {
        // Hapus semua detail yang ada terlebih dahulu
        $this->deleteDetails($order);

        $savedDetails = [];
        foreach ($details as $detail) {
            // Set field wajib dari header
            $detail['trhdr_id'] = $order->id;
            $detail['tr_code'] = $order->tr_code;

            // Pastikan disc_pct ada dan dalam format yang benar
            if (isset($detail['disc_pct'])) {
                $detail['disc_pct'] = (float)$detail['disc_pct'];
            } else {
                $detail['disc_pct'] = 0;
            }

            $savedDetails[] = OrderDtl::create($detail);
        }

        return $savedDetails;
    }

    private function deleteDetails(OrderHdr $order): void
    {
        // Hapus semua detail yang terkait dengan order ini
        OrderDtl::where('trhdr_id', $order->id)->forceDelete();
    }
}
