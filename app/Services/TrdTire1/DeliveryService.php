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
        // app(BillingService::class)->delFromDelivery($delivId);

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

        $existingPackings = DelivPacking::withTrashed()->where('trhdr_id', $headerData['id'])->get();
        // foreach ($existingPackings as $packing) {
        //     dd($packing->isDirty(), $packing->isDirty('qty'), $packing->isClean(), $packing->getDirty());
        // }
        // dd($existingPackings->isDirty(), $existingPackings->isDirty('qty'), $existingPackings->isClean(), $existingPackings->getDirty());

        $packing_ids = [];
        foreach ($detailData as $detail) {
            $detail['trhdr_id'] = $headerData['id'];
            $detail['tr_type'] = $headerData['tr_type'];
            $detail['tr_code'] = $headerData['tr_code'];

            // dd($detail);
            if (!isset($detail['id']) || empty($detail['id'])) {
                $detail['tr_seq'] = $this->getNextSequence('DelivPacking',$headerData['id']);
                $packing = new DelivPacking();
                $packing->fill($detail);
                $packing->save();

                $detail['id']= $packing->id;
                $this->inventoryService->addReservation($headerData, $detail);
                $this->savePicking($headerData,$detail);
                $packing_ids[] = $packing->id;
            } else {
                $packing = $existingPackings->firstWhere('id', $detail['id']);
                if ($packing) {
                    $packing->fill($detail);
                    if ($packing->isDirty()) {
                        $this->inventoryService->delIvtLog(0, $detail['id']);
                        $packing->save();

                        $this->inventoryService->addReservation($headerData, $detail);
                        $this->savePicking($headerData,$detail);
                    }
                }
                $packing_ids[] = $packing->id;
            }

        }
        foreach ($existingPackings as $existing) {
            if (!in_array($existing->id, $packing_ids)) {
                $this->inventoryService->delIvtLog(0, $existing->id);
                $existing->delete();
            }
        }
        return true;

    }

    private function savePicking($headerData, $detailData)
    {
        $pickingData = [];

        if ($detailData['tr_type'] === 'PD') {
            $pickingData[] = [
                'id' => null,
                'trpacking_id' => $detailData['id'],
                'matl_id' => $detailData['matl_id'],
                'matl_code' => $detailData['matl_code'],
                'matl_uom' => $detailData['matl_uom'],
                'wh_id' => $detailData['wh_id'],
                'wh_code' => $detailData['wh_code'],
                'batch_code' => date('ymd', strtotime($detailData['order_date'])),
                'qty' => $detailData['qty'],
                'reffdtl_id' => $detailData['reffdtl_id'],
                'trdtl_id' => $detailData['id'],
            ];
                } else if ($detailData['tr_type'] === 'SD') {
            $ivtBal = IvtBal::where('matl_id', $detailData['matl_id'])
                ->where('matl_uom', $detailData['matl_uom'])
                ->where('wh_id', $detailData['wh_id'])
                ->orderBy('batch_code')
                ->get();
            $qty_remaining = $detailData['qty'];
            foreach ($ivtBal as $bal) {
                $pickingData[] = [
                    'id' => null,
                    'trpacking_id' => $detailData['id'],
                    'matl_id' => $detailData['matl_id'],
                    'matl_code' => $detailData['matl_code'],
                    'matl_uom' => $detailData['matl_uom'],
                    'wh_id' => $detailData['wh_id'],
                    'wh_code' => $detailData['wh_code'],
                    'batch_code' => $bal->batch_code,
                    'ivt_id' => $bal->id,
                    'reffdtl_id' => $detailData['reffdtl_id'],
                    'trdtl_id' => $detailData['id'],
                ];
                if ($bal->qty_oh >= $qty_remaining) {
                    $pickingData[count($pickingData)-1]['qty'] = $qty_remaining;
                    $qty_remaining = 0;
                    break;
                } else {
                    $pickingData[count($pickingData)-1]['qty'] = $bal->qty_oh;
                    $qty_remaining  -= $bal->qty_oh;
                }
            }
            if ($qty_remaining > 0) {
                throw new Exception('Tidak cukup stok untuk picking. Qty yang diminta: ' . $detailData['qty'] . ', Qty yang tersedia: ' . ($detailData['qty'] - $qty_remaining));
            }
        }

        $picking_ids = [];
        $existingPickings = DelivPicking::withTrashed()->where('trpacking_id', $detailData['id'])->get();
        foreach ($pickingData as $key => $detail) {
            $picking = $existingPickings
                ->where('trpacking_id', $detail['trpacking_id'])
                ->where('matl_id', $detail['matl_id'])
                ->where('matl_uom', $detail['matl_uom'])
                ->where('wh_id', $detail['wh_id'])
                ->where('batch_code', $detail['batch_code'])
                ->first();
            if (!$picking) {
                $detail['tr_seq'] = $this->getNextSequence('DelivPicking',$detailData['id']);
                $picking = new DelivPicking();
                $picking->fill($detail);
                $picking->save();
                $detail['id'] = $picking->id;
                $ivtBalId = $this->inventoryService->addOnhand($headerData, $detail);
                $picking->ivt_id = $ivtBalId;
                $picking->save();
                $picking_ids[] = $picking->id;
            } else {
                $detail['id'] = $picking->id;
                $detail['tr_seq'] = $picking->tr_seq;
                $picking->fill($detail);
                if ($picking->isDirty()) {
                    $this->inventoryService->delIvtLog(0, $picking->id);
                    // dd($picking, $detail);
                    $picking->save();
                    $this->inventoryService->addOnhand($headerData, $detail);
                }
                $picking_ids[] = $picking->id;
            }

        }
        foreach ($existingPickings as $existing) {
            if (!in_array($existing->id, $picking_ids)) {
                $this->inventoryService->delIvtLog(0, $existing->id);
                $existing->delete();
            }
        }
        return true;

    }

    private function getNextSequence($model,int $keyId): int
    {
        if ($model === 'DelivPacking') {
            $max = DelivPacking::withTrashed()
                ->where('trhdr_id', $keyId)
                ->max('tr_seq');
        } else if ($model === 'DelivPicking') {
            $max = DelivPicking::withTrashed()
                ->where('trpacking_id', $keyId)
                ->max('tr_seq');
        }
        return ($max ?? 0) + 1;
    }



    private function deleteHeader(int $delivId): bool
    {
        $delivHdr = DelivHdr::findOrFail($delivId);
        return (bool) $delivHdr->forceDelete();
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
