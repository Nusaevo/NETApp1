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
        try {
            $delivHdr = $this->saveHeader($headerData);

            // Set trhdr_id, tr_code, tr_type pada setiap detail
            foreach ($detailData as &$detail) {
                $detail['trhdr_id'] = $delivHdr->id;
                $detail['tr_code'] = $delivHdr->tr_code;
                $detail['tr_type'] = $delivHdr->tr_type;
            }
            unset($detail);

            $this->saveDetail($headerData, $detailData);

            app(BillingService::class)->addfromDelivery($delivHdr->id);
        } catch (Exception $e) {
            throw new Exception('Error adding delivery: ' . $e->getMessage());
        }
        return [
            'header' => $delivHdr
        ];
    }

    public function updDelivery(int $delivId, array $headerData, array $detailData): DelivHdr
    {
        try {
            // Cek apakah delivery header ada
            $delivHdr = DelivHdr::find($delivId);
            if (!$delivHdr) {
                throw new Exception('Delivery header tidak ditemukan');
            }

            // Update header
            $delivHdr->update($headerData);

            // Hapus detail lama
            $this->deleteDetail($delivId);

            // Simpan detail baru
            $this->saveDetail($headerData, $detailData);

            // Hapus billing lama terlebih dahulu, kemudian buat yang baru
            // app(BillingService::class)->delFromDelivery($delivHdr->id);
            app(BillingService::class)->updfromDelivery($delivHdr->id);
            // dd($headerData, $detailData);

            return $delivHdr;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function delDelivery(int $delivId)
    {
        // Hapus billing yang terkait terlebih dahulu
        app(BillingService::class)->delFromDelivery($delivId);

        $this->deleteDetail($delivId);
        $this->deleteHeader($delivId);
    }

    // Region Delivery Header Methods
    private function saveHeader(array $headerData): DelivHdr
    {
        if (isset($headerData['id'])) {
            $delivHdr = DelivHdr::find($headerData['id']);
            if (!$delivHdr) {
                throw new Exception('Delivery header tidak ditemukan');
            }
            $delivHdr->update($headerData);
            return $delivHdr;
        } else {
            return DelivHdr::create($headerData);
        }
    }

    private function deleteHeader(int $delivId): bool
    {
        $delivHdr = DelivHdr::findOrFail($delivId);
        return (bool) $delivHdr->forceDelete();
    }

    // Region Delivery Detail Methods
    private function saveDetail(array $headerData, array $detailData): void
    {
        foreach ($detailData as $detail) {
            $delivDetail = DelivDtl::create($detail);

            // Siapkan data untuk delReservation
            $delivDetailRsv = $delivDetail->toArray();
            $headerDataRsv = $headerData;            // Sesuaikan tr_type untuk delReservation
            if ($delivDetail->tr_type === 'PD') {
                $delivDetailRsv['tr_type'] = 'PO';
                $headerDataRsv['tr_type'] = 'PO';
            } else if ($delivDetail->tr_type === 'SD') {
                $delivDetailRsv['tr_type'] = 'SO';
                $headerDataRsv['tr_type'] = 'SO';
            }
            // Hapus reservasi order
            $this->inventoryService->addReservation('-', $headerDataRsv, $delivDetailRsv);

            // Tambah stok onhand - convert $delivDetail object to array
            $this->inventoryService->addOnhand($headerData, $delivDetail->toArray());

            // Update qty_reff di OrderDtl
            if ($delivDetail->reffdtl_id) {
                $this->orderService->updOrderQtyReff('+', $delivDetail->qty, $delivDetail->reffdtl_id);
            }
        }
    }

    private function deleteDetail(int $trHdrId): void
    {
        // Get existing details
        $existingDetails = DelivDtl::where('trhdr_id', $trHdrId)->get();

        // Delete onhand and reservation for each detail
        foreach ($existingDetails as $detail) {
            // Kembalikan qty_reff di OrderDtl
            if ($detail->reffdtl_id) {
                $this->orderService->updOrderQtyReff('-', $detail->qty, $detail->reffdtl_id);
            }
            $detail->forceDelete();
        }

        // Hapus log inventory
        $this->inventoryService->delIvtLog($trHdrId);
    }

    public function updDelivQtyReff(string $mode, float $qty, int $dlvdtl_id)
    {
        // Update qty_reff di DelivDtl
        $delivDtl = DelivDtl::find($dlvdtl_id);
        if ($delivDtl) {
            if ($mode === '+') {
                $delivDtl->qty_reff = ($delivDtl->qty_reff ?? 0) + $qty;
            } else if ($mode === '-') {
                $delivDtl->qty_reff = ($delivDtl->qty_reff ?? 0) - $qty;
            }
            $delivDtl->save();
        }
    }

    #endregion

}
