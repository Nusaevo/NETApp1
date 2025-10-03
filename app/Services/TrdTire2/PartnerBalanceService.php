<?php

namespace App\Services\TrdTire2;

use Exception;
use Illuminate\Support\Carbon;
use App\Models\SysConfig1\ConfigConst;
use Illuminate\Support\Facades\{DB, Log};
use App\Models\TrdTire2\Master\PartnerBal;
use App\Models\TrdTire2\Master\PartnerLog;

class PartnerBalanceService
{
    public function updFromBilling(array $headerData)
    {
        try {
            // Cari partner balance berdasarkan partner_id saja
            $partnerBal = PartnerBal::updateOrCreate(
                [
                    'partner_id' => $headerData['partner_id'],
                    'reff_id' => $headerData['id'],
                ],
                [
                    'partner_code' => $headerData['partner_code'],
                    'reff_type' => $headerData['tr_type'],
                    'reff_code' => $headerData['tr_code'],
                    'descr' => 'Invoice Nota ' . $headerData['tr_code'],
                    'amt_bal' => $headerData['amt'] + $headerData['amt_adjusthdr'] + $headerData['amt_shipcost']
                ]
            );

            $logData = [
                'tr_date' => $headerData['tr_date'],
                'trdtl_id' => 0,
                'trhdr_id' => $headerData['id'],
                'tr_type' => $headerData['tr_type'],
                'tr_code' => $headerData['tr_code'],
                'tr_seq' => 1,
                'partner_id' => $headerData['partner_id'],
                'partner_code' => $headerData['partner_code'],
                'reff_id' => $headerData['id'],
                'reff_type' => $headerData['tr_type'],
                'reff_code' => $headerData['tr_code'],
                'tr_amt' => $headerData['amt'],
                'tramt_adjusthdr' => $headerData['amt_adjusthdr'],
                'tramt_shipcost' => $headerData['amt_shipcost'],
                'partnerbal_id' => $partnerBal->id,
                'amt' => $headerData['amt'],
                'curr_id' => $headerData['curr_id'],
                'curr_code' => $headerData['curr_code'],
                'curr_rate' => $headerData['curr_rate'],
                'tr_descr' => 'Invoice ' . $headerData['tr_type'] . '-' . $headerData['tr_code'],
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

    public function updFromPayment(array $headerData, array $detailData)
    {
        try {
            if (!isset($headerData['id'])) {
                throw new Exception('Header ID (id) is required');
            }

            $amtBal = 0;
            $amtAdv = 0;

            $partnerbalDescr = '';
            if ($detailData['tr_type'] === 'ARP') {
                // khusu pelunsan invoice
                $partnerId = $headerData['partner_id'];
                $partnerCode = $headerData['partner_code'];
                $reffId = $detailData['billhdr_id'];
                $reffType = $detailData['billhdrtr_type'];
                $reffCode = $detailData['billhdrtr_code'];

                $amtBal = -$detailData['amt'];
                $trAmt = $detailData['amt'];
                $trDesc = 'Pelunasan ' . $headerData['tr_code'] . ' untuk nota ' . $detailData['billhdrtr_code'];
            } else if ($detailData['tr_type'] === 'ARPS') {
                $partnerId = $detailData['bank_id'] ?? null;
                $partnerCode = $detailData['bank_code'];
                $reffId = $detailData['reff_id'] ?? 0;
                $reffType = $detailData['reff_type'] ?? '';
                $reffCode = $detailData['reff_code'] ?? '';
                if ($detailData['bank_code'] == ConfigConst::getAppEnv('CHEQUE_DEPOSIT')) {
                    $bankDuedt = Carbon::parse($detailData['bank_duedt'])->format('d M Y');
                    $partnerbalDescr = ($detailData['bank_reff'] ?? '') . ' - ' . $bankDuedt;
                }

                $amtBal = $detailData['amt'];
                $trAmt = $detailData['amt'];
                $trDesc =  'Penerimaan ' . ($detailData['bank_code']) . ' dari pelunasan ' . $headerData['tr_code'];
            } else if ($detailData['tr_type'] === 'ARPA') {
                // khusus pemakaian advance (lebih bayar)
                $partnerId = $headerData['partner_id'];
                $partnerCode = $headerData['partner_code'];
                $reffId = $detailData['reff_id'];
                $reffType = $detailData['reff_type'];
                $reffCode = $detailData['reff_code'];

                $amtAdv = $detailData['amt'];
                $trAmt = $detailData['amt'];
                $trDesc = 'Pemakaian saldo dari pelunasan ' . $detailData['reff_code'];
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
                ]
            );

            if (!empty($partnerbalDescr)) {
                $partnerBal->descr = $partnerbalDescr;
            }
            $partnerBal->amt_bal += $amtBal;
            $partnerBal->amt_adv += $amtAdv;
            $partnerBal->save();

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
                'amt' => $amtBal != 0 ? $amtBal : $amtAdv,
                'curr_id' => $headerData['curr_id'],
                'curr_code' => $headerData['curr_code'],
                'curr_rate' => $headerData['curr_rate'],
                'tr_descr' => $trDesc,
            ];

            PartnerLog::create($logData);

            return $partnerBal->id;
        } catch (Exception $e) {
            throw new Exception('Error Update Partner Balance: ' . $e->getMessage());
        }
    }

    public function updFromOverPayment(array $headerData, array $detailData)
    {
        // dd( $headerData);
        if (!isset($headerData['id'])) {
            throw new Exception('Header ID (id) is required');
        }

        $trDesc = 'Lebih bayar dari pelunasan ' . $detailData['tr_code']
            . ' - ' . Carbon::parse($headerData['tr_date'])->format('d M Y');;

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
                'descr' => $trDesc,
            ]
        );

