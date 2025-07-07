<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Master\PartnerBal;
use App\Models\TrdTire1\Master\PartnerLog;
use Illuminate\Support\Facades\{DB, Log};

class PartnerBalanceService
{
    public function updFromBilling(string $mode, array $headerData)
    {
        if ($mode === '+') {
            $amtBal = $headerData['amt'];
        } else if ($mode === '-') {
            $amtBal = -$headerData['amt'];
        }
        // dd($amtBal);
        // Cari atau buat partner balance berdasarkan partner_id
        $partnerBal = PartnerBal::updateOrCreate(
            [
                'partner_id' => $headerData['partner_id'],
                'reff_id' => $headerData['reff_id'],
            ],
            [
                'partner_code' => $headerData['partner_code'],
                'reff_type' => $headerData['reff_type'],
                'reff_code' => $headerData['reff_code'],
                'amt_bal' => 0,
                'amt_adv' => 0,
                'note' => '',
            ]
        );
        $partnerBal = null;
        // dd($headerData);
        $partnerBal = PartnerBal::where([
            'partner_id' => $headerData['partner_id'],
            'reff_id' => $headerData['reff_id']
        ])->first();

        $partnerBal->amt_bal = $amtBal + $partnerBal->amt_bal;
        $partnerBal->save();

        $logData = [
            'tr_date' => $headerData['tr_date'],
            'trhdr_id' => $headerData['id'],
            'tr_type' => $headerData['tr_type'],
            'tr_code' => $headerData['tr_code'],
            'reff_id' => $headerData['reff_id'],
            'reff_type' => $headerData['reff_type'],
            'reff_code' => $headerData['reff_code'],
            'tr_seq' => 0,
            'trdtl_id' => 0,
            'partner_id' => $headerData['partner_id'],
            'partner_code' => $headerData['partner_code'],
            'partnerbal_id' => $partnerBal->id,
            'curr_id' => $headerData['curr_id'],
            'amt' => $amtBal,
            'tr_amt' => $headerData['amt'],
            'tr_desc' => 'Billing ' . $headerData['tr_type'] . ' ' . $headerData['tr_code'],
        ];
        // Selalu buat log baru untuk delivery
        // dd($logData, $amtBal);
        PartnerLog::create($logData);
        // dd($tes);
        return $partnerBal->id;
    }

    public function updFromPayment(string $mode, array $headerData, array $detailData)
    {
        // Debug: log data yang diterima
        Log::debug('PartnerBalanceService::updFromPayment called', [
            'mode' => $mode,
            'headerData_keys' => array_keys($headerData),
            'detailData_keys' => array_keys($detailData),
            'headerData' => $headerData,
            'detailData' => $detailData
        ]);

        // dd($mode, $headerData);
        if (!isset($headerData['id'])) {
            throw new \Exception('Header ID (id) is required');
        }

        if ($mode === '+') {
            $amtBal = $detailData['amt'];
        } else if ($mode === '-') {
            $amtBal = -$detailData['amt'];
        }

        // Validasi field yang diperlukan
        $requiredHeaderFields = ['id', 'partner_id', 'partner_code', 'tr_type', 'tr_code', 'tr_date'];
        $requiredDetailFields = ['amt', 'tr_seq', 'id'];

        foreach ($requiredHeaderFields as $field) {
            if (!isset($headerData[$field])) {
                throw new \Exception("Missing required header field: {$field}");
            }
        }

        foreach ($requiredDetailFields as $field) {
            if (!isset($detailData[$field])) {
                throw new \Exception("Missing required detail field: {$field}");
            }
        }

        // Untuk payment detail, perlu billhdr_id, billhdrtr_type, billhdrtr_code
        if (isset($detailData['billhdr_id'])) {
            $reffId = $detailData['billhdr_id'];
            $reffType = $detailData['billhdrtr_type'] ?? '';
            $reffCode = $detailData['billhdrtr_code'] ?? '';
        } else {
            // Fallback untuk kasus lain
            $reffId = $detailData['reff_id'] ?? $headerData['id'];
            $reffType = $detailData['reff_type'] ?? $headerData['tr_type'];
            $reffCode = $detailData['reff_code'] ?? $headerData['tr_code'];
        }

        // Cari atau buat partner balance berdasarkan partner_id
        $partnerBal = PartnerBal::updateOrCreate(
            [
                'partner_id' => $headerData['partner_id'],
                'reff_id' => $reffId,
            ],
            [
                'partner_code' => $headerData['partner_code'],
                'reff_type' => $reffType,
                'reff_code' => $reffCode,
                'amt_bal' => 0,
                'amt_adv' => 0,
                'note' => '',
            ]
        );

        $partnerBal->amt_bal += $amtBal;
        $partnerBal->save();
        $partnerBal->refresh();

        $logData = [
            'tr_date' => $headerData['tr_date'],
            'trhdr_id' => $headerData['id'],
            'tr_type' => $headerData['tr_type'],
            'tr_code' => $headerData['tr_code'],
            'tr_seq' => $detailData['tr_seq'],
            'trdtl_id' => $detailData['id'],
            'partner_id' => $headerData['partner_id'],
            'partner_code' => $headerData['partner_code'],
            'partnerbal_id' => $partnerBal->id,
            'amt' => $amtBal,
            'tr_amt' => $detailData['amt'],
            'tr_desc' => 'Payment ' . $headerData['tr_type'] . ' ' . $headerData['tr_code'],
        ];
        // Selalu buat log baru untuk delivery
        PartnerLog::create($logData);
        // dd($logData);
        return $partnerBal->id;
    }

    public function delPartnerLog(int $trHdrId)
    {
        // Hapus semua log partner terkait trHdrId secara permanen
        $logs = PartnerLog::where('trhdr_id','=', $trHdrId)->get();

        foreach ($logs as $log) {
            // Update PartnerBal jika perlu
            $partnerBal = PartnerBal::where('id', '=',$log->partnerbal_id)->first();
            // dd($partnerBal);
            if ($partnerBal) {
                if ($log->tr_type === 'ARPA') {
                    // Jika log adalah untuk pembayaran, kurangi dari amt_adv
                    $partnerBal->amt_adv = ($partnerBal->amt_adv ?? 0) - $log->amt;
                } else {
                    // Jika log adalah untuk billing atau lainnya, kurangi dari amt_bal
                    $partnerBal->amt_bal = ($partnerBal->amt_bal ?? 0) - $log->amt;
                    // dd($partnerBal, $log->amt);
                }
                $partnerBal->save();
            }
            // Hapus log secara permanen
            $log->forceDelete();
        }
    }
}
