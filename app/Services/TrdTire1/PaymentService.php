<?php

namespace App\Services\TrdTire1;

use App\Models\TrdTire1\Transaction\{PaymentHdr, PaymentDtl, PaymentSrc, BillingHdr, BillingDtl};
use App\Models\TrdTire1\Master\Partner;
use App\Models\SysConfig1\ConfigConst;
use Illuminate\Support\Facades\{DB, Log};
use Exception;

class PaymentService
{
    protected $partnerBalanceService;

    public function __construct(PartnerBalanceService $partnerBalanceService)
    {
        $this->partnerBalanceService = $partnerBalanceService;
    }

    public function addPayment(array $headerData, array $detailData, array $sourceData, $advanceData)
    {
        return DB::transaction(function () use ($headerData, $detailData, $sourceData, $advanceData) {
            // Debugging
            Log::debug('Starting addPayment transaction', [
                'detailData_count' => count($detailData),
                'sourceData_count' => count($sourceData)
            ]);

            // Simpan header
            $paymentHdr = $this->saveHeader($headerData);
            Log::debug('Header saved', ['id' => $paymentHdr->id]);

            // Update headerData dengan ID yang baru dibuat
            $headerData['id'] = $paymentHdr->id;
            Log::debug('Updated headerData with new ID', ['headerData' => $headerData]);

            // Set header ID ke detail data
            foreach ($detailData as &$detail) {
                $detail['trhdr_id'] = $paymentHdr->id;
                $detail['tr_code'] = $paymentHdr->tr_code;
                $detail['tr_type'] = $paymentHdr->tr_type;
            }
            unset($detail);

            // Set header ID ke payment data
            foreach ($sourceData as &$payment) {
                $payment['trhdr_id'] = $paymentHdr->id;
                $payment['tr_code'] = $paymentHdr->tr_code;
                $payment['tr_type'] = $paymentHdr->tr_type;
            }
            unset($payment);

            // Simpan detail dan payment
            $this->savePaymentDetail($headerData, $detailData);
            $this->savePaymentSrc($headerData, $sourceData);
            $this->savePaymentAdv($headerData, $advanceData);

            return [
                'header' => $paymentHdr
            ];
        });
    }