        // Hindari dobel: selalu jumlahkan dari nilai yang ada (default 0 bila null)
        $partnerBal->amt_adv = ($partnerBal->amt_adv ?? 0) + $detailData['amt'];
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
            'tr_descr' => $trDesc,
        ];
        PartnerLog::create($logData);
        return $partnerBal->id;
    }

    public function updFromPartnerTrx(array $headerData, array $detailData)
    {
        if (!isset($headerData['id'])) {
            throw new Exception('Header ID (id) is required');
        }

        $reffId = $detailData['reff_id'];
        $reffType = $detailData['reff_type'];
        $reffCode = $detailData['reff_code'];
        if ($detailData['tr_type'] === 'CQDEP') {
            if ($detailData['tr_seq'] < 0) {
                $trDesc = 'Setor Giro ' . $detailData['tr_descr'];
            } else {
                $trDesc = 'Terima Setoran Giro ' . $detailData['tr_descr'];
                $reffType = '';
                $reffCode = '';
                $reffId = 0;
            }
        } else if ($detailData['tr_type'] === 'CQREJ') {
            if ($detailData['tr_seq'] < 0) {
                $trDesc = 'Tolakan Giro ' . $detailData['tr_descr'];
                $reffType = '';
                $reffCode = '';
                $reffId = 0;
            } else {
                $trDesc = 'Terima Tolakan Giro ' . $detailData['tr_descr'];
            }
        } else if ($detailData['tr_type'] === 'ARA') {
            $trDesc = 'Transaksi penyesuaian Piutang ' . $detailData['tr_type'] . ' ' . $detailData['tr_code'];
        } else {
            $trDesc = 'Transaksi ' . $detailData['tr_type'] . ' ' . $detailData['tr_code'];
        }

        // Cari atau buat partner balance berdasarkan partner_id
        $partnerBal = PartnerBal::updateOrCreate(
            [
                'partner_id' => $detailData['partner_id'],
                'reff_id' => $detailData['reff_id'],
            ],
            [
                'partner_code' => $detailData['partner_code'],
                'reff_type' => $detailData['reff_type'],
                'reff_code' => $detailData['reff_code'],
            ]
        );
        $partnerBal->amt_bal += $detailData['amt'];
        $partnerBal->save();

        $logData = [
            'tr_date' => $headerData['tr_date'],
            'trdtl_id' => $detailData['id'],
            'trhdr_id' => $detailData['trhdr_id'],
            'tr_type' => $detailData['tr_type'],
            'tr_code' => $detailData['tr_code'],
            'tr_seq' => $detailData['tr_seq'],
            'partnerbal_id' => $partnerBal->id,
            'partner_id' => $detailData['partner_id'],
            'partner_code' => $detailData['partner_code'],
            'reff_id' => $reffId,
            'reff_type' => $reffType,
            'reff_code' => $reffCode,
            'tr_amt' => $detailData['amt'],
            'tramt_adjusthdr' => 0,
            'tramt_shipcost' => 0,
            'amt' => $detailData['amt'],
            'curr_id' => $headerData['curr_id'],
            'curr_code' => $headerData['curr_code'],
            'curr_rate' => $headerData['curr_rate'],
            'tr_descr' => $trDesc,
        ];
        PartnerLog::create($logData);
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
