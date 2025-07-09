<?php

namespace App\Services\TrdTire1;

use Exception;
use Illuminate\Support\Facades\{DB, Log};
use App\Models\TrdTire1\Master\PartnerBal;
use App\Services\SysConfig1\ConfigService;
use App\Models\TrdTire1\Transaction\{PaymentHdr, PaymentDtl, PaymentSrc, BillingHdr, BillingDtl, PaymentAdv};

class PaymentService
{
    protected $partnerBalanceService;

    public function __construct(PartnerBalanceService $partnerBalanceService)
    {
        $this->partnerBalanceService = $partnerBalanceService;
    }

    public function addPayment(array $headerData, array $detailData, array $sourceData, array $advanceData, float $overAmt)
    {
        // dd($headerData, $detailData, $sourceData, $advanceData, $overAmt);
        try {
            // Simpan header
            $paymentHdr = $this->saveHeader($headerData);
            $headerData['id'] = $paymentHdr->id;

            $this->savePaymentDetail($headerData, $detailData);
            $this->savePaymentSrc($headerData, $sourceData);
            if ($advanceData) {
                $this->savePaymentAdv($headerData, $advanceData);
            }
            if ($overAmt > 0) {
                $this->overPayment($headerData, $overAmt); // Pass empty array untuk advanceData
            }

            return $paymentHdr;
       } catch (Exception $e) {
            throw new Exception('Error adding payment: ' . $e->getMessage());
        }
    }

