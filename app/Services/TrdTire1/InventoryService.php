<?php

namespace App\Services\TrdTire1;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\TrdTire1\Master\{MatlUom, Material};
use App\Models\TrdTire1\Inventories\IvttrHdr;
use App\Models\TrdTire1\Inventories\{IvtLog, IvtBal, IvttrDtl};
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivDtl, OrderDtl, OrderHdr};

class InventoryService
{
    private function isMaterialJasa(int $matlId): bool
    {
        $material = Material::find($matlId);
        return $material && strtoupper(trim($material->type_code)) === 'S';
    }

    #endregion

    #region Reservation Methods

    public function addReservation(array $headerData, array $detailData)
    {
        // Skip reservation jika material category adalah JASA
        if ($this->isMaterialJasa($detailData['matl_id'])) {
            return;
        }

        $trQty = $detailData['qty'];
        $qty = 0;
        if ($headerData['tr_type'] === 'PO' || $headerData['tr_type'] === 'SO') {
            $qty = $trQty;
            $priceBeforeTax = $detailData['price_beforetax'];
        } else if ($headerData['tr_type'] === 'PD' || $headerData['tr_type'] === 'SD') {
            $qty = -$trQty;
            $orderDtl = OrderDtl::find($detailData['reffdtl_id']);
            $priceBeforeTax = $orderDtl->price_beforetax;
        }
        // dd($detailData);
        $ivtBal = IvtBal::updateOrCreate(
            [
                'matl_id' => $detailData['matl_id'],
                'matl_uom' => $detailData['matl_uom'],
                'wh_id' => 0,
                'batch_code' => ''
            ],
            [
                'matl_code' => $detailData['matl_code'] ?? ''
            ]
        );
        if ($headerData['tr_type'] === 'PO' || $headerData['tr_type'] === 'PD') {
            $ivtBal->qty_fgr +=  $qty;
        } else if ($headerData['tr_type'] === 'SO' || $headerData['tr_type'] === 'SD') {
            $ivtBal->qty_fgi +=  $qty;
        }
        $ivtBal->save();
        // dd($ivtBal);

        // Tentukan tr_type untuk log
        $trType = $headerData['tr_type'] . 'R';
        $logData = [
            'tr_date' => $headerData['tr_date'],
            'trdtl_id' => $detailData['id'],
            'trhdr_id' => $detailData['trhdr_id'],
            'tr_type' => $trType,
            'tr_code' => $detailData['tr_code'],
            'tr_seq' => $detailData['tr_seq'],
            'tr_seq2' => $detailData['tr_seq2'] ?? 0,
            'ivt_id' => $ivtBal->id,
            'matl_id' => $detailData['matl_id'],
            'matl_code' => $detailData['matl_code'] ?? '',
            'matl_uom' => $detailData['matl_uom'] ?? '',
            'wh_id' => 0,
            'wh_code' => '',
            'batch_code' => '',
            'tr_qty' => $trQty,
            'qty' => $qty,
            'price_beforetax' => $priceBeforeTax,
            'price_cogs' => 0,
            'qty_running' => 0,
            'tr_desc' => 'RESERVASI ' . $headerData['tr_type'] . ' ' . $detailData['tr_code'],
            'reff_id' => 0,
            'process_flag' => '',
        ];
        // dd($logData);

        // Simpan log inventory
        IvtLog::create($logData);
        // return $ivtBal->id;
    }

