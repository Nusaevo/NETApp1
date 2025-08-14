<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Master\PartnerBal;
use App\Models\TrdTire1\Master\PartnerLog;
use Exception;
use Illuminate\Support\Facades\{DB, Log};

class PartnerBalanceService
{
    public function updFromBilling(array $headerData)
    {
        try{
            // Cari partner balance berdasarkan partner_id saja
            $partnerBal = PartnerBal::where('partner_id', $headerData['partner_id'])->first();

            if (!$partnerBal) {
                // Jika belum ada, buat baru
                $partnerBal = PartnerBal::create([
                    'partner_id' => $headerData['partner_id'],
                    'partner_code' => $headerData['partner_code'],
                    'reff_type' => $headerData['tr_type'],
                    'reff_code' => $headerData['tr_code'],
                    'reff_id' => $headerData['id'],
                    'amt_bal' => 0,
                    'amt_adv' => 0,
                    'note' => '',
                ]);
            } else {
                // Jika sudah ada, update reff info dan balance
                $partnerBal->partner_code = $headerData['partner_code'];
                $partnerBal->reff_type = $headerData['tr_type'];
                $partnerBal->reff_code = $headerData['tr_code'];
                $partnerBal->reff_id = $headerData['id'];
            }

            $amt = $headerData['amt'] + $headerData['amt_adjusthdr'] + $headerData['amt_shipcost'];
            $partnerBal->amt_bal += $amt;
            $partnerBal->save();

            $logData = [
                'tr_date' => $headerData['tr_date'],
                'trdtl_id' => 0,
                'trhdr_id' => $headerData['id'],
                'tr_type' => $headerData['tr_type'],
                'tr_code' => $headerData['tr_code'],
                'tr_seq' => 1,
                'partner_id' => $headerData['partner_id'],
                'partner_code' => $headerData['partner_code'],
                'reff_id' => 0,
                'reff_type' => '',
                'reff_code' => '',
                'tr_amt' => $headerData['amt'],
                'tramt_adjusthdr' => $headerData['amt_adjusthdr'],
                'tramt_shipcost' => $headerData['amt_shipcost'],
                'partnerbal_id' => $partnerBal->id,
                'amt' => $amt,
                'curr_id' => $headerData['curr_id'],
                'curr_code' => $headerData['curr_code'],
                'curr_rate' => $headerData['curr_rate'],
                'tr_desc' => 'Billing ' . $headerData['tr_type'] . ' ' . $headerData['tr_code'],
            ];
            // Selalu buat log baru untuk delivery
            // dd($logData, $amtBal);
            PartnerLog::create($logData);
            // dd($tes);
            return $partnerBal->id;
        } catch (Exception $e) {
             throw new Exception('Error deleting order: ' . $e->getMessage());
        }
    }