    public function updPayment(int $paymentId, array $headerData, array $detailData, array $sourceData, array $advanceData, float $overAmt = 0.0)
    {
        try {
            // Cek apakah payment header ada
            $paymentHdr = PaymentHdr::find($paymentId);
            if (!$paymentHdr) {
                throw new Exception('Payment header tidak ditemukan');
            }

            // Update header
            $paymentHdr->update($headerData);
            $headerData['id'] = $paymentHdr->id;

            // Set header ID ke detail data
            // Hapus detail dan payment lama
            $this->deleteDetail($paymentId);


            $this->savePaymentDetail($headerData, $detailData);
            $this->savePaymentSrc($headerData, $sourceData);
            if ($advanceData) {
                $this->savePaymentAdv($headerData, $advanceData);
            }
            if ($overAmt > 0) {
                $this->overPayment($headerData, $overAmt); // Pass empty array untuk advanceData
            }

            return $paymentHdr;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function delPayment(int $paymentId)
    {
        $this->deleteDetail($paymentId);
        $this->deleteHeader($paymentId);
    }

    private function saveHeader(array $headerData): PaymentHdr
    {
        // dd($headerData);
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
        // dd($headerData, $detailData);
        // Debug: tampilkan data yang akan disimpan
        // if (empty($detailData)) {
        //     Log::warning('No detail data to save in PaymentDtl');
        //     return;
        // }

        // Pastikan headerData memiliki id
        if (!isset($headerData['id'])) {
            throw new Exception('Header ID tidak tersedia untuk menyimpan PaymentDetail');
        }

        try {
            foreach ($detailData as &$detail) {
                $detail['trhdr_id'] = $headerData["id"];
                $detail['tr_type'] = $headerData["tr_type"];
                $detail['tr_code'] = $headerData["tr_code"];
            }
            unset($detail);

            foreach ($detailData as &$detail) {
                $paymentDtl = new PaymentDtl($detail);
                $paymentDtl->save();
                $detail['id'] = $paymentDtl->id;

                // Debug sebelum update partner balance
                $partnerBalId = $this->partnerBalanceService->updFromPayment($headerData, $detail);

                $paymentDtl->partnerbal_id = $partnerBalId;
                $paymentDtl->save();

                app(BillingService::class)->updAmtReff("+", $detail["amt"], $detail["billhdr_id"]);

            }
            unset($detail);

        } catch (Exception $e) {
            Log::error('Error in savePaymentDetail: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'headerData' => $headerData
            ]);
            throw $e;
        }
    }

    private function savePaymentSrc(array $headerData, array $sourceData): void
    {
        // Pastikan headerData memiliki id
        if (!isset($headerData['id'])) {
            throw new Exception('Header ID tidak tersedia untuk menyimpan PaymentSrc');
        }

        foreach ($sourceData as &$source) {
            $source['trhdr_id'] = $headerData["id"];
            $source['tr_type'] = $headerData["tr_type"] . 'S'; // Gunakan tr_type dari header
            $source['tr_code'] = $headerData["tr_code"];
        }
        unset($source);

        foreach ($sourceData as $payment) {
            $paymentSrc = new PaymentSrc($payment);
            $paymentSrc->save();
            $payment['id'] = $paymentSrc->id;

            $partnerBalId = $this->partnerBalanceService->updFromPayment( $headerData, $payment);
            if (!$paymentSrc->partnerbal_id) {
                $paymentSrc->partnerbal_id = $partnerBalId;  // Now using the ID directly
            }
            $paymentSrc->save();


        }
    }

    public function savePaymentAdv(array $headerData, array $advanceData): void
    {
        // Pastikan headerData memiliki id
        if (!isset($headerData['id'])) {
            throw new Exception('Header ID tidak tersedia untuk menyimpan PaymentAdv');
        }

        // dd($headerData, $advanceData);
        foreach ($advanceData as &$advance) {
            $advance['trhdr_id'] = $headerData["id"];
            $advance['tr_type'] = $headerData["tr_type"] . 'A';
            $advance['tr_code'] = $headerData["tr_code"];
        }
        unset($advance);

        foreach ($advanceData as $advance) {
            $paymentAdv = new PaymentAdv($advance);
            $paymentAdv->save();
            $advance['id'] = $paymentAdv->id;

            $partnerBalId = $this->partnerBalanceService->updFromPayment( $headerData, $advance);
            if (!$paymentAdv->partnerbal_id) {
                $paymentAdv->partnerbal_id = $partnerBalId;  // Now using the ID directly
            }
            $paymentAdv->save();

        }

    }

    private function overPayment(array $headerData, float $overAmt): void
    {
        // Pastikan headerData memiliki id
        if (!isset($headerData['id'])) {
            throw new Exception('Header ID tidak tersedia untuk menyimpan overPayment');
        }

        if ($overAmt <= 0) {
            Log::warning('OverPayment amount is 0 or negative, skipping');
            return;
        }

        // Buat array baru untuk overPayment, jangan gunakan $advanceData yang mungkin kosong
        $overPaymentData = [
            'trhdr_id' => $headerData['id'],
            'tr_type' => $headerData['tr_type'] . 'A',
            'tr_code' => $headerData['tr_code'],
            'tr_seq' => 1,
            'adv_type_code' => 'ARADVPAY',
            'adv_type_id' => app(ConfigService::class)->getConstIdByStr1('TRX_PAYMENT_TYPE_ADVS', 'ARADVPAY'),
            'amt' => $overAmt,
            'reff_id' => $headerData['id'],
            'reff_type' => $headerData['tr_type'],
            'reff_code' => $headerData['tr_code'],
        ];

        $paymentAdv = new PaymentAdv($overPaymentData);
        $paymentAdv->save();
        $overPaymentData['id'] = $paymentAdv->id;

        $partnerBalId = $this->partnerBalanceService->updFromPayment( $headerData, $overPaymentData);
        $paymentAdv->partnerbal_id = $partnerBalId;  // This is already correct since it uses the ID directly

        $paymentAdv->save();
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
        foreach ($existingDetails as $detail) {
            app(BillingService::class)->updAmtReff("-", $detail["amt"], $detail["billhdr_id"]);
            $detail->delete();
        }

        PaymentSrc::where('trhdr_id', $paymentId)->delete();

        PaymentAdv::where('trhdr_id', $paymentId)->delete();

        // Delete partner log untuk setiap detail
        app(PartnerBalanceService::class)->delPartnerLog($paymentId);
    }
 }
