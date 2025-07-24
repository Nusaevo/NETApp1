<?php

namespace App\Services\TrdTire1;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\TrdTire1\Master\{MatlUom};
use App\Models\TrdTire1\Inventories\IvttrHdr;
use App\Models\TrdTire1\Inventories\{IvtLog, IvtBal, IvttrDtl};
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivDtl, OrderDtl, OrderHdr};

class InventoryService
{
    #region Reservation Methods

    public function addReservation(array $headerData, array $detailData)
    {
        // dd([
        //     'mode' => $mode,
        //     'qty' => $detailData['qty'] ?? null,
        //     'matl_id' => $detailData['matl_id'] ?? null,
        //     'tr_type' => $headerData['tr_type'] ?? null,
        //     'called_from' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'] ?? null,
        // ]);

        // Hitung price dan amount
        $price = 0;
        $trAmt = 0;
        $trQty = 0;
        $qty = 0;
        if ($headerData['tr_type'] === 'PO' || $headerData['tr_type'] === 'SO') {
            $trQty = $detailData['qty'];
            $qty = $trQty;
            $price = $detailData['amt_beforetax'] / $detailData['qty'];
            $trAmt = $detailData['amt_beforetax'];
        } else if ($headerData['tr_type'] === 'PD' || $headerData['tr_type'] === 'SD') {
            $trQty = $detailData['qty'];
            $qty = -$trQty;
            $orderDtl = OrderDtl::find($detailData['reffdtl_id']);
            if (!$orderDtl) {
                throw new Exception('Order Detail nomor: ' . $detailData['reffhdr_id']);
            }
            $price = $orderDtl->amt_beforetax / $orderDtl->qty;
            $trAmt = $price * $trQty;
        }            
        
        $ivtBal = IvtBal::updateOrCreate([
            'matl_id' => $detailData['matl_id'],
            'matl_uom' => $detailData['matl_uom'],
            'wh_id' => 0,
            'batch_code' => ''
        ], [
            'matl_code' => $detailData['matl_code'] ?? ''
            // qty_oh, qty_fgr, qty_fgi tidak di-set di sini agar tidak overwrite
        ]);
        if ($headerData['tr_type'] === 'PO' || $headerData['tr_type'] === 'PD') {
            $ivtBal->qty_fgr +=  ($detailData['qty']);
        } else if ($headerData['tr_type'] === 'SO' || $headerData['tr_type'] === 'SD') {
            $ivtBal->qty_fgi +=  ($detailData['qty']);
        }            
        $ivtBal->save();


        // Tentukan tr_type untuk log
        $trType = $headerData['tr_type'] . 'R';

        // Siapkan data log
        $logData = [
            'trhdr_id' => $detailData['trhdr_id'],
            'tr_type' => $trType,
            'tr_code' => $detailData['tr_code'],
            'tr_seq' => $detailData['tr_seq'] ?? 0,
            'trdtl_id' => $detailData['id'] ?? 0,
            'ivt_id' => $ivtBal->id,
            'matl_id' => $detailData['matl_id'],
            'matl_code' => $detailData['matl_code'] ?? '',
            'matl_uom' => $detailData['matl_uom'] ?? '',
            'wh_id' => 0,
            'wh_code' => '',
            'batch_code' => '',
            'reff_id' => $headerData['reff_id'] ?? 0,
            'tr_date' => $headerData['tr_date'],
            'qty' => $qty,
            'price' => $price,
            'tr_amt' => $trAmt,
            'tr_qty' => $trQty,
            'tr_desc' => 'RESERVASI ' . $headerData['tr_type'] . ' ' . $detailData['tr_code'],
            'price_cogs' => 0,
            'amt_cogs' => 0,
            'qty_running' => 0,
            'amt_running' => 0,
            'process_flag' => '',
        ];

        // Simpan log inventory
        IvtLog::create($logData);
        // return $ivtBal->id;
    }

    public function addOnhand(array $headerData, array $detailData): int
    {
        $price = 0;
        $amt = 0;
        $qty = 0;
        $trDesc = '';
        if ($headerData['tr_type'] === 'PD' || $headerData['tr_type'] === 'SD') {
            // $orderHdr = OrderHdr::find($detailData['reffhdr_id']);
            // if (!$orderHdr) {
            //     throw new Exception('Order header tidak ditemukan: ' . $detailData['reffhdr_id']);
            // }
            $orderDtl = OrderDtl::find($detailData['reffdtl_id']);
            if (!$orderDtl) {
                throw new Exception('Order detail tidak ditemukan: ' . $detailData['reffdtl_id']);
            }
            $price = $orderDtl->amt_beforetax / $orderDtl->qty;
        }

        switch ($headerData['tr_type']) {
            case 'PD': // Purchase Delivery: Tambah OH, Kurangi FGR
                $qty = $detailData['qty'];
                $amt = $price * $qty;
                $trDesc = 'DELIVERY IN ' . $detailData['tr_code'];
                break;
            case 'SD': // Sales Delivery: Kurangi OH, Kurangi FGI
                $qty = -$detailData['qty'];
                $amt = $price * $qty;
                $trDesc = 'DELIVERY OUT ' . $detailData['tr_code'];
                break;
            case 'TW':
                $qty = $detailData['qty'];
                if ($detailData['qty'] < 0) {
                    $trDesc = 'TRANSFER WH OUT ' . $detailData['tr_code'];
                } else {
                    $trDesc = 'TRANSFER WH IN ' . $detailData['tr_code'];
                }
                break;
            case 'IA':
                $qty = $detailData['qty'];
                $trDesc = 'ADJUSTMENT ' . $detailData['tr_code'];
                break;
        }

        $ivtBal = IvtBal::updateOrCreate([
            'matl_id' => $detailData['matl_id'],
            'matl_uom' => $detailData['matl_uom'],
            'wh_id' => $detailData['wh_id'],
            'batch_code' => $detailData['batch_code'],
        ],[
            'matl_code' => $detailData['matl_code'],
            'wh_code' => $detailData['wh_code'],
        ]);
        $ivtBal->qty_oh += $qty;
        $ivtBal->save();
        $detailData['ivt_id'] = $ivtBal->id;

        // Siapkan data log
        $logData = [
            'trhdr_id' => $detailData['trhdr_id'],
            'tr_type' => $detailData['tr_type'],
            'tr_code' => $detailData['tr_code'],
            'tr_seq' => $detailData['tr_seq'],
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
            'price' => $price,
            'amt' => $amt,
            'tr_desc' => $trDesc,
            'price_cogs' => 0,
            'amt_cogs' => 0,
            'qty_running' => 0,
            'amt_running' => 0,
            'process_flag' => ''
        ];
        IvtLog::create($logData);

        return $ivtBal->id;
    }

