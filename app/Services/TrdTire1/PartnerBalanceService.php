<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Master\PartnerBal;
use App\Models\TrdTire1\Master\PartnerLog;
use Illuminate\Support\Facades\DB;

class PartnerBalanceService
{
    public function updFromBilling(string $mode, array $headerData)
    {
        if ($mode === '+') {
            $amtBal = $headerData['total_amt'];
        } else if ($mode === '-') {
            $amtBal = -$headerData['total_amt'];
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
        $tes = PartnerBal::where('partner_id', '=', $headerData['partner_id'])
                                ->where('reff_id', '=', $headerData['reff_id'])->first();
        $tes->amt_bal = $amtBal + $tes->amt_bal;
        // dd($partnerBal);
        $tes->update();

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
            'partnerbal_id' => $tes->id,
            'curr_id' => $headerData['curr_id'],
            'amt' => $amtBal,
            'tr_amt' => $headerData['total_amt'],
            'tr_desc' => 'Billing ' . $headerData['tr_type'] . ' ' . $headerData['tr_code'],
        ];
        // Selalu buat log baru untuk delivery
        // dd($logData, $amtBal);
        PartnerLog::create($logData);
        // dd($tes);
        return $tes->id;
    }

    public function updFromPayment(string $mode, array $headerData, array $detailData)
    {
        // dd($mode, $headerData);
        if (!isset($headerData['id'])) {
            throw new \Exception('Header ID (id) is required');
        }

        if ($mode === '+') {
            $amtBal = $detailData['amt'];
        } else if ($mode === '-') {
            $amtBal = -$detailData['amt'];
        }

        // Cari atau buat partner balance berdasarkan partner_id
        $partnerBal = PartnerBal::updateOrCreate(
            [
                'partner_id' => $headerData['partner_id'],
                'reff_id' => $detailData['billhdr_id'],
            ],
            [
                'partner_code' => $headerData['partner_code'],
                'reff_type' => $detailData['billhdrtr_type'],
                'reff_code' => $detailData['billhdrtr_code'],
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
