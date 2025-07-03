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
            if ($overAmt) {
                $this->overPayment($headerData, $advanceData, $overAmt);
            }

            DB::commit();
       } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Error adding payment: ' . $e->getMessage());
        };
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
            if ($overAmt) {
                $this->overPayment($headerData, $advanceData, $overAmt);
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

        try {
            foreach ($detailData as &$detail) {
                $detail['trhdr_id'] = $headerData["id"];
                $detail['tr_type'] = $headerData["tr_type"];
                $detail['tr_code'] = $headerData["tr_code"];
            }
            unset($detail);

            foreach ($detailData as $detail) {
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

    private function savePaymentAdv(array $headerData, array $advanceData): void
    {

        foreach ($advanceData as $advance) {
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

            $payAdv['id'] = $headerData['id'] ?? null;
            $payAdv['total_amt'] = $payAdv['amt'] ?? 0;
            $payAdv['tr_date'] = $paymentHdr->tr_date ?? now();

            $partnerBalId = $this->partnerBalanceService->updFromBilling('-', $payAdv);
            $paymentAdv->partnerbal_id = $partnerBalId;
            $paymentAdv->save();

        }
    }

    private function overPayment(array $headerData, array $advanceData, float $overAmt): void
    {
        $advanceData['trhdr_id'] = $headerData['id'];
        $advanceData['tr_type'] = 'ARPA';
        $advanceData['tr_code'] = $headerData['tr_code'];
        $advanceData['tr_seq'] = 1;
        $advanceData['adv_type_code'] = 'ARADVPAY';
        $adcanceData['adv_type_id'] = app(ConfigService::class)->getConstIdByStr1('TRX_PAYMENT_TYPE_ADVS', $advanceData['adv_type_code']);
        $advanceData['amt'] = $overAmt;
        $advanceData['reff_id'] = $headerData['id'];
        $advanceData['reff_type'] = $headerData['tr_type'];
        $advanceData['reff_code'] = $headerData['tr_code'];

        $paymentAdv = new PaymentAdv($advanceData);
        $paymentAdv->save();
        $advanceData['id'] = $headerData['id'];


        $paartnerBalId = $this->partnerBalanceService->updFromPayment('+', $headerData, $advanceData);

        $paymentAdv->partnerbal_id = $paartnerBalId;
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
            $existingDetails->delete();
        }

        $existingDetails = PaymentSrc::where('trhdr_id', $paymentId)->get();
        $existingDetails->delete();

        $existingDetails = PaymentAdv::where('trhdr_id', $paymentId)->get();
        $existingDetails->delete();

        app(PartnerBalanceService::class)->delPartnerLog($detail["billhdr_id"]);
    }
 }
