<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Inventories\{IvtLog, IvtBal};
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryService
{
    public function addReservation(array $headerData, array $detailData): IvtLog
    {
        return DB::transaction(function () use ($headerData, $detailData) {
            // Buat atau update IvtBal terlebih dahulu
            $ivtBal = $this->createOrUpdateIvtBal($detailData);

            // Simpan log inventory dengan ivt_id dari IvtBal
            $ivtLog = $this->saveIvtLog($headerData, $detailData, $ivtBal->id);

            return $ivtLog;
        });
    }

    public function modReservation(int $ivtLogId, array $headerData, array $detailData): IvtLog
    {
        return DB::transaction(function () use ($ivtLogId, $headerData, $detailData) {
            // Hapus log inventory lama
            $oldIvtLog = IvtLog::findOrFail($ivtLogId);
            $this->reverseIvtBal($oldIvtLog);
            $oldIvtLog->delete();

            // Buat atau update IvtBal
            $ivtBal = $this->createOrUpdateIvtBal($detailData);

            // Simpan log inventory baru dengan ivt_id dari IvtBal
            $ivtLog = $this->saveIvtLog($headerData, $detailData, $ivtBal->id);

            return $ivtLog;
        });
    }

    public function delReservation(int $ivtLogId): bool
    {
        return DB::transaction(function () use ($ivtLogId) {
            $ivtLog = IvtLog::findOrFail($ivtLogId);

            // Reverse saldo inventory
            $this->reverseIvtBal($ivtLog);

            // Hapus log inventory
            return (bool) $ivtLog->delete();
        });
    }

    private function createOrUpdateIvtBal(array $detailData): IvtBal
    {
        $ivtBal = IvtBal::firstOrNew([
            'matl_id' => $detailData['matl_id'],
            'wh_id' => $detailData['wh_id'] ?? null,
            'batch_code' => $detailData['batch_code'] ?? ''
        ]);

        // Update quantity berdasarkan tr_type
        if ($detailData['tr_type'] === 'SO') {
            $ivtBal->qty_fgi = ($ivtBal->qty_fgi ?? 0) + ($detailData['tr_qty'] ?? 0); // Quantity reserved untuk SO
        } else if ($detailData['tr_type'] === 'PO') {
            $ivtBal->qty_fgr = ($ivtBal->qty_fgr ?? 0) + ($detailData['tr_qty'] ?? 0); // Quantity in transit untuk PO
        }

        $ivtBal->matl_code = $detailData['matl_code'];
        $ivtBal->matl_uom = $detailData['matl_uom'];
        $ivtBal->wh_code = $detailData['wh_code'] ?? '';

        $ivtBal->save();
        return $ivtBal;
    }

    private function saveIvtLog(array $headerData, array $detailData, int $ivtId): IvtLog
    {
        $logData = [
            'trhdr_id' => $headerData['id'],
            'tr_type' => $headerData['tr_type'],
            'tr_code' => $headerData['tr_code'],
            'tr_seq' => $detailData['tr_seq'],
            'trdtl_id' => $detailData['id'],
            'ivt_id' => $ivtId,
            'matl_id' => $detailData['matl_id'],
            'matl_code' => $detailData['matl_code'],
            'matl_uom' => $detailData['matl_uom'],
            'wh_id' => $headerData['wh_id'] ?? null,
            'wh_code' => $headerData['wh_code'] ?? '',
            'batch_code' => $detailData['batch_code'] ?? '',
            'reff_id' => $headerData['reff_id'] ?? null,
            'tr_date' => $headerData['tr_date'],
            'qty' => $detailData['qty'],
            'price' => $detailData['price'],
            'tr_amt' => $detailData['tr_amt'],
            'tr_qty' => $detailData['tr_qty'],
            'tr_desc' => 'RESERVASI ' . $headerData['tr_type'] . ' ' . $headerData['tr_code'],
            'price_cogs' => $detailData['price_cogs'] ?? 0,
            'amt_cogs' => $detailData['amt_cogs'] ?? 0,
            'qty_running' => $detailData['qty_running'] ?? 0,
            'amt_running' => $detailData['amt_running'] ?? 0,
            'process_flag' => $headerData['process_flag'] ?? '',
        ];

        return IvtLog::create($logData);
    }

    private function reverseIvtBal(IvtLog $ivtLog): void
    {
        $ivtBal = IvtBal::where([
            'matl_id' => $ivtLog->matl_id,
            'wh_id' => $ivtLog->wh_id ?? null,
            'batch_code' => $ivtLog->batch_code
        ])->first();

        if ($ivtBal) {
            // Reverse quantity berdasarkan tr_type
            if ($ivtLog->tr_type === 'SO') {
                $ivtBal->qty_fgi = $ivtBal->qty_fgi - ($ivtLog->tr_qty ?? 0);
            } else if ($ivtLog->tr_type === 'PO') {
                $ivtBal->qty_fgr = $ivtBal->qty_fgr - ($ivtLog->tr_qty ?? 0);
            }
            $ivtBal->save();
        }
    }
}