    public function addOnhand(array $headerData, array $detailData): int
    {
        // Skip onhand jika material category adalah JASA
        if ($this->isMaterialJasa($detailData['matl_id'])) {
            return 0;
        }

        // dd($detailData);
        $price = 0;
        $qty = 0;
        $trDesc = '';
        if ($headerData['tr_type'] === 'PD' || $headerData['tr_type'] === 'SD') {
            $orderDtl = OrderDtl::find($detailData['reffdtl_id']);
            if (!$orderDtl) {
                throw new Exception('Order detail tidak ditemukan: ' . $detailData['reffdtl_id']);
            }
            $price = $orderDtl->price_beforetax;
        }

        switch ($headerData['tr_type']) {
            case 'PD': // Purchase Delivery: Tambah OH, Kurangi FGR
                $qty = $detailData['qty'];
                // $amt = $price * $qty;
                $trDesc = 'DELIVERY IN ' . ($detailData['wh_code'] ?? '') . ' ' . $headerData['tr_code'];
                break;
            case 'SD': // Sales Delivery: Kurangi OH, Kurangi FGI
                $qty = -$detailData['qty'];
                // $amt = $price * $qty;
                $trDesc = 'DELIVERY OUT ' . ($detailData['wh_code'] ?? '') . ' ' . $headerData['tr_code'];
                break;
            case 'TW':
                $qty = $detailData['qty'];
                if ($detailData['qty'] < 0) {
                    $trDesc = 'TRANSFER WH OUT ' . ($detailData['wh_code'] ?? '') . ' ' . $headerData['tr_code'];
                } else {
                    $trDesc = 'TRANSFER WH IN ' . ($detailData['wh_code2'] ?? '') . ' ' . $headerData['tr_code'];
                }
                break;
            case 'IA':
                $qty = $detailData['qty'];
                $trDesc = 'ADJUSTMENT ' . ($detailData['wh_code'] ?? '') . ' ' . $headerData['tr_code'];
                break;
        }

        $ivtBal = IvtBal::updateOrCreate([
            'matl_id' => $detailData['matl_id'],
            'matl_uom' => $detailData['matl_uom'],
            'wh_id' => $detailData['wh_id'],
            'batch_code' => $detailData['batch_code'],
        ], [
            'matl_code' => $detailData['matl_code'],
            'wh_code' => $detailData['wh_code'],
        ]);
        $ivtBal->qty_oh += $qty;
        $ivtBal->save();
        $detailData['ivt_id'] = $ivtBal->id;

        // dd($detailData);
        // Siapkan data log
        $logData = [
            'trhdr_id' => $headerData['id'],
            'tr_type' => $headerData['tr_type'],
            'tr_code' => $headerData['tr_code'],
            'tr_seq' => $detailData['tr_seq'],
            'tr_seq2' => $detailData['tr_seq2'] ?? 0,
            'trdtl_id' => $detailData['id'],
            'ivt_id' => $ivtBal->id,
            'matl_id' => $detailData['matl_id'],
            'matl_code' => $detailData['matl_code'],
            'matl_uom' => $detailData['matl_uom'],
            'wh_id' => $detailData['wh_id'],
            'wh_code' => $detailData['wh_code'],
            'batch_code' => $detailData['batch_code'],
            'reff_id' => $headerData['reff_id'] ?? 0,
            'tr_date' => $headerData['tr_date'],
            'tr_qty' => $detailData['qty'],
            'qty' => $qty,
            'price_beforetax' => $price,
            'tr_desc' => $trDesc,
            'price_cogs' => 0,
            'qty_running' => 0,
            'process_flag' => ''
        ];
        $ivtLog = IvtLog::create($logData);
        return $ivtBal->id;
    }

    public function delIvtLog(string $trType, int $trHdrId, ?int $trDtlId = null)
    {
        // Hapus log inventory berdasarkan trHdrId dan opsional trdtlId
        if ($trDtlId !== null) {
            // Jika ada trdtlId, cari berdasarkan trType, trhdrId, dan trdtlId untuk memastikan log yang tepat
            $query = IvtLog::where('tr_type', $trType)
                ->where('trhdr_id', $trHdrId)
                ->where('trdtl_id', $trDtlId);
        } else {
            // Jika tidak ada trdtlId, cari berdasarkan trType dan trhdrId
            $query = IvtLog::where('tr_type', $trType)
                ->where('trhdr_id', $trHdrId);
        }

        $logs = $query->get();

        foreach ($logs as $log) {
            $ivtBal = IvtBal::find($log->ivt_id);
            if ($ivtBal) {
                if ($log->tr_type === 'POR' || $log->tr_type === 'PDR') {
                    $ivtBal->qty_fgr -= $log->qty;
                } else if ($log->tr_type === 'SOR' || $log->tr_type === 'SDR') {
                    $ivtBal->qty_fgi -= $log->qty;
                } else {
                    $ivtBal->qty_oh -= $log->qty;
                }
                $ivtBal->save();
            }
            $log->delete();
        }
    }