    public function updFromPayment( array $headerData, array $detailData)
    {
        // dd( $headerData);
        try {
            if (!isset($headerData['id'])) {
                throw new Exception('Header ID (id) is required');
            }

            $amtBal = 0;
            $amtAdv = 0;

            if ($detailData['tr_type'] === 'ARP') {
                $partnerId = $headerData['partner_id'];
                $partnerCode = $headerData['partner_code'];
                $reffId = $detailData['billhdr_id'];
                $reffType = $detailData['billhdrtr_type'];
                $reffCode = $detailData['billhdrtr_code'];

                $amtBal = -$detailData['amt'];
                $trAmt = $detailData['amt'];
                $trDesc = 'Payment ' . $headerData['tr_type'] . ' ' . $headerData['tr_code'];

            } else if ($detailData['tr_type'] === 'ARPS') {
                $partnerId = $detailData['bank_id'] ;
                $partnerCode = $detailData['bank_code'];
                $reffId = 0;
                $reffType = '';
                $reffCode = '';

                $amtBal = $detailData['amt'];
                $trAmt = $detailData['amt'];
                $trDesc =  'PayRcvd To ' . $detailData['bank_code'];

            } else if ($detailData['tr_type'] === 'ARPA') {
                $partnerId = $headerData['partner_id'];
                $partnerCode = $headerData['partner_code'];
                $reffId = $detailData['reff_id'];
                $reffType = $detailData['reff_type'];
                $reffCode = $detailData['reff_code'];

                $amtAdv = -$detailData['amt'];
                $trAmt = -$detailData['amt'];
                $trDesc = 'Sisa Pembayaran dari ' . $detailData['reff_type'] . ' ' . $detailData['reff_code'];
            }

            // Cari atau buat partner balance berdasarkan partner_id
            $partnerBal = PartnerBal::updateOrCreate(
                [
                    'partner_id' => $partnerId,
                    'reff_id' => $reffId,
                ],
                [
                    'partner_code' => $partnerCode,
                    'reff_type' => $reffType,
                    'reff_code' => $reffCode,
                    'amt_bal' => 0,
                    'amt_adv' => 0,
                    'note' => $trDesc,
                ]
            );


            $partnerBal->amt_bal += $amtBal;
            $partnerBal->amt_adv += $amtAdv;
            $partnerBal->note = $trDesc; // update note setiap kali update
            $partnerBal->save();

            // Jika ARPA, kurangi juga amt_adv dari PartnerBal lama (saldo sebelumnya)
            // if ($detailData['tr_type'] === 'ARPA' && $reffId) {
            //     $oldPartnerBal = PartnerBal::where('partner_id', $partnerId)
            //         ->where('id', '!=', $partnerBal->id)
            //         ->where('reff_id', $reffId)
            //         ->first();
            //     if ($oldPartnerBal) {
            //         $oldPartnerBal->amt_adv -= abs($detailData['amt']);
            //         $oldPartnerBal->save();
            //     }
            // }

            $logData = [
                'tr_date' => $headerData['tr_date'],
                'trdtl_id' => $detailData['id'],
                'trhdr_id' => $detailData['trhdr_id'],
                'tr_type' => $detailData['tr_type'],
                'tr_code' => $detailData['tr_code'],
                'tr_seq' => $detailData['tr_seq'],
                'partnerbal_id' => $partnerBal->id,
                'partner_id' => $partnerId,
                'partner_code' => $partnerCode,
                'reff_id' => $reffId,
                'reff_type' => $reffType,
                'reff_code' => $reffCode,
                'tr_amt' => $trAmt,
                'tramt_adjusthdr' => 0,
                'tramt_shipcost' => 0,
                'partnerbal_id' => $partnerBal->id,
                'amt' => $amtBal != 0 ? $amtBal : $amtAdv,
                'curr_id' => $headerData['curr_id'],
                'curr_code' => $headerData['curr_code'],
                'curr_rate' => $headerData['curr_rate'],
                'tr_desc' => $trDesc,
        ];
            // dd($logData, $amtBal);
            PartnerLog::create($logData);
            // dd($logData);
            return $partnerBal->id;
        } catch (Exception $e) {
            throw new Exception('Error deleting order: ' . $e->getMessage());
        }
    }

    public function updFromOverPayment( array $headerData, array $detailData)
    {
        // dd( $headerData);
        if (!isset($headerData['id'])) {
            throw new Exception('Header ID (id) is required');
        }

        $trDesc = 'Advance Usage from ' . $detailData['reff_type'] . ' ' . $detailData['reff_code'];

        // Cari atau buat partner balance berdasarkan partner_id
        $partnerBal = PartnerBal::updateOrCreate(
            [
                'partner_id' => $headerData['partner_id'],
                'reff_id' => $detailData['reff_id'],
            ],
            [
                'partner_code' => $headerData['partner_code'],
                'reff_type' => $detailData['reff_type'],
                'reff_code' => $detailData['reff_code'],
           ]
        );

        $partnerBal->amt_adv += $detailData['amt'];
        $partnerBal->save();


        $logData = [
            'tr_date' => $headerData['tr_date'],
            'trhdr_id' => $detailData['trhdr_id'],
            'tr_type' => $detailData['tr_type'],
            'tr_code' => $detailData['tr_code'],
            'tr_seq' => $detailData['tr_seq'],
            'trdtl_id' => $detailData['id'],
            'partner_id' => $headerData['partner_id'],
            'partner_code' => $headerData['partner_code'],
            'partnerbal_id' => $partnerBal->id,
            'reff_id' => $detailData['reff_id'],
            'reff_type' => $detailData['reff_type'],
            'reff_code' => $detailData['reff_code'],
            'tr_amt' => $detailData['amt'],
            'tramt_adjusthdr' => 0,
            'tramt_shipcost' => 0,
            'partnerbal_id' => $partnerBal->id,
            'amt' => $detailData['amt'],
            'curr_id' => $headerData['curr_id'],
            'curr_code' => $headerData['curr_code'],
            'curr_rate' => $headerData['curr_rate'],
            'tr_desc' => $trDesc,
     ];
        // dd($logData, $amtBal);
        PartnerLog::create($logData);
        // dd($logData);
        return $partnerBal->id;
    }

    public function delPartnerLog(int $trHdrId, ?int $trDtlId = null)
    {
        if ($trDtlId !== null) {
            $logs = PartnerLog::where('trdtl_id', '=', $trDtlId)->get();
        } else {
            $logs = PartnerLog::where('trhdr_id', '=', $trHdrId)->get();
        }

        foreach ($logs as $log) {
            $partnerBal = PartnerBal::find($log->partnerbal_id);
            if ($partnerBal) {
                if ($log->tr_type === 'ARPA' or $log->tr_type === 'APPA') {
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
            $log->Delete();
        }
    }
}
