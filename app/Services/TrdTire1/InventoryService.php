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

    public function addReservation(array $headerData, array $detailData): IvtLog
    {
        // Buat atau update IvtBal untuk order (hanya berdasarkan matl_id)
        $ivtBal = IvtBal::where([
            'matl_id' => $detailData['matl_id'],
            'wh_id' => 0,
            'batch_code' => ''
        ])->first();

        if (!$ivtBal) {
            $ivtBal = new IvtBal([
                'matl_id' => $detailData['matl_id'],
                'wh_id' => 0,
                'batch_code' => '',
                'qty_oh' => 0,
                'qty_fgr' => 0,
                'qty_fgi' => 0
            ]);
        }

        // Update qty berdasarkan tr_type
        switch ($headerData['tr_type']) {
            case 'SO': // Sales Order: Tambah FGI
                $ivtBal->qty_fgi = ($ivtBal->qty_fgi ?? 0) + ($detailData['tr_qty']);
                break;
            case 'PO': // Purchase Order: Tambah FGR
                $ivtBal->qty_fgr = ($ivtBal->qty_fgr ?? 0) + ($detailData['tr_qty']);
                break;
        }
        $ivtBal->save();

        // Hitung price dan amount
        $price = $detailData['price'] ?? 0;
        $discPct = ($detailData['disc_pct'] ?? 0) / 100;
        $qty = $detailData['qty'] ?? 0;
        $trAmt = $price * $qty * (1 - $discPct);

        // Siapkan data log
        $logData = [
            'trhdr_id' => $detailData['trhdr_id'],
            'tr_type' => $headerData['tr_type'],
            'tr_code' => $detailData['tr_code'],
            'tr_seq' => $detailData['tr_seq'] ?? 0,
            'trdtl_id' => $detailData['id'],
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
            'tr_qty' => $detailData['tr_qty'] ?? 0,
            'tr_desc' => 'RESERVASI ' . $headerData['tr_type'] . ' ' . $detailData['tr_code'],
            'price_cogs' => 0,
            'amt_cogs' => 0,
            'qty_running' => 0,
            'amt_running' => 0,
            'process_flag' => $headerData['process_flag'] ?? ''
        ];

        // Simpan log inventory
        return $this->saveIvtLog($logData);
    }

    public function delReservation(array $headerData, array $detailData): void
    {
        // Update ivtBal yang sudah ada
        $ivtBal = IvtBal::where([
            'matl_id' => $detailData['matl_id'],
            'wh_id' => 0,
            'batch_code' => '',
        ])->first();

        if ($ivtBal) {
            switch ($detailData['tr_type']) {
                case 'SO': // Sales Order: Kurangi FGI
                    $ivtBal->qty_fgi = ($ivtBal->qty_fgi) - ($detailData['qty']);
                    break;
                case 'PO': // Purchase Order: Kurangi FGR
                    $ivtBal->qty_fgr = ($ivtBal->qty_fgr) - ($detailData['qty']);
                    break;
            }
            $ivtBal->save();

            // Ambil data OrderDtl untuk mendapatkan disc_pct
            $orderDtl = OrderDtl::find($detailData['reffdtl_id']);
            $discPct = $orderDtl ? $orderDtl->disc_pct : 0;

            // Ambil price dari MatlUom berdasarkan matl_id
            $matlUom = MatlUom::where('matl_id', $detailData['matl_id'])->first();
            $price = $matlUom ? $matlUom->selling_price : 0;

            // Hitung price setelah diskon
            $price = $price * (1 - ($discPct / 100));
            $trAmt = $price * $detailData['qty'];

            // Siapkan data log
            $logData = [
                'trhdr_id' => $detailData['trhdr_id'],
                'tr_type' => $detailData['tr_type'],
                'tr_code' => $detailData['tr_code'],
                'tr_seq' => $detailData['tr_seq'] ?? 0,
                'trdtl_id' => $detailData['id'],
                'ivt_id' => $ivtBal->id,
                'matl_id' => $detailData['matl_id'],
                'matl_code' => $detailData['matl_code'] ?? '',
                'matl_uom' => $detailData['matl_uom'] ?? '',
                'wh_id' => 0,
                'wh_code' => '',
                'batch_code' => '',
                'reff_id' => 0,
                'tr_date' => $headerData['tr_date'],
                'qty' => $detailData['qty'],
                'price' => $price,
                'tr_amt' => $trAmt,
                'tr_qty' => -$detailData['qty'],
                'tr_desc' => 'DELIV RESERVASI ' . $detailData['tr_type'] . ' ' . $detailData['tr_code'],
                'price_cogs' => 0,
                'amt_cogs' => 0,
                'qty_running' => 0,
                'amt_running' => 0,
                'process_flag' => $headerData['process_flag'] ?? ''
            ];

            // Simpan log inventory
            $this->saveIvtLog($logData);
        }
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
                'wh_id' => $detailData->wh_id,
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
            'tr_type' => $detailData->tr_type,
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

    public function delOnhand(DelivHdr $headerData, DelivDtl $detailData): void
    {
        $ivtBal = IvtBal::where([
            'matl_id' => $detailData->matl_id,
            'wh_id' => $detailData->wh_id,
            'batch_code' => $detailData->batch_code
        ])->first();

        if ($ivtBal) {
            switch ($headerData->tr_type) {
                case 'PD': // Purchase Delivery: Kurangi OH, Tambah FGR
                    $ivtBal->qty_oh = ($ivtBal->qty_oh) - ($detailData->qty);
                    $ivtBal->qty_fgr = ($ivtBal->qty_fgr) + ($detailData->qty);
                    break;
                case 'SD': // Sales Delivery: Tambah OH, Tambah FGI
                    $ivtBal->qty_oh = ($ivtBal->qty_oh) + ($detailData->qty);
                    $ivtBal->qty_fgi = ($ivtBal->qty_fgi) + ($detailData->qty);
                    break;
            }
            $ivtBal->save();

            // Siapkan data log untuk penghapusan
            $logData = [
                'trhdr_id' => $detailData->trhdr_id,
                'tr_type' => $detailData->tr_type,
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
                'tr_date' => $headerData->tr_date,
                'qty' => -$detailData->qty, // Negatif karena penghapusan
                'price' => $detailData->price ?? 0,
                'tr_amt' => -($detailData->amt ?? 0), // Negatif karena penghapusan
                'tr_qty' => -$detailData->qty, // Negatif karena penghapusan
                'tr_desc' => 'HAPUS DELIVERY ' . $detailData->tr_type . ' ' . $detailData->tr_code,
                'price_cogs' => 0,
                'amt_cogs' => 0,
                'qty_running' => 0,
                'amt_running' => 0,
                'process_flag' => $headerData->process_flag ?? ''
            ];

            // Simpan log penghapusan
            $this->saveIvtLog($logData);
        }
    }

    private function saveIvtLog(array $logData): IvtLog
    {
        return IvtLog::create($logData);
    }
    #endregion
}