    public function saveInventoryTrx(array $headerData, array $detailData)
    {
        if (empty($headerData) || empty($detailData)) {
            throw new Exception('Header data or detail data is empty');
        }
        try {
            $header = $this->saveHeaderTrx($headerData);
            $headerData['id'] = $header->id;

            $detail = $this->saveDetailTrx($headerData, $detailData);

            return [
                'header' => $header,
                'detail' => $detail,
            ];

        } catch (Exception $e) {
            throw new Exception('Error updating order: ' . $e->getMessage());
        }
    }

    #region saveHeader
    private function saveHeaderTrx(array $headerData)
    {
        if (!isset($headerData['id']) || empty($headerData['id'])) {
            $ivttrHdr = IvttrHdr::create($headerData);
        } else {
            $ivttrHdr = IvttrHdr::findOrFail($headerData['id']);
            $ivttrHdr->fill($headerData);
            if ($ivttrHdr->isDirty()) {
                $ivttrHdr->save();
            }
        }
        return $ivttrHdr;
    }
    #endregion

    private function saveDetailTrx(array $headerData, array $detailData)
    {
        // Hanya ada ADD tidak diperbolehkan UPDATE

        foreach ($detailData as &$detail) {
            $detail['trhdr_id'] = $headerData['id'];
            $detail['tr_type'] =  $headerData['tr_type'];
            $detail['tr_code'] = $headerData['tr_code'];

            if ($detail['tr_type'] === 'IA') {
                if (!isset($detail['id']) || empty($detail['id'])) {
                    $detail['tr_seq'] = IvttrDtl::getNextTrSeq($headerData['id']);

                    $ivttrDtl = new IvttrDtl();
                    $ivttrDtl->fill($detail);
                    $ivttrDtl->save();

                    // Set id pada detail untuk logging dan penentuan ivt_id
                    $detail['id'] = $ivttrDtl->id;
                    $ivtId = $this->addOnhand($headerData, $detail);
                    // Update ivt_id pada detail yang baru dibuat
                    $ivttrDtl->ivt_id = $ivtId;
                    $ivttrDtl->save();
                }
            } else if ($detail['tr_type'] === 'TW') {
                if (!isset($detail['id']) || empty($detail['id'])) {
                    $detail['tr_seq'] = IvttrDtl::getNextTrSeq($headerData['id']);

                    // Detail OUT (tr_seq negatif, qty negatif)
                    $detail1 = $detail;
                    $detail1['tr_seq'] = -$detail['tr_seq'];
                    $detail1['qty'] = -$detail['qty'];
                    $ivttrDtl1 = new IvttrDtl();
                    $ivttrDtl1->fill($detail1);
                    $ivttrDtl1->save();
                    // Pastikan id terpasang di detail untuk addOnhand
                    $detail1['id'] = $ivttrDtl1->id;
                    $detail['id'] = $ivttrDtl1->id;
                    $ivtId1 = $this->addOnhand($headerData, $detail1);
                    $ivttrDtl1->ivt_id = $ivtId1;
                    $ivttrDtl1->save();

                    // Detail IN (ke gudang tujuan)
                    $detail2 = $detail;
                    $detail2['wh_id'] = $detail['wh_id2'];
                    $detail2['wh_code'] = $detail['wh_code2'];
                    $ivttrDtl2 = new IvttrDtl();
                    $ivttrDtl2->fill($detail2);
                    $ivttrDtl2->save();
                    $detail2['id'] = $ivttrDtl2->id;
                    $detail['id2'] = $ivttrDtl2->id;
                    $ivtId2 = $this->addOnhand($headerData, $detail2);
                    $ivttrDtl2->ivt_id = $ivtId2;
                    $ivttrDtl2->save();
               }
            }
        }
        unset($detail);

        return true;

    }

    public function delInventory(string $trType, int $ivttrId): void
    {
        $this->deleteDetails($trType, $ivttrId);
        $this->deleteHeader($ivttrId);
    }

    private function deleteHeader(int $ivttrId)
    {
        IvttrHdr::where('id', $ivttrId)->forceDelete();
    }

    private function deleteDetails(string $trType, int $ivttrId): void
    {
        $this->delIvtLog($trType, $ivttrId);
        IvttrDtl::where('trhdr_id', $ivttrId)->delete();
    }
    #endregion
}
