<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Transaction\{DelivHdr, DelivPacking, DelivPicking, OrderHdr, OrderDtl};
use App\Models\TrdTire1\Master\{Material, Partner};
use App\Models\TrdTire1\Inventories\{IvtBal, IvtLog};
use App\Services\TrdTire1\{OrderService, InventoryService, MasterService, ConfigService, BillingService};
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
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
    public function saveDelivery(array $headerData, array $detailData)
    {
        try {
            $header = $this->saveHeader($headerData);

            $headerData['id'] = $header->id;

            // dd($headerData);
            $details = $this->saveDetails($headerData, $detailData);

            // dd($header, $details);
            return [
                'header' => $header,
                'details' => $details
            ];
            // Set trhdr_id, tr_code, tr_type pada setiap packing
            // foreach ($detailData as &$detail) {
            //     $detail['trhdr_id'] = $delivHdr->id;
            //     $detail['tr_code'] = $delivHdr->tr_code;
            //     $detail['tr_type'] = $delivHdr->tr_type;
            // }
            // unset($packing);

            // // Set trhdr_id pada setiap picking
            // foreach ($pickingData as &$picking) {
            //     $picking['trhdr_id'] = $delivHdr->id;
            // }
            // unset($picking);

            // dd($packingData, $pickingData);

            // $this->savePacking($packingData, $pickingData);

            // dd($packingData, $pickingData);

            // app(BillingService::class)->addfromDelivery($delivHdr->id);
        } catch (Exception $e) {
            throw new Exception('Error adding delivery: ' . $e->getMessage());
        }
    }

    public function delDelivery(int $delivId)
    {
        // Hapus billing yang terkait terlebih dahulu
        app(BillingService::class)->delFromDelivery($delivId);

        $this->deletePacking($delivId);
        $this->deleteHeader($delivId);
    }

    // Region Delivery Header Methods
    private function saveHeader(array $headerData): DelivHdr
    {
        if (!isset($headerData['id']) || empty($headerData['id'])) {
            $deliveryHdr = DelivHdr::create($headerData);
        } else {
            $deliveryHdr = DelivHdr::findOrFail($headerData['id']);
            $deliveryHdr->fill($headerData);
            if ($deliveryHdr->isDirty()) {
                $deliveryHdr->save();
            }
        }
        return $deliveryHdr;
    }

    private function saveDetails(array $headerData, array $detailData)
    {
        // dd($headerData, $detailData);
        if (!isset($headerData['id']) || empty($headerData['id'])) {
            throw new Exception('Header ID tidak ditemukan. Pastikan header sudah tersimpan.');
        }

        // $updatedDetails = [];
        // $existingDetailIds = [];
        $packing_ids = [];
        $picking_ids = [];

        foreach ($detailData as $detail) {
            $detail['trhdr_id'] = $headerData['id'];
            $detail['tr_type'] = $headerData['tr_type'];
            $detail['tr_code'] = $headerData['tr_code'];

            if (!isset($detail['id']) || empty($detail['id'])) {
                $detail['tr_seq'] = $this->getNextSequence($headerData['id']);
                // $newDetail = DelivPacking::create($detail);
                $packing = new DelivPacking();
                $packing->fill($detail);
                $packing->save();
                $packing_ids[] = $packing->id;

                $detail['trpacking_id'] = $packing->id;
                $picking = new DelivPicking();
                $picking->fill($detail);
                $picking->save();
                $picking_ids[] = $picking->id;

                // $this->inventoryService->addReservation($headerData, $newDetail->toArray());

            } else {
                $packing = DelivPacking::withTrashed()->find($detail['id']);
                $packing->fill($detail);
                if ($packing->isDirty()) {
                    // $this->inventoryService->delIvtLog(0, $packing->id);
                    $packing->save();

                    // $this->inventoryService->addReservation($headerData, $packing->toArray());

                    $pickings = DelivPicking::withTrashed()->where('trpacking_id', '=' ,$packing->id)->get();
                    foreach ($pickings as $picking) {
                        // $this->inventoryService->delIvtLog(0, $packing->id);
                        $picking->fill($detail);
                        if ($picking->isDirty()) {
                            $picking->save();

                            // $this->inventoryService->addReservation($headerData, $packing->toArray());
                        }
                    }
                }


                $packing_ids[] = $packing->id;
                $picking_ids[] = $picking->id;
            }
        }
        // dd($headerData, $detailData);
        $packings = DelivPacking::where('trhdr_id', '=' ,$headerData['id'] )
        ->whereNotIn('id', $packing_ids)
        ->get();
        foreach ($packings as $packing) {
            // Hapus ivt_logs untuk detail yang dihapus
            // $this->inventoryService->delIvtLog(0, $packing->id);
            $packing->delete();
            $pickings = DelivPicking::where('trpacking_id', '=' ,$packing->id );
            foreach ($pickings as $picking) {
                $picking->delete();
            }
        }

        return true;

    }

    private function getNextSequence(int $orderId): int
    {
        // withTrashed() agar termasuk baris soft-deleted
        $max = OrderDtl::withTrashed()
            ->where('trhdr_id', $orderId)
            ->max('tr_seq');

        return ($max ?? 0) + 1;
    }



    private function deleteHeader(int $delivId): bool
    {
        $delivHdr = DelivHdr::findOrFail($delivId);
        return (bool) $delivHdr->forceDelete();
    }

    // Region Delivery Packing Methods
    private function savePacking(array $packingData, array $allPickingData)
    {
        try {
            foreach ($packingData as $packingIndex => $packing) {

                // Hanya field yang ada di model DelivPacking
                $packingFields = [
                    'trhdr_id', 'tr_type', 'tr_code', 'tr_seq', 'reffdtl_id', 'reffhdr_id',
                    'reffhdrtr_type', 'reffhdrtr_code', 'reffdtltr_seq', 'matl_descr', 'qty'
                ];
                $delivPacking = DelivPacking::create(Arr::only($packing, $packingFields));

                // Ambil picking data untuk packing ini
                $packingPickings = array_filter($allPickingData, function($picking) use ($packingIndex) {
                    return $picking['packing_index'] == $packingIndex;
                });

                foreach ($packingPickings as $picking) {
                    // Hanya field yang ada di model DelivPicking
                    $pickingFields = [
                        'trpacking_id', 'tr_seq', 'matl_id', 'matl_code', 'matl_uom',
                        'wh_id', 'wh_code', 'batch_code', 'qty'
                    ];

                    // Tambahkan ivt_id hanya jika tidak null
                    if ($picking['ivt_id'] !== null) {
                        $pickingFields[] = 'ivt_id';
                    }

                    $picking['trpacking_id'] = $delivPacking->id;
                    $delivPicking = DelivPicking::create(Arr::only($picking, $pickingFields));

                    // Prepare data untuk inventory service
                    $headerDataForInv = [
                        'tr_type' => $delivPacking->tr_type,
                        'tr_date' => date('Y-m-d'),
                        'reff_id' => $delivPacking->reffdtl_id
                    ];

                    $pickingDataForInv = [
                        'trhdr_id' => $delivPacking->trhdr_id,
                        'tr_code' => $delivPacking->tr_code,
                        'tr_type' => $delivPacking->tr_type,
                        'tr_seq' => $picking['tr_seq'],
                        'matl_id' => $picking['matl_id'],
                        'matl_code' => $picking['matl_code'],
                        'matl_uom' => $picking['matl_uom'],
                        'wh_id' => $picking['wh_id'],
                        'wh_code' => $picking['wh_code'],
                        'batch_code' => $picking['batch_code'],
                        'qty' => $picking['qty'],
                        'reffdtl_id' => $delivPacking->reffdtl_id,
                        'reffhdr_id' => $delivPacking->reffhdr_id,
                        'id' => $delivPicking->id
                    ];

                    // dd($headerDataForInv, $pickingDataForInv, $delivPacking);
                    // Update inventory
                    $this->inventoryService->addReservation($headerDataForInv, $pickingDataForInv);
                    $ivtBalId = $this->inventoryService->addOnhand($headerDataForInv, $pickingDataForInv);

                    // Update ivt_id di DelivPicking dengan ID yang baru dibuat
                    $delivPicking->ivt_id = $ivtBalId;
                    $delivPicking->save();

                    // Update qty_reff di OrderDtl jika masih diperlukan (jika tidak, abaikan)
                    if ($delivPacking->reffdtl_id) {
                        $this->orderService->updOrderQtyReff('+', $delivPicking->qty, $delivPacking->reffdtl_id);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error in savePacking: ' . $e->getMessage());
            Log::error('Packing data: ' . json_encode($packingData));
            Log::error('Picking data: ' . json_encode($allPickingData));
            throw $e;
        }
    }

    private function deletePacking(int $trHdrId): void
    {
        // Get existing packings
        $existingPackings = DelivPacking::where('trhdr_id', $trHdrId)->get();

        // Delete pickings for each packing
        foreach ($existingPackings as $packing) {
            $existingPickings = DelivPicking::where('trpacking_id', $packing->id)->get();

            // Delete onhand and reservation for each picking
            foreach ($existingPickings as $picking) {
                // Kembalikan qty_reff di OrderDtl
                if ($picking->reffdtl_id) {
                    $this->orderService->updOrderQtyReff('-', $picking->qty, $picking->reffdtl_id);
                }
                $picking->forceDelete();
            }

            $packing->forceDelete();
        }

        // Hapus log inventory berdasarkan trhdr_id
        $this->inventoryService->delIvtLog($trHdrId);
    }

    public function updDelivQtyReff(string $mode, float $qty, int $dlvpicking_id)
    {
        // Update qty_reff di DelivPicking
        $delivPicking = DelivPicking::find($dlvpicking_id);
        if ($delivPicking) {
            if ($mode === '+') {
                $delivPicking->qty_reff = ($delivPicking->qty_reff ?? 0) + $qty;
            } else if ($mode === '-') {
                $delivPicking->qty_reff = ($delivPicking->qty_reff ?? 0) - $qty;
            }
            $delivPicking->save();
        }
    }

    #endregion

}
