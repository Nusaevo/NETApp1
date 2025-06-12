<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Inventories\{IvtLog, IvtBal};
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivDtl, OrderDtl};
use Illuminate\Support\Facades\DB;
use Exception;

class DeliveryService
{
    protected $inventoryService;
    protected $orderService;

    public function __construct(InventoryService $inventoryService, OrderService $orderService)
    {
        $this->inventoryService = $inventoryService;
        $this->orderService = $orderService;
    }

    #region Delivery Methods
    public function addDelivery(array $headerData, array $detailData): array
    {
        return DB::transaction(function () use ($headerData, $detailData) {
            // Simpan header
            $delivHdr = $this->saveHeader($headerData);

            // Set header ID ke detail data
            foreach ($detailData as &$detail) {
                $detail['trhdr_id'] = $delivHdr->id;
                $detail['tr_code'] = $delivHdr->tr_code;
                $detail['tr_type'] = $delivHdr->tr_type;
            }
            unset($detail);

            $this->saveDetail($headerData, $detailData);

            return [
                'header' => $delivHdr
            ];
        });
    }

    public function modDelivery(int $delivId, array $headerData, array $detailData): array
    {
        return DB::transaction(function () use ($delivId, $headerData, $detailData) {
            // Get existing delivery
            $delivHdr = DelivHdr::findOrFail($delivId);

            // Update header
            $delivHdr = $this->saveHeader($headerData, $delivId);

            // Delete existing details
            $this->deleteDetail($delivHdr, $detailData);

            $this->saveDetail($headerData, $detailData);

            return [
                'header' => $delivHdr,
            ];
        });
    }

    public function delDelivery(int $delivId): bool
    {
        return DB::transaction(function () use ($delivId) {
            $delivHdr = DelivHdr::findOrFail($delivId);

            // Delete details first
            $this->deleteDetail($delivHdr, []);

            return $this->deleteHeader($delivId);
        });
    }

    // Region Delivery Header Methods
    private function saveHeader(array $headerData, int $delivId = null): DelivHdr
    {
        if ($delivId) {
            $delivHdr = DelivHdr::findOrFail($delivId);
            $delivHdr->update($headerData);
        } else {
            $delivHdr = DelivHdr::create($headerData);
        }

        return $delivHdr;
    }

    private function deleteHeader(int $delivId): bool
    {
        $delivHdr = DelivHdr::findOrFail($delivId);
        return (bool) $delivHdr->delete();
    }

    // Region Delivery Detail Methods
    private function saveDetail(array $headerData, array $detailData): void
    {
        foreach ($detailData as $detail) {
            // Simpan detail
            $delivDetail = new DelivDtl($detail);
            $delivDetail->save();

            // Siapkan data untuk delReservation
            $delivDetailRsv = $delivDetail->toArray();

            // Sesuaikan tr_type untuk delReservation
            if ($delivDetail->tr_type === 'PD') {
                $delivDetailRsv['tr_type'] = 'PO';
            } else if ($delivDetail->tr_type === 'SD') {
                $delivDetailRsv['tr_type'] = 'SO';
            }

            // Hapus reservasi order
            $this->inventoryService->delReservation($headerData, $delivDetailRsv);

            // Tambah stok onhand
            $this->inventoryService->addOnhand($headerData, $delivDetail);

            // Update qty_reff di OrderDtl
            if ($delivDetail->reffdtl_id) {
                $this->orderService->updQtyReff('+', $delivDetail->qty, $delivDetail->reffdtl_id);
            }
        }
    }

    private function deleteDetail(DelivHdr $headerData, array $detailData): void
    {
        // Get existing details
        $existingDetails = DelivDtl::where('trhdr_id', $headerData->id)->get();

        // Delete onhand and reservation for each detail
        foreach ($existingDetails as $detail) {
            $this->inventoryService->delOnhand($headerData, $detail);
            $this->inventoryService->addReservation($headerData->toArray(), $detail->toArray());
            $this->orderService->updQtyReff('-', $detail->qty, $detail->reffdtl_id);
            $detail->forceDelete();
        }
    }

    #endregion

}