    public function delIvtLog(int $ivttrId)
    {
        // Hapus semua log inventory terkait trHdrId
        $logs = IvtLog::where('trhdr_id', $ivttrId)->get();
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

    public function addInventory(array $headerData, array $detailData): IvttrHdr
    {
        if (empty($headerData) || empty($detailData)) {
            throw new Exception('Header data or detail data is empty');
        }
        try{
            $ivttrHdr = $this->saveHeader($headerData);
            $headerData['id'] = $ivttrHdr->id;

            $this->saveDetails($headerData, $detailData);

            return $ivttrHdr;
        } catch (Exception $e) {
            throw new Exception('Error updating order: ' . $e->getMessage());
        }

    }

    public function updInventory(array $headerData, array $detailData, int $ivttrId): IvttrHdr
    {
        // Validasi headerData dan detailData
        if (empty($headerData) || empty($detailData)) {
            throw new Exception('Header data or detail data is empty');
        }
        try {
            $ivttrHdr = $this->saveHeader($headerData);
            $headerData['id'] = $ivttrHdr->id;

            $this->deleteDetails($ivttrId);

            $this->saveDetails($headerData, $detailData);

            return $ivttrHdr;
        } catch (Exception $e) {
            throw $e;
        }
 }

    public function delInventory(int $ivttrId): void
    {
        $this->deleteDetails($ivttrId);
        $this->deleteHeader($ivttrId);
    }

    #region saveHeader
    private function saveHeader(array $headerData)
    {
        if ($headerData['id']) {
            $ivttrHdr = IvttrHdr::findOrFail($headerData['id']);
            $ivttrHdr->update($headerData);
        } else {
            $ivttrHdr = IvttrHdr::create($headerData);
        }
        return $ivttrHdr;
    }
    #endregion

    private function saveDetails(array $headerData, array $detailData): array
    {
        // dd('Saving details', $headerData, $detailData);
        if (!isset($headerData['id']) || empty($headerData['id'])) {
            throw new Exception('Header ID tidak ditemukan. Pastikan header sudah tersimpan.');
        }

        $trSeq = 1;
        $savedDetails = [];
        foreach ($detailData as $detail) {
            $detail['trhdr_id'] = $headerData['id'];
            $detail['tr_type'] =  $headerData['tr_type'];
            $detail['tr_code'] = $headerData['tr_code'];

            if ($detail['tr_type'] === 'TW') {
                $detail1 = $detail;
                $detail1['tr_seq'] = -$detail['tr_seq'];
                $detail1['qty'] = -$detail['qty'];
                $savedDetail = IvttrDtl::create($detail1);
                $savedDetails = $savedDetail->toArray();
                $savedDetails['tr_seq'] = $trSeq;
                $ivtId = $this->addOnhand($headerData, $savedDetails);
                $savedDetail->ivt_id = $ivtId;
                $savedDetail->save();
                $trSeq++;

                $detail2 = $detail;
                $detail2['wh_id'] = $detail['wh_id2'];
                $detail2['wh_code'] = $detail['wh_code2'];
                $savedDetail = IvttrDtl::create($detail2);
                $savedDetails = $savedDetail->toArray();
                $savedDetails['tr_seq'] = $trSeq;
                $ivtId = $this->addOnhand($headerData, $savedDetails);
                $savedDetail->ivt_id = $ivtId;
                $savedDetail->save();
                $trSeq++;
            } else if ($detail['tr_type'] === 'IA') {
                $detail['tr_seq'] = $trSeq;
                $savedDetail = IvttrDtl::create($detail);
                $detail['id'] = $savedDetail->id; // <-- tambahkan baris ini!
                $ivtId = $this->addOnhand($headerData, $detail);
                $savedDetail->ivt_id = $ivtId;
                $savedDetail->save();
                $trSeq++;
            }


            // dd('Detail saved successfully', $savedDetail->toArray());
        }
        return $savedDetails;
    }

    private function deleteHeader(int $ivttrId)
    {
        IvttrHdr::where('id', $ivttrId)->forceDelete();
    }

    private function deleteDetails(int $ivttrId): void
    {
        // Hapus detail
        IvttrDtl::where('trhdr_id', $ivttrId)->delete();
        $this->delIvtLog($ivttrId);
    }
    #endregion
}
