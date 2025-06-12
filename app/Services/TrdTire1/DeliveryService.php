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

            // Simpan detail dan update inventory
            $delivDetails = [];
            foreach ($detailData as $detail) {
                // Set field wajib dari header
                $detail['trhdr_id'] = $delivHdr->id;
                $detail['tr_code'] = $delivHdr->tr_code;
                $detail['tr_type'] = $delivHdr->tr_type;

                // Simpan detail
                $delivDetail = DelivDtl::create($detail);
                $delivDetails[] = $delivDetail;

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
                $this->inventoryService->addOnhand($delivHdr, $delivDetail);

                // Update qty_reff di OrderDtl
                if ($delivDetail->reffdtl_id) {
                    $this->orderService->updQtyReff('+', $delivDetail->qty, $delivDetail->reffdtl_id);
                }
            }

            return [
                'header' => $delivHdr,
                'details' => $delivDetails
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
            $this->deleteDetail($delivHdr);

            // Save new details
            $delivDetails = $this->saveDetail($delivHdr, $detailData);

            return [
                'header' => $delivHdr,
                'details' => $delivDetails
            ];
        });
    }

    public function delDelivery(int $delivId): bool
    {
        return DB::transaction(function () use ($delivId) {
            $delivHdr = DelivHdr::findOrFail($delivId);

            $this->deleteDetail($delivHdr);
            return $this->deleteHeader($delivId);

        });
    }

    // Region Delivery Header Methods
    private function saveHeader(array $data, int $delivId = null): DelivHdr
    {
        // Hapus field yang tidak ada di tabel
        // unset($data['wh_code']);
        // unset($data['wh_id']);

        if ($delivId) {
            $delivHdr = DelivHdr::findOrFail($delivId);
            $delivHdr->update($data);
        } else {
            $delivHdr = DelivHdr::create($data);
        }

        return $delivHdr;
    }

    private function deleteHeader(int $delivId): bool
    {
        $delivHdr = DelivHdr::findOrFail($delivId);
        return (bool) $delivHdr->delete();
    }

    // Region Delivery Detail Methods
    private function saveDetail(DelivHdr $delivHdr, array $details): array
    {
        $savedDetails = [];
        foreach ($details as $detail) {
            // Set field wajib dari header
            $detail['trhdr_id'] = $delivHdr->id;
            $detail['tr_code'] = $delivHdr->tr_code;
            $detail['tr_type'] = $delivHdr->tr_type;
            // $detail['tr_qty'] = $detail['qty']; // Tambahkan tr_qty sama dengan qty

            $savedDetail = DelivDtl::create($detail);
            $savedDetails[] = $savedDetail;

            // Update inventory
            $this->inventoryService->addOnhand($delivHdr, $savedDetail);
        }

        return $savedDetails;
    }

    private function deleteDetail(DelivHdr $delivHdr): void
    {
        // Get existing details
        $existingDetails = DelivDtl::where('trhdr_id', $delivHdr->id)->get();

        // Delete onhand and reservation for each detail
        foreach ($existingDetails as $detail) {
            $this->inventoryService->delOnhand($delivHdr, $detail);
            if ($detail->reffdtl_id) {
                $this->orderService->updQtyReff('-', $detail->qty, $detail->reffdtl_id);
            }
        }

        // Delete the details
        DelivDtl::where('trhdr_id', $delivHdr->id)->forceDelete();
    }

    #endregion

}