    public function modPayment(int $paymentId, array $headerData, array $detailData, array $sourceData, $advanceData = [])
    {
        DB::beginTransaction();
        try {
            // Cek apakah payment header ada
            $paymentHdr = PaymentHdr::find($paymentId);
            if (!$paymentHdr) {
                throw new Exception('Payment header tidak ditemukan');
            }

            // Update header
            $paymentHdr->update($headerData);

            // Pastikan headerData memiliki ID yang benar
            $headerData['id'] = $paymentHdr->id;
            Log::debug('Updated headerData with existing ID in modPayment', ['headerData' => $headerData]);

            // Hapus detail dan payment lama
            $this->deleteDetail($paymentId);
            $this->deletePayment($paymentId);

            // Set header ID ke detail data
            foreach ($detailData as &$detail) {
                $detail['trhdr_id'] = $paymentHdr->id;
                $detail['tr_code'] = $paymentHdr->tr_code;
                $detail['tr_type'] = $paymentHdr->tr_type;
            }
            unset($detail);

            // Set header ID ke payment data
            foreach ($sourceData as &$payment) {
                $payment['trhdr_id'] = $paymentHdr->id;
                $payment['tr_code'] = $paymentHdr->tr_code;
                $payment['tr_type'] = $paymentHdr->tr_type;
            }
            unset($payment);

            // Simpan detail dan payment baru
            $this->savePaymentDetail($headerData, $detailData);
            $this->savePaymentSrc($headerData, $sourceData);
            $this->savePaymentAdv($headerData, $advanceData);

            DB::commit();
            return $paymentHdr;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function delPayment(int $paymentId)
    {
        $this->deleteDetail($paymentId);
        $this->deletePayment($paymentId);
        $this->deleteHeader($paymentId);
    }

    private function saveHeader(array $headerData): PaymentHdr
    {
        if (isset($headerData['id'])) {
            $paymentHdr = PaymentHdr::find($headerData['id']);
            if (!$paymentHdr) {
                throw new Exception('Payment header tidak ditemukan');
            }
            $paymentHdr->update($headerData);
            return $paymentHdr;
        } else {
            return PaymentHdr::create($headerData);
        }
    }

    private function savePaymentDetail(array $headerData, array $detailData): void
    {
        // Debug: tampilkan data yang akan disimpan
        if (empty($detailData)) {
            Log::warning('No detail data to save in PaymentDtl');
            return;
        }

        try {
            // Log headerData untuk debugging
            Log::debug('savePaymentDetail - headerData received', [
                'headerData' => $headerData,
                'has_id' => isset($headerData['id']),
                'id_value' => $headerData['id'] ?? 'not set'
            ]);

            foreach ($detailData as $detail) {
                try {
                    $paymentDetail = new PaymentDtl($detail);
                    $result = $paymentDetail->save();

                    if (!$result) {
                        Log::error('Failed to save PaymentDtl', [
                            'detail' => $detail,
                            'errors' => $paymentDetail->getErrors() ?? 'Unknown error'
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error saving PaymentDtl: ' . $e->getMessage(), [
                        'detail' => $detail,
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            }

            // Update saldo partner sekali saja setelah semua detail disimpan
            if (!empty($detailData)) {
                Log::debug('About to update partner balance with headerData', [
                    'headerData' => $headerData
                ]);
                $this->partnerBalanceService->updPartnerBalance('-', $headerData);
            }
        } catch (\Exception $e) {
            Log::error('Error in savePaymentDetail: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'headerData' => $headerData
            ]);
            throw $e;
        }
    }

    private function savePaymentSrc(array $headerData, array $sourceData): void
    {
        foreach ($sourceData as $payment) {
            $paymentSrc = new PaymentSrc($payment);
            $paymentSrc->save();
        }
    }

    private function savePaymentAdv(array $headerData, array $advanceData): void
    {
        // Simpan detail pembayaran advance jika ada
        if (empty($advanceData)) {
            return;
        }

        foreach ($advanceData as $adv) {
            $payAdv = [
                'trhdr_id'      => $headerData['id'] ?? null,
                'tr_type'       => ($headerData['tr_type'] ?? '') . 'A',
                'tr_code'       => $headerData['tr_code'] ?? null,
                'tr_seq'        => $adv['tr_seq'] ?? 1,
                'adv_type_id'   => ConfigConst::where('const_group', 'ADV_TYPE_CODE')
                                        ->where('str1', 'ARADVPAY')
                                        ->value('id'),
                'adv_type_code' => ($headerData['tr_type'] ?? '') . 'ADVPAY',
                'partnerbal_id' => $adv['partnerbal_id'] ?? null,
                'reff_id'       => $adv['reff_id'] ?? null,
                'reff_type'     => $adv['reff_type'] ?? null,
                'reff_code'     => $adv['reff_code'] ?? null,
                'amt'           => $adv['amt'] ?? 0,
                'amt_base'      => $adv['amt_base'] ?? 0,
            ];

            // Simpan ke tabel PaymentSrc (atau model advance yang sesuai)
            $paymentAdv = new PaymentSrc($payAdv);
            $paymentAdv->save();

            // Update saldo partner untuk advance
            $this->partnerBalanceService->updPartnerBalance('-', $payAdv);
        }
    }

    private function deleteHeader(int $paymentId): bool
    {
        $paymentHdr = PaymentHdr::findOrFail($paymentId);
        return (bool) $paymentHdr->forceDelete();
    }

    private function deleteDetail(int $paymentId): void
    {
        // Get existing details
        $existingDetails = PaymentDtl::where('trhdr_id', $paymentId)->get();

        // Delete details
        foreach ($existingDetails as $detail) {
            $detail->forceDelete();
        }
    }

    private function deletePayment(int $paymentId): void
    {
        // Get existing payments
        $existingPayments = PaymentSrc::where('trhdr_id', $paymentId)->get();

        // Delete payments
        foreach ($existingPayments as $payment) {
            $payment->forceDelete();
        }
    }
}
