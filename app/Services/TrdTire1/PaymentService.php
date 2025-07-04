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
        DB::beginTransaction();
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
                $this->overPayment($headerData, [], $overAmt); // Pass empty array untuk advanceData
            }

            DB::commit();
            return $paymentHdr;
       } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error adding payment: ' . $e->getMessage());
        }
    }

    public function updPayment(int $paymentId, array $headerData, array $detailData, array $sourceData, array $advanceData, float $overAmt = 0.0)
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
                $this->overPayment($headerData, [], $overAmt); // Pass empty array untuk advanceData
            }

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
        // dd($headerData, $detailData);
        // Debug: tampilkan data yang akan disimpan
        if (empty($detailData)) {
            Log::warning('No detail data to save in PaymentDtl');
            return;
        }

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
                $partnerBalId = $this->partnerBalanceService->updFromPayment('-', $headerData, $detail);

                $paymentDtl->partnerbal_id = $partnerBalId;
                $paymentDtl->save();

                try {
                    app(BillingService::class)->updAmtReff("+", $detail["amt"], $detail["billhdr_id"]);
                } catch (\Throwable $e) {
                    throw $e;
                }
            }
            unset($detail); // Clean up reference

            // Update saldo partner sekali saja setelah semua detail disimpan
            if (!empty($detailData)) {
                Log::debug('About to update partner balance with headerData', [
                    'headerData' => $headerData
                ]);
           }
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
        // dd($headerData, $sourceData);
        if (empty($sourceData)) {
            Log::warning('No source data to save in PaymentSrc');
            return;
        }

        // Pastikan headerData memiliki id
        if (!isset($headerData['id'])) {
            throw new Exception('Header ID tidak tersedia untuk menyimpan PaymentSrc');
        }

        foreach ($sourceData as &$source) {
            $source['trhdr_id'] = $headerData["id"];
            $source['tr_type'] = "ARPS";
            $source['tr_code'] = $headerData["tr_code"];
        }
        unset($source);

        foreach ($sourceData as $payment) {
            $paymentSrc = new PaymentSrc($payment);
            $paymentSrc->save();
        }
    }

    public function savePaymentAdv(array $headerData, array $advanceData): void
    {
        if (empty($advanceData)) {
            Log::warning('No advance data to save in PaymentAdv');
            return;
        }

        // Pastikan headerData memiliki id
        if (!isset($headerData['id'])) {
            throw new Exception('Header ID tidak tersedia untuk menyimpan PaymentAdv');
        }

        // dd($headerData, $advanceData);
        foreach ($advanceData as &$advance) {
            $advance['trhdr_id'] = $headerData["id"];
            $advance['tr_type'] = "ARPA";
            $advance['tr_code'] = $headerData["tr_code"];
        }
        unset($advance);

        foreach ($advanceData as $adv) {
            // Selalu ambil partner_id dan partner_code dari PaymentHdr berdasarkan id header
            $paymentHdr = null;
            if (isset($headerData['id'])) {
                $paymentHdr = PaymentHdr::find($headerData['id']);
            }
            if (!$paymentHdr) {
                throw new Exception('PaymentHdr tidak ditemukan untuk simpan advance');
            }
            $partner_id = $paymentHdr->partner_id;
            $partner_code = $paymentHdr->partner_code;
            // Pastikan partnerbal_id tidak null
            $partnerBal = PartnerBal::where('partner_id', $partner_id)->first();
            if (!$partnerBal) {
                $partnerBal = PartnerBal::create([
                    'partner_id' => $partner_id,
                    'partner_code' => $partner_code,
                    'amt_bal' => 0,
                    'amt_adv' => 0,
                ]);
            }
            $payAdv = [
                'trhdr_id'      => $headerData['id'] ?? null,
                'tr_type'       => ($headerData['tr_type'] ?? '') . 'A',
                'tr_code'       => $headerData['tr_code'] ?? null,
                'tr_seq'        => $adv['tr_seq'] ?? 1,
                'adv_type_id'   => $adv['adv_type_id'] ?? '',
                'adv_type_code' => $adv['adv_type_code'] ?? null,
                'partnerbal_id' => $partnerBal->id,
                'partner_id'    => $partner_id,
                'partner_code'  => $partner_code,
                'reff_id'       => $headerData['id'] ?? null,
                'reff_type'     => $headerData['tr_type'] ?? null,
                'reff_code'     => $adv['reff_code'] ?? null,
                'amt'           => $adv['amt'] ?? 0,
                'amt_base'      => $adv['amt_base'] ?? 0,
            ];

            $paymentAdv = new PaymentAdv($payAdv);
            $paymentAdv->save();

            // Set data yang benar untuk update partner balance
            $payAdv['id'] = $paymentAdv->id; // ID dari PaymentAdv yang baru disimpan
            $payAdv['total_amt'] = $payAdv['amt'] ?? 0;
            $payAdv['tr_date'] = $paymentHdr->tr_date ?? now();
            $payAdv['curr_id'] = $headerData['curr_id'] ?? 1; // Tambahkan curr_id yang diperlukan
            $payAdv['reff_id'] = $paymentAdv->id; // reff_id seharusnya ID PaymentAdv
            $payAdv['reff_type'] = $payAdv['tr_type'] ?? 'ARPA';
            $payAdv['reff_code'] = $payAdv['tr_code'] ?? '';

            $partnerBalId = $this->partnerBalanceService->updFromBilling('-', $payAdv);
            $paymentAdv->partnerbal_id = $partnerBalId;
            $paymentAdv->save();

        }
    }

    private function overPayment(array $headerData, array $advanceData, float $overAmt): void
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
            'tr_type' => 'ARPA',
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

        // Set ID yang benar untuk update partner balance
        $overPaymentData['id'] = $paymentAdv->id;
        $overPaymentData['total_amt'] = $overPaymentData['amt'];
        $overPaymentData['tr_date'] = $headerData['tr_date'] ?? now();

        $partnerBalId = $this->partnerBalanceService->updFromPayment('+', $headerData, $overPaymentData);

        $paymentAdv->partnerbal_id = $partnerBalId;
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
            // Delete partner log untuk setiap detail
            app(PartnerBalanceService::class)->delPartnerLog($detail["billhdr_id"]);
        }
        $existingDetails->each->delete();

        $existingPaymentSrc = PaymentSrc::where('trhdr_id', $paymentId)->get();
        $existingPaymentSrc->each->delete();

        $existingPaymentAdv = PaymentAdv::where('trhdr_id', $paymentId)->get();
        $existingPaymentAdv->each->delete();
    }
 }
