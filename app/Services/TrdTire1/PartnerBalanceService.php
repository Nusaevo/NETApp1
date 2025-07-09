<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Master\PartnerBal;
use App\Models\TrdTire1\Master\PartnerLog;
use Exception;
use Illuminate\Support\Facades\{DB, Log};

class PartnerBalanceService
{
    public function updFromBilling( array $headerData)
    {
        try{
            $partnerBal = PartnerBal::updateOrCreate(
                [
                    'partner_id' => $headerData['partner_id'],
                    'reff_id' => $headerData['id'],
                ],
                [
                    'partner_code' => $headerData['partner_code'],
                    'reff_type' => $headerData['tr_type'],
                    'reff_code' => $headerData['tr_code'],
                    'amt_bal' => 0,
                    'amt_adv' => 0,
                    'note' => '',
                ]
            );

            $partnerBal->amt_bal +=  $headerData['amt'];
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
                'amt' => $headerData['amt'],
                'tr_amt' => $headerData['amt'],
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
                $reffId = $detailData['billhdr_id'];
                $partnerCode = $headerData['partner_code'];
                $reffType = $detailData['billhdrtr_type'];
                $reffCode = $detailData['billhdrtr_code'];

                $amtBal = -$detailData['amt'];
                $trAmt = $detailData['amt'];
                $trDesc = 'Payment ' . $headerData['tr_type'] . ' ' . $headerData['tr_code'];

            } else if ($detailData['tr_type'] === 'ARPS') {
                $partnerId = $detailData['bank_id'] ;
                $reffId = 0;
                $partnerCode = $detailData['bank_code'];
                $reffType = '';
                $reffCode = '';

                $amtBal = $detailData['amt'];
                $trAmt = $detailData['amt'];
                $trDesc =  'PayRcvd To ' . $detailData['bank_code'];

            } else if ($detailData['tr_type'] === 'ARPA') {
                $partnerId = $headerData['partner_id'];
                $reffId = $detailData['reff_id'];
                $partnerCode = $headerData['partner_code'];
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

            $logData = [
                'trhdr_id' => $detailData['trhdr_id'],
                'tr_type' => $detailData['tr_type'],
                'tr_code' => $detailData['tr_code'],
                'tr_date' => $headerData['tr_date'],
                'tr_seq' => $detailData['tr_seq'],
                'trdtl_id' => $detailData['id'],
                'partner_id' => $partnerId,
                'partner_code' => $partnerCode,
                'partnerbal_id' => $partnerBal->id,
                'amt' => $amtBal != 0 ? $amtBal : $amtAdv,
                'tr_amt' => $trAmt,
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
        try {
            if (!isset($headerData['id'])) {
                throw new Exception('Header ID (id) is required');
            }

            $amtAdv = 0;

            $partnerId = $headerData['partner_id'];
            $reffId = $detailData['reff_id'];
            $partnerCode = $headerData['partner_code'];
            $reffType = $detailData['reff_type'];
            $reffCode = $detailData['reff_code'];

            $amtAdv = $detailData['amt'];
            $trAmt = $detailData['amt'];
            $trDesc = 'Advance Usage from ' . $detailData['reff_type'] . ' ' . $detailData['reff_code'];

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
                    'note' => '',
                ]
            );

            $partnerBal->amt_adv += $amtAdv;
            $partnerBal->save();


            $logData = [
                'trhdr_id' => $detailData['trhdr_id'],
                'tr_type' => $detailData['tr_type'],
                'tr_code' => $detailData['tr_code'],
                'tr_date' => $headerData['tr_date'],
                'tr_seq' => $detailData['tr_seq'],
                'trdtl_id' => $detailData['id'],
                'partner_id' => $partnerId,
                'partner_code' => $partnerCode,
                'partnerbal_id' => $partnerBal->id,
                'amt' => $amtAdv,
                'tr_amt' => $trAmt,
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

    public function delPartnerLog(int $trHdrId)
    {
        // Hapus semua log partner terkait trHdrId secara permanen
        $logs = PartnerLog::where('trhdr_id','=', $trHdrId)->get();

        foreach ($logs as $log) {
            // Update PartnerBal jika perlu
            $partnerBal = PartnerBal::where('id', '=',$log->partnerbal_id)->first();
            // dd($partnerBal);
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
