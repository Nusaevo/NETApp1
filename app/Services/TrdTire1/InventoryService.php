<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Inventories\{IvtLog, IvtBal};
use App\Models\TrdTire1\Transaction\{DelivHdr, DelivDtl, OrderDtl};
use App\Models\TrdTire1\Master\{MatlUom};
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryService
{
    #region Reservation Methods

    public function addReservation(string $mode, array $headerData, array $detailData)
    {
        // Hitung price dan amount
        $price = 0;
        $trAmt = 0;
        $trQty = 0;
        $qty = $detailData['qty'] ?? 0;

        if (isset($detailData['reffdtl_id'])) {
            // Case Delivery: Ambil data dari OrderDtl
            $orderDtl = OrderDtl::find($detailData['reffdtl_id']);
            if (!$orderDtl) {
                throw new Exception('Order detail not found for reffdtl_id: ' . $detailData['reffdtl_id']);
            }
            $price = $orderDtl->price * (1 - ($orderDtl->disc_pct / 100));
        } else {
            // Case Order: Gunakan data langsung dari detailData
            $price = $detailData['price'] * (1 - ($detailData['disc_pct'] / 100));
        }

        if ($mode === '+') {
            $trAmt = $price * $qty;
            $trQty = $qty;
        } else if ($mode === '-'){
            $trAmt = $price * -$qty;
            $trQty = -$qty;
        }

        // Buat atau update IvtBal untuk order (hanya berdasarkan matl_id)
        $ivtBal = IvtBal::where([
            'matl_id' => $detailData['matl_id'],
            'wh_id' => 0,
            'batch_code' => ''
        ])->first();

        if (!$ivtBal) {
            $ivtBal = new IvtBal([
                'matl_id' => $detailData['matl_id'],
                'matl_code' => $detailData['matl_code'] ?? '',
                'matl_uom' => $detailData['matl_uom'] ?? '',
                'wh_id' => 0,
                'batch_code' => '',
                'qty_oh' => 0,
                'qty_fgr' => 0,
                'qty_fgi' => 0
            ]);
        }

        // dibungkus mode + dan -
        if ($headerData) {
            if ($mode === '+') {
                switch ($headerData['tr_type']) {
                    case 'SO': // Sales Order: Tambah FGI
                        $ivtBal->qty_fgi = ($ivtBal->qty_fgi ?? 0) + ($detailData['qty']);
                        break;
                    case 'PO': // Purchase Order: Tambah FGR
                        $ivtBal->qty_fgr = ($ivtBal->qty_fgr ?? 0) + ($detailData['qty']);
                        break;
                }
            } else if ($mode === '-') {
                switch ($headerData['tr_type']) {
                    case 'SO': // Sales Order: Tambah FGI
                        $ivtBal->qty_fgi = ($ivtBal->qty_fgi ?? 0) - ($detailData['qty']);
                        break;
                    case 'PO': // Purchase Order: Tambah FGR
                        $ivtBal->qty_fgr = ($ivtBal->qty_fgr ?? 0) - ($detailData['qty']);
                        break;
                }
            }
            $ivtBal->save();
        }

        // Tentukan tr_type untuk log
        $trType = $headerData['tr_type'];
        // Tambah R hanya jika ini adalah reservasi dari delivery
        if (isset($detailData['reffdtl_id'])) {
            $trType .= 'R';
        }

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
            'reff_id' => $headerData['reff_id'] ?? null,
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
            'process_flag' => $headerData['process_flag'] ?? ''
        ];

        // Simpan log inventory
        IvtLog::create($logData);
    }


    public function addOnhand(array $headerData, DelivDtl $detailData): void
    {
        // Update ivtBal untuk delivery (berdasarkan matl_id, wh_id, dan batch_code)
        $ivtBal = IvtBal::where([
            'matl_id' => $detailData->matl_id,
            'wh_id' => $detailData->wh_id,
            'batch_code' => $detailData->batch_code,
        ])->first();

        if (!$ivtBal) {
            $ivtBal = new IvtBal([
                'matl_id' => $detailData->matl_id,
                'wh_code' => $detailData->wh_code,
                'wh_id' => $detailData->wh_id,
                'matl_uom' => $detailData->matl_uom,
                'batch_code' => $detailData->batch_code
            ]);
        }

        switch ($headerData['tr_type']) {
            case 'PD': // Purchase Delivery: Tambah OH, Kurangi FGR
                $ivtBal->qty_oh = ($ivtBal->qty_oh ?? 0) + ($detailData->qty);
                $trQty = $detailData->qty;
                break;
            case 'SD': // Sales Delivery: Kurangi OH, Kurangi FGI
                $ivtBal->qty_oh = ($ivtBal->qty_oh ?? 0) - ($detailData->qty);
                $trQty = -$detailData->qty;
                break;
        }
        $ivtBal->save();

        // Ambil data OrderDtl untuk mendapatkan disc_pct
        $orderDtl = OrderDtl::find($detailData->reffdtl_id);
        $discPct = $orderDtl ? $orderDtl->disc_pct : 0;

        // Ambil price dari MatlUom berdasarkan matl_id
        $matlUom = MatlUom::where('matl_id', $detailData->matl_id)->first();
        $price = $matlUom ? $matlUom->selling_price : 0;

        // Hitung price setelah diskon
        $price = $price * (1 - ($discPct / 100));
        $trAmt = $price * $detailData->qty;

        $logData = [
            'trhdr_id' => $detailData->trhdr_id,
            'tr_type' => $detailData->tr_type . 'R', // Selalu tambah R untuk onhand delivery
            'tr_code' => $detailData->tr_code,
            'tr_seq' => $detailData->tr_seq,
            'trdtl_id' => $detailData->id,
            'ivt_id' => $ivtBal->id,
            'matl_id' => $detailData->matl_id,
            'matl_code' => $detailData->matl_code,
            'matl_uom' => $detailData->matl_uom,
            'wh_id' => $detailData->wh_id,
            'wh_code' => $detailData->wh_code,
            'batch_code' => $detailData->batch_code,
            'tr_date' => $headerData['tr_date'],
            'qty' => $detailData->qty,
            'price' => $price,
            'tr_amt' => $trAmt,
            'tr_qty' => $trQty,
            'tr_desc' => 'DELIVERY ' . $detailData->tr_type . ' ' . $detailData->tr_code,
            'price_cogs' => 0,
            'amt_cogs' => 0,
            'qty_running' => 0,
            'amt_running' => 0,
            'process_flag' => $headerData['process_flag'] ?? ''
        ];
        // Selalu buat log baru untuk delivery
        IvtLog::create($logData);
    }
    public function delIvtLog(int $trHdrId)
    {
        // Hapus semua log inventory terkait trHdrId
        $logs = IvtLog::where('trhdr_id', $trHdrId)->get();
        foreach ($logs as $log) {
            // Hapus log inventory
            $log->delete();

            // Update IvtBal jika perlu
            $ivtBal = IvtBal::find($log->ivt_id);
            if ($ivtBal) {
                switch ($log->tr_type) {
                    case 'SO': // Sales Order: Kurangi FGI
                        $ivtBal->qty_fgi -= $log->tr_qty;
                        break;
                    case 'PO': // Purchase Order: Kurangi FGR
                        $ivtBal->qty_fgr -= $log->tr_qty;
                        break;
                    case 'SD': // Sales Delivery: Kurangi QOH
                        $ivtBal->qty_oh -= $log->tr_qty;
                        break;
                    case 'PD': // Purchase Delivery: Kurangi QOH
                        $ivtBal->qty_oh -= $log->tr_qty;
                        break;
                }
                $ivtBal->save();
            }
        }

    }
    #endregion
}
