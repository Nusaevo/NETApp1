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
        return DB::transaction(function () use ($headerData, $detailData) {
            $order = $this->saveHeader($headerData);

            $this->saveDetails($order, $detailData);

            return $order;
        });
    }

    public function modOrder(int $orderId, array $headerData, array $detailData): OrderHdr
    {
        return DB::transaction(function () use ($orderId, $headerData, $detailData) {
            // Get existing order
            $order = OrderHdr::findOrFail($orderId);

            // Update header
            $order = $this->saveHeader($headerData, $orderId);

            // Delete existing details
            $this->deleteDetails($order);

            // Save new details
            $this->saveDetails($order, $detailData);

            return $order;
        });
    }

    public function delOrder(int $orderId): bool
    {
        return DB::transaction(function () use ($orderId) {
            $order = OrderHdr::findOrFail($orderId);

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

            $savedDetail = OrderDtl::create($detail);
            $savedDetails[] = $savedDetail;

            // Add inventory reservation
            $detailArray = $savedDetail->toArray();
            $detailArray['tr_amt'] = $detailArray['amt'] ?? 0;
            $detailArray['tr_qty'] = $detailArray['qty'] ?? 0;

            // Siapkan headerData untuk addReservation
            $headerData = [
                'id' => $order->id,
                'tr_type' => $order->tr_type,
                'tr_code' => $order->tr_code,
                'tr_date' => $order->tr_date,
                'tr_desc' => 'RESERVASI ' . $order->tr_type . ' ' . $order->tr_code,
                'process_flag' => $order->process_flag ?? '',
                'reff_id' => $order->reff_id ?? null
            ];

            $this->inventoryService->addReservation($headerData, $detailArray);
        }

        return $savedDetails;
    }

    private function deleteDetails(OrderHdr $order): void
    {
        // Get existing details
        $existingDetails = OrderDtl::where('trhdr_id', $order->id)->get();

        // Delete inventory reservations first
        foreach ($existingDetails as $detail) {
            $ivtLog = IvtLog::where('trdtl_id', $detail->id)->first();
            if ($ivtLog) {
                $this->inventoryService->delReservation($ivtLog->id, $ivtLog->matl_id, $ivtLog->wh_id, $ivtLog->batch_code);
            }
        }

        // Then delete the details
        OrderDtl::where('trhdr_id', $order->id)->forceDelete();
    }

    public function updQtyReff(string $mode, float $qtyDeliv, int $delivDtlId)
    {
        // Update qty_reff di OrderDtl
        $orderDtl = OrderDtl::find($delivDtlId);
        if ($orderDtl) {
            if ($mode === '+') {
                $orderDtl->qty_reff += $qtyDeliv;
            } else if ($mode === '-') {
                $orderDtl->qty_reff -=$qtyDeliv;
            }
            $orderDtl->save();
        }
    }
}
