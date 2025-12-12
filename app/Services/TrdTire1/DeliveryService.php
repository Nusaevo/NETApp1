<?php

namespace App\Services\TrdTire1;

use Exception;
use App\Models\TrdTire1\Master\{Material, Partner};
use App\Models\TrdTire1\Inventories\{IvtBal, IvtLog};

use App\Models\TrdTire1\Transaction\{DelivHdr, DelivPacking, DelivPicking, OrderHdr, OrderDtl};
use App\Services\TrdTire1\{OrderService, InventoryService, MasterService, ConfigService, BillingService};

class DeliveryService
{
    protected $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    private function isMaterialJasa(int $matlId): bool
    {
        $material = Material::find($matlId);
        return $material && strtoupper(trim($material->category ?? '')) === 'JASA';
    }

    #region Save Delivery
    public function saveDelivery(array $headerData, array $detailData)
    {
        try {
            $header = $this->saveHeader($headerData);

            $headerData['id'] = $header->id;

            $details = $this->saveDetails($headerData, $detailData);

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

                    // Check if warehouse changed by comparing with existing picking
                    $existingPicking = DelivPicking::where('trpacking_id', $detail['id'])->first();
                    $warehouseChanged = false;
                    if ($existingPicking) {
                        $warehouseChanged = ($existingPicking->wh_id != $detail['wh_id'] ||
                                           $existingPicking->wh_code != $detail['wh_code']);
                    }

                    $packing->fill($detail);
                    if ($packing->isDirty()) {
                        // dd ($headerData['tr_type'] . 'R', $headerData['id'], $detail['id']);
                        $this->inventoryService->delIvtLog($headerData['tr_type'] . 'R', $headerData['id'], $detail['id']);
                        OrderDtl::updateQtyReff(-$originalQty, $detail['reffdtl_id']);
                        $packing->save();
                        OrderDtl::updateQtyReff($detail['qty'], $detail['reffdtl_id']);

                        $this->inventoryService->addReservation($headerData, $detail);
                        $this->savePicking($headerData, $detail);
                    } elseif ($warehouseChanged) {
                        // Warehouse changed but packing not dirty, still need to update picking
                        $this->savePicking($headerData, $detail);
                    }
                }
                $packing_ids[] = $packing->id;
            }
        }
        unset($detail);
        foreach ($existingPackings as $existing) {
            if (!in_array($existing->id, $packing_ids)) {
                // Hapus semua picking terlebih dahulu beserta log PD-nya
                $existingPickings = DelivPicking::where('trpacking_id', $existing->id)->get();
                foreach ($existingPickings as $picking) {
                    // Hapus PD log yang menggunakan picking id sebagai trdtl_id
                    $this->inventoryService->delIvtLog($headerData['tr_type'], $headerData['id'], $picking->id);
                    $picking->delete();
                }

                // Hapus log reservation (PDR) yang dibuat dengan packing id
                $this->inventoryService->delIvtLog($headerData['tr_type'] . 'R', $headerData['id'], $existing->id);
                OrderDtl::updateQtyReff(-$existing->qty, $existing->reffdtl_id);
                $existing->delete();
            }
        }
        return true;
    }

    private function savePicking($headerData, $detailData)
    {
        $pickingData = [];
        $trhdrId = $headerData['id'] ?? null;
        $trType = $headerData['tr_type'] ?? null;
        $trCode = $headerData['tr_code'] ?? null;
        $trpackingSeq = $detailData['tr_seq'] ?? DelivPacking::where('id', $detailData['id'])->value('tr_seq');
        $basePickingMeta = [
            'trhdr_id' => $trhdrId,
            'tr_type' => $trType,
            'tr_code' => $trCode,
            'trpacking_seq' => $trpackingSeq,
        ];

        // Untuk JASA, tidak perlu melihat stok - langsung buat picking seperti PD
        $isJasa = $this->isMaterialJasa($detailData['matl_id']);

        if ($detailData['tr_type'] === 'PD' || ($detailData['tr_type'] === 'SD' && $isJasa)) {
            $pickingData[] = array_merge($basePickingMeta, [
                'id' => null,
                'trpacking_id' => $detailData['id'],
                'matl_id' => $detailData['matl_id'],
                'matl_code' => $detailData['matl_code'],
                'matl_uom' => $detailData['matl_uom'],
                'wh_id' => $detailData['wh_id'],
                'wh_code' => $detailData['wh_code'],
                'batch_code' => $detailData['tr_type'] === 'PD'
                    ? date('ymd', strtotime($detailData['order_date'] ?? 'now'))
                    : ($detailData['batch_code'] ?? ''),
                'qty' => $detailData['qty'],
                'reffdtl_id' => $detailData['reffdtl_id'],
                'trdtl_id' => $detailData['id'],
            ]);
        } else if ($detailData['tr_type'] === 'SD' && !$isJasa) {
            // Untuk SD non-JASA, tetap cek stok dari IvtBal
            $ivtBal = IvtBal::where('matl_id', $detailData['matl_id'])
                ->where('matl_uom', $detailData['matl_uom'])
                ->where('wh_id', $detailData['wh_id'])
                ->where('qty_oh', '>', 0)
                ->orderBy('batch_code')
                ->get();
            $qty_remaining = $detailData['qty'];
            foreach ($ivtBal as $bal) {
                $pickingData[] = array_merge($basePickingMeta, [
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
                ]);
                if ($bal->qty_oh >= $qty_remaining) {
                    $pickingData[count($pickingData) - 1]['qty'] = $qty_remaining;
                    $qty_remaining = 0;
                    break;
                } else {
                    $pickingData[count($pickingData) - 1]['qty'] = $bal->qty_oh;
                    $qty_remaining  -= $bal->qty_oh;
                }
            }

            // Validasi: Pastikan stok cukup sebelum membuat picking
            if ($qty_remaining > 0) {
                $totalStock = IvtBal::where('matl_id', $detailData['matl_id'])
                    ->where('matl_uom', $detailData['matl_uom'])
                    ->where('wh_id', $detailData['wh_id'])
                    ->where('qty_oh', '>', 0)
                    ->sum('qty_oh');
                throw new Exception('Stok tidak cukup untuk material ' . $detailData['matl_code'] .
                    ' di gudang ' . $detailData['wh_code'] . '. Stok tersedia: ' . $totalStock .
                    ', Dibutuhkan: ' . $detailData['qty']);
            }
        }

        $picking_ids = [];
        $existingPickings = DelivPicking::where('trpacking_id', $detailData['id'])->get();

        foreach ($pickingData as $key => $detail) {
            // For PD type, find picking without wh_id filter to allow warehouse updates
            // For SD type, keep wh_id filter as there might be multiple pickings with different warehouses
            if ($detailData['tr_type'] === 'PD') {
                $picking = $existingPickings
                    ->where('trpacking_id', $detail['trpacking_id'])
                    ->where('matl_id', $detail['matl_id'])
                    ->where('matl_uom', $detail['matl_uom'])
                    ->where('batch_code', $detail['batch_code'])
                    ->first();
            } else {
                $picking = $existingPickings
                    ->where('trpacking_id', $detail['trpacking_id'])
                    ->where('matl_id', $detail['matl_id'])
                    ->where('matl_uom', $detail['matl_uom'])
                    ->where('wh_id', $detail['wh_id'])
                    ->where('batch_code', $detail['batch_code'])
                    ->first();
            }

            if (!$picking) {
                $detail['tr_seq'] = DelivPicking::getNextTrSeq($detailData['id']);
                $detail['tr_seq2'] = DelivPicking::getNextTrSeq($detailData['id']);

                $picking = new DelivPicking();
                $picking->fill($detail);
                $picking->save();

                $detail['id'] = $picking->id;

                $packingTrSeq = DelivPacking::where('id', $detailData['id'])->value('tr_seq');
                $detail['tr_seq'] = $packingTrSeq;

                $pickingTrSeq = $picking->tr_seq;
                $detail['tr_seq2'] = $pickingTrSeq;

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
                    // dd ($detailData['tr_type'], $headerData['id'], $picking->id);
                    $this->inventoryService->delIvtLog($detailData['tr_type'], $headerData['id'], $picking->id);
                    $picking->save();

                    // Untuk ivt_logs, gunakan tr_seq dari packing
                    $packingTrSeq = DelivPacking::where('id', $detailData['id'])->value('tr_seq');
                    $detail['tr_seq'] = $packingTrSeq;

                    // Untuk ivt_logs, gunakan tr_seq2 dari picking
                    $pickingTrSeq = $picking->tr_seq;
                    $detail['tr_seq2'] = $pickingTrSeq;

                    $ivtBalId = $this->inventoryService->addOnhand($headerData, $detail);
                    // Update ivt_id if warehouse changed (for PD type)
                    if ($detailData['tr_type'] === 'PD' && $ivtBalId) {
                        $picking->ivt_id = $ivtBalId;
                        $picking->save();
                    }
                }
                $picking_ids[] = $picking->id;
            }
        }
        foreach ($existingPickings as $existing) {
            if (!in_array($existing->id, $picking_ids)) {
                // dd ($detailData['tr_type'], $headerData['id'], $picking->id);
                $this->inventoryService->delIvtLog($detailData['tr_type'], $headerData['id'], $existing->id);
                $existing->delete();
            }
        }
        return true;
    }

    #region Delete Delivery
    public function delDelivery(string $trType, int $delivId)
    {
        $this->deleteDetail($delivId);
        // dd ($trType, $delivId);
        $reservationType = $trType . 'R'; // SDR untuk SD, PDR untuk PD
        $this->inventoryService->delIvtLog($trType, $delivId);
        $this->inventoryService->delIvtLog($reservationType, $delivId);
        $this->deleteHeader($delivId);
    }

    private function deleteHeader(int $delivId): bool
    {
        return (bool) DelivHdr::where('id', $delivId)->forceDelete();
    }

    private function deleteDetail(int $delivId): bool
    {
        // Get delivery header untuk mendapatkan tr_type
        $delivHdr = DelivHdr::find($delivId);
        if (!$delivHdr) {
            return false;
        }

        $trType = $delivHdr->tr_type;
        $reservationType = $trType . 'R'; // PDR untuk PD, SDR untuk SD

        // Get existing packings
        $dbDelivPacking = DelivPacking::where('trhdr_id', $delivId)->get();
        foreach ($dbDelivPacking as $packing) {
            // Hapus semua picking terlebih dahulu beserta log PD-nya
            $existingPickings = DelivPicking::where('trpacking_id', $packing->id)->get();
            foreach ($existingPickings as $picking) {
                // Hapus PD log yang menggunakan picking id sebagai trdtl_id
                $this->inventoryService->delIvtLog($trType, $delivId, $picking->id);
                $picking->delete();
            }

            // Hapus PDR log yang menggunakan packing id sebagai trdtl_id
            $this->inventoryService->delIvtLog($reservationType, $delivId, $packing->id);

            // Update qty_reff di order detail
            OrderDtl::updateQtyReff(-$packing->qty, $packing->reffdtl_id);

            // Hapus packing
            $packing->Delete();
        }
        return true;
    }


    public function saveDeliverySalesReturn(array $headerData, array $detailData = [])
    {
        // 1. Buat DelivHdr dengan tr_type='SRD'
        $delivHdrData = [
            'tr_type' => 'SRD',
            'tr_code' => $headerData['tr_code'], // Gunakan tr_code yang sama dengan order
            'tr_date' => $headerData['tr_date'] ?? date('Y-m-d'),
            'reff_code' => $headerData['reff_code'] ?? '',
            'reff_date' => $headerData['tr_date'] ?? date('Y-m-d'),
            'partner_id' => $headerData['partner_id'] ?? null,
            'partner_code' => $headerData['partner_code'] ?? '',
            'deliv_by' => '',
            'amt_shipcost' => null,
            'note' => '',
            'billhdr_id' => null,
        ];

        // Cek apakah sudah ada delivery header dengan tr_code yang sama
        $delivHdr = DelivHdr::where('tr_code', $headerData['tr_code'])
            ->where('tr_type', 'SRD')
            ->first();

        if (!$delivHdr) {
            $delivHdr = DelivHdr::create($delivHdrData);
        } else {
            $delivHdr->fill($delivHdrData);
            if ($delivHdr->isDirty()) {
                $delivHdr->save();
            }
        }

        // 2. Ambil semua OrderDtl yang sudah tersimpan untuk header ini
        $orderDetails = OrderDtl::where('trhdr_id', $headerData['id'])
            ->where('tr_type', $headerData['tr_type'])
            ->orderBy('tr_seq')
            ->get();

        if ($orderDetails->isEmpty()) {
            return $delivHdr;
        }

        // 3. Ambil warehouse tujuan dari header
        $whIdTujuan = $headerData['wh_id'] ?? null;
        $whCodeTujuan = $headerData['wh_code'] ?? '';

        // 4. Buat DelivPacking dan DelivPicking untuk setiap OrderDtl
        foreach ($orderDetails as $orderDtl) {
            // Buat DelivPacking (1:1 dengan OrderDtl)
                $packingData = [
                    'trhdr_id' => $delivHdr->id,
                    'tr_type' => 'SRD',
                    'tr_code' => $headerData['tr_code'],
                    'tr_seq' => DelivPacking::getNextTrSeq($delivHdr->id),
                    'reffdtl_id' => $orderDtl->id, // Reference ke OrderDtl SR
                    'reffhdr_id' => $headerData['id'],
                    'reffhdrtr_type' => 'SR',
                    'reffhdrtr_code' => $headerData['tr_code'],
                    'reffdtltr_seq' => $orderDtl->tr_seq,
                    'matl_id' => $orderDtl->matl_id,
                    'matl_code' => $orderDtl->matl_code,
                    'matl_uom' => $orderDtl->matl_uom,
                    'matl_descr' => $orderDtl->matl_descr,
                    'qty' => $orderDtl->qty, // Total qty per material
                ];

                // Cek apakah sudah ada packing
                $packing = DelivPacking::where('trhdr_id', $delivHdr->id)
                    ->where('reffdtl_id', $orderDtl->id)
                    ->where('tr_type', 'SRD')
                    ->first();

            if (!$packing) {
                $packing = DelivPacking::create($packingData);
            } else {
                $packing->fill($packingData);
                if ($packing->isDirty()) {
                    $packing->save();
                }
            }

            // 5. Buat DelivPicking per batch dari detailData
            // Filter detailData berdasarkan matl_id dan qty_return > 0
            $batchDetails = collect($detailData)->filter(function ($d) use ($orderDtl) {
                $matlId = $d['matl_id'] ?? null;
                $qtyReturn = floatval($d['qty_return'] ?? 0);
                return $matlId == $orderDtl->matl_id && $qtyReturn > 0;
            });

            foreach ($batchDetails as $batchDetail) {
                $batchCode = $batchDetail['batch_code'] ?? '';
                $qtyReturn = floatval($batchDetail['qty_return'] ?? 0);

                if (empty($batchCode) || $qtyReturn <= 0) {
                    continue;
                }

                // Gunakan warehouse tujuan dari header, bukan dari detail
                $whId = $whIdTujuan ?? $batchDetail['wh_id'] ?? 0;
                $whCode = $whCodeTujuan ?: ($batchDetail['wh_code'] ?? '');

                // Jika tidak ada warehouse, cari dari IvtBal atau set default
                if (!$whId) {
                    $ivtBal = IvtBal::where('matl_id', $orderDtl->matl_id)
                        ->where('batch_code', $batchCode)
                        ->where('qty_oh', '>', 0)
                        ->first();

                    if ($ivtBal) {
                        $whId = $ivtBal->wh_id;
                        $whCode = $ivtBal->wh_code;
                    } else {
                        // Set default jika tidak ada inventory
                        $whId = 0;
                        $whCode = '';
                    }
                }

                        $pickingData = [
                            'trpacking_id' => $packing->id,
                            'trhdr_id' => $delivHdr->id,
                            'tr_type' => 'SRD',
                            'tr_code' => $headerData['tr_code'],
                            'trpacking_seq' => $packing->tr_seq,
                            'tr_seq' => DelivPicking::getNextTrSeq($packing->id),
                            'tr_seq2' => DelivPicking::getNextTrSeq($packing->id),
                            'matl_id' => $orderDtl->matl_id,
                            'matl_code' => $orderDtl->matl_code,
                            'matl_uom' => $orderDtl->matl_uom,
                            'wh_id' => $whId,
                            'wh_code' => $whCode,
                            'batch_code' => $batchCode, // Batch code dari picking asal
                            'qty' => $qtyReturn, // Qty return per batch
                            'ivt_id' => null, // Akan di-set setelah create ivt_logs
                            'reffpicking_id' => $batchDetail['reffpicking_id'] ?? null, // Reference ke picking asal
                        ];

                        // Cek apakah sudah ada picking dengan batch_code yang sama
                        $picking = DelivPicking::where('trpacking_id', $packing->id)
                            ->where('batch_code', $batchCode)
                            ->where('wh_id', $whId)
                            ->first();

                if (!$picking) {
                    $picking = DelivPicking::create($pickingData);
                } else {
                    $picking->fill($pickingData);
                    if ($picking->isDirty()) {
                        $picking->save();
                    }
                }

                // 6. Buat ivt_logs dari DelivPicking menggunakan InventoryService
                $this->inventoryService->createIvtLogFromDeliveryPicking($delivHdr, $picking, $orderDtl);
            }
        }

        // 7. Buat billing data (BillingHdr, BillingDeliv, BillingOrder) dan partner logs/balances
        $orderHdr = OrderHdr::find($headerData['id']);
        if ($orderHdr) {
            // Gunakan app() untuk avoid circular dependency
            $billingService = app(BillingService::class);
            $billingService->createBillingSalesReturn($delivHdr, $orderHdr);
        }

        return $delivHdr;
    }
    #endregion
}
