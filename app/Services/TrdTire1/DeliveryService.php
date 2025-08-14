<?php

namespace App\Services\TrdTire1;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\TrdTire1\Master\{Material, Partner};
use App\Models\TrdTire1\Inventories\{IvtBal, IvtLog};

use App\Models\TrdTire1\Transaction\{DelivHdr, DelivPacking, DelivPicking, OrderHdr, OrderDtl};
use App\Services\TrdTire1\{OrderService, InventoryService, MasterService, ConfigService, BillingService};

class DeliveryService
{
    protected $inventoryService;
    protected $orderService;

    public function __construct(InventoryService $inventoryService, OrderService $orderService)
    {
        $this->inventoryService = $inventoryService;
        $this->orderService = $orderService;
    }

    #region Save Delivery
    public function saveDelivery(array $headerData, array $detailData)
    {
        try {
            $header = $this->saveHeader($headerData);

            $headerData['id'] = $header->id;

            // dd($headerData,$detailData);
            $details = $this->saveDetails($headerData, $detailData);

            // dd($header, $details);
            return [
                'header' => $header,
                'details' => $details
            ];
        } catch (Exception $e) {
            throw new Exception('Error adding delivery: ' . $e->getMessage());
        }
    }

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
        if (!isset($headerData['id']) || empty($headerData['id'])) {
            throw new Exception('Header ID tidak ditemukan. Pastikan header sudah tersimpan.');
        }

        $existingPackings = DelivPacking::where('trhdr_id', $headerData['id'])
            ->get();

        $packing_ids = [];
        foreach ($detailData as &$detail) {

            $detail['trhdr_id'] = $headerData['id'];
            $detail['tr_type'] = $headerData['tr_type'];
            $detail['tr_code'] = $headerData['tr_code'];

            // dd($detail);
            if (!isset($detail['id']) || empty($detail['id'])) {
                $detail['tr_seq'] = DelivPacking::getNextTrSeq($headerData['id']);

                $packing = new DelivPacking();
                $packing->fill($detail);
                $packing->save();
                OrderDtl::updateQtyReff($detail['qty'], $detail['reffdtl_id']);

                $detail['id'] = $packing->id;
                $this->inventoryService->addReservation($headerData, $detail);
                $this->savePicking($headerData, $detail);
                $packing_ids[] = $packing->id;
            } else {
                $packing = $existingPackings->firstWhere('id', $detail['id']);
                if ($packing) {
                    $originalQty = $packing->getOriginal('qty');
                    $packing->fill($detail);
                    if ($packing->isDirty()) {
                        $this->inventoryService->delIvtLog(0, $detail['id']);
                        OrderDtl::updateQtyReff(-$originalQty, $detail['reffdtl_id']);
                        $packing->save();
                        OrderDtl::updateQtyReff($detail['qty'], $detail['reffdtl_id']);

                        $this->inventoryService->addReservation($headerData, $detail);
                        $this->savePicking($headerData, $detail);
                    }
                }
                $packing_ids[] = $packing->id;
            }
        }
        unset($detail);
        foreach ($existingPackings as $existing) {
            if (!in_array($existing->id, $packing_ids)) {
                $this->inventoryService->delIvtLog(0, $existing->id);
                OrderDtl::updateQtyReff(-$existing->qty, $existing->reffdtl_id);
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
                    $pickingData[count($pickingData) - 1]['qty'] = $qty_remaining;
                    $qty_remaining = 0;
                    break;
                } else {
                    $pickingData[count($pickingData) - 1]['qty'] = $bal->qty_oh;
                    $qty_remaining  -= $bal->qty_oh;
                }
            }
            if ($qty_remaining > 0) {
                throw new Exception('Tidak cukup stok untuk picking. Qty yang diminta: ' . $detailData['qty'] . ',
                Qty yang tersedia: ' . ($detailData['qty'] - $qty_remaining));
            }
        }

        $picking_ids = [];
        $existingPickings = DelivPicking::where('trpacking_id', $detailData['id'])->get();
        foreach ($pickingData as $key => $detail) {
            $picking = $existingPickings
                ->where('trpacking_id', $detail['trpacking_id'])
                ->where('matl_id', $detail['matl_id'])
                ->where('matl_uom', $detail['matl_uom'])
                ->where('wh_id', $detail['wh_id'])
                ->where('batch_code', $detail['batch_code'])
                ->first();
            if (!$picking) {
                $detail['tr_seq'] = DelivPacking::where('id', $detailData['id'])->value('tr_seq');
                $detail['tr_seq2'] = DelivPicking::getNextTrSeq($detailData['id']);

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
                $detail['tr_seq2'] = $picking->tr_seq2;

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

    #region Delete Delivery
    public function delDelivery(int $delivId)
    {
        $this->deleteDetail($delivId);
        $this->inventoryService->delIvtLog($delivId);
        $this->deleteHeader($delivId);
    }

    private function deleteHeader(int $delivId): bool
    {
        return (bool) DelivHdr::where('id', $delivId)->forceDelete();
    }

    private function deleteDetail(int $delivId): bool
    {
        // Get existing packings
        $dbDelivPacking = DelivPacking::where('trhdr_id', $delivId)->get();
        foreach ($dbDelivPacking as $packing) {
            OrderDtl::updateQtyReff(-$packing->qty, $packing->reffdtl_id);
            DelivPicking::where('trpacking_id', $packing->id)->delete();
            $packing->Delete();
        }
        return true;
    }
}
