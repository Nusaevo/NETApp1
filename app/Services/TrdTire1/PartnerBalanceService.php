<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Master\PartnerBal;
use App\Models\TrdTire1\Master\PartnerLog;
use Illuminate\Support\Facades\DB;

class PartnerBalanceService
{
    public function updPartnerBalance(string $mode, array $headerData)
    {
        // dd($mode, $headerData);
        // Validasi data yang diperlukan
        if (!isset($headerData['id'])) {
            throw new \Exception('Header ID (id) is required');
        }

        if ($mode === '+') {
            $Amt = $headerData['total_amt'];
        } else if ($mode === '-') {
            $Amt = -$headerData['total_amt'];
        }


        // Cari atau buat partner balance berdasarkan partner_id
        $partnerBal = PartnerBal::firstOrNew([
            'partner_id' => $headerData['partner_id']
        ]);

        // Set data partner jika baru
        if (!$partnerBal->exists) {
            $partnerBal->partner_code = $headerData['partner_code'];
            $partnerBal->amt_bal = 0;
            $partnerBal->amt_adv = 0;
            // $partnerBal->note = '';
        }

        $partnerBal->reff_id = $headerData['id'] ?? null;
        $partnerBal->reff_code = $headerData['tr_code'] ?? null;
        $partnerBal->reff_type = $headerData['tr_type'] ?? null;
        $partnerBal->amt_bal = ($partnerBal->amt_bal ?? 0) + $Amt;

        $partnerBal->save();

        $logData = [
            'tr_date' => $headerData['tr_date'],
            'trhdr_id' => $headerData['id'], // ID header wajib ada
            'tr_type' => $headerData['tr_type'],
            'tr_code' => $headerData['tr_code'],
            'tr_seq' => 0,
            'trdtl_id' => 0,
            'partner_id' => $headerData['partner_id'],
            'partner_code' => $headerData['partner_code'],
            'partnerbal_id' => $partnerBal->id,
            'amt' => $partnerBal->amt_bal,
            'tr_amt' => $headerData['total_amt'],
            'curr_id' => $headerData['curr_id'] ?? 1,
            'curr_rate' => $headerData['curr_rate'] ?? 1,
            'tr_desc' => ($mode === '+' ? 'Billing ' : 'Payment ') . $headerData['tr_type'] . ' ' . $headerData['tr_code'],
        ];
        // Selalu buat log baru untuk delivery
        PartnerLog::create($logData);
    }

    public function delPartnerLog(int $trHdrId)
    {
        // Hapus semua log partner terkait trHdrId secara permanen
        $logs = PartnerLog::where('trhdr_id', $trHdrId)->get();

        foreach ($logs as $log) {
            // Update PartnerBal jika perlu
            $partnerBal = PartnerBal::find($log->partnerbal_id);
            if ($partnerBal) {
                $partnerBal->amt_bal = ($partnerBal->amt_bal ?? 0) - $log->amt;
                $partnerBal->save();
            }
            // Hapus log secara permanen
            $log->forceDelete();
        }
    }
}
