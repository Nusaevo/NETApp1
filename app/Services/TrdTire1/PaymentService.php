<?php

namespace App\Services\TrdTire1;

use Exception;
use Illuminate\Support\Facades\{DB, Log};
use App\Models\TrdTire1\Master\PartnerBal;
use App\Services\SysConfig1\ConfigService;
use App\Models\TrdTire1\Transaction\{PaymentHdr, PaymentDtl, PaymentSrc, BillingHdr, BillingDtl, PartnertrHdr, PaymentAdv};

class PaymentService
{
    protected $partnerBalanceService;

    public function __construct(PartnerBalanceService $partnerBalanceService)
    {
        $this->partnerBalanceService = $partnerBalanceService;
    }

    public function savePayment(array $headerData, array $detailData, array $sourceData, array $advanceData, float $overAmt)
    {
        // dd($headerData, $detailData, $sourceData, $advanceData, $overAmt);
        try {
            // Simpan header
            $paymentHdr = $this->saveHeader($headerData);
            $headerData['id'] = $paymentHdr->id;

            $this->savePaymentDtl($headerData, $detailData);

            $this->savePaymentSrc($headerData, $sourceData);

            $this->savePaymentAdv($headerData, $advanceData);

            $this->saveOverPayment($headerData, $overAmt);
            // dd($headerData, $detailData);

            // Commit transaction
            DB::commit();

            return $paymentHdr;
               } catch (Exception $e) {
            // Rollback transaction
            DB::rollBack();

            throw new Exception('Error adding payment: ' . $e->getMessage());
        }
    }

    private function saveHeader(array $headerData): PaymentHdr
    {
        // dd($headerData);
        if (!isset($headerData['id']) || empty($headerData['id'])) {
            return PaymentHdr::create($headerData);
        } else {
            $paymentHdr = PaymentHdr::find($headerData['id']);
            $paymentHdr->fill($headerData);
            if ($paymentHdr->isDirty()) {
                $paymentHdr->save();
            }
        }
        return $paymentHdr;
    }

    private function savePaymentDtl(array $headerData, array $detailData)
    {
        if (!isset($headerData['id'])) {
            throw new Exception('Header ID tidak tersedia untuk menyimpan PaymentDetail');
        }

        $dbPaymentDtl = PaymentDtl::where('trhdr_id', $headerData['id'])->get();
        $savedIds = [];
        foreach ($detailData as &$detail) {
            $detail['trhdr_id'] = $headerData["id"];
            $detail['tr_type'] = $headerData["tr_type"];
            $detail['tr_code'] = $headerData["tr_code"];

            if (!isset($detail['id']) || empty($detail['id'])) {
                $detail['tr_seq'] = PaymentDtl::getNextTrSeq($headerData['id']);
                $paymentDtl = new PaymentDtl($detail);
                $paymentDtl->fill($detail);
                $paymentDtl->save();
                BillingHdr::updAmtReff($detail['billhdr_id'],$detail['amt']);

                $detail['id'] = $paymentDtl->id;
                $partnerBalId = $this->partnerBalanceService->updFromPayment($headerData, $detail);
                $detail['partnerbal_id'] = $partnerBalId;
                $paymentDtl->partnerbal_id = $partnerBalId;
                $paymentDtl->save();
            } else {
                $paymentDtl = $dbPaymentDtl->where('billhdr_id', $detail['billhdr_id'])->first();
                if ($paymentDtl) {
                    $originalAmt = $paymentDtl->getOriginal('amt');
                    $paymentDtl->fill($detail);
                    if ($paymentDtl->isDirty()) {
                        $this->partnerBalanceService->delPartnerLog(0 , $detail['id']);
                        BillingHdr::updAmtReff($detail['billhdr_id'] , - $originalAmt);
                        $paymentDtl->save();
                        BillingHdr::updAmtReff($detail['billhdr_id'] , $detail['amt']);
                        $partnerBalId = $this->partnerBalanceService->updFromPayment($headerData, $detail);
                        $detail['partnerbal_id'] = $partnerBalId;
                        $paymentDtl->partnerbal_id = $partnerBalId;
                        $paymentDtl->save();
                    }
                }
            }
            $savedIds[] = $detail['id'];
        }
        unset($detail);

        foreach ($dbPaymentDtl as $dbData) {
            if (!in_array($dbData->id, $savedIds)) {
                $this->partnerBalanceService->delPartnerLog(0 , $dbData->id);
                BillingHdr::updAmtReff($dbData->billhdr_id , - $dbData->amt);
                $dbData->delete();
            }
        }
        return true;
    }

    private function savePaymentSrc(array $headerData, array $sourceData)
    {
        // Pastikan headerData memiliki id
        if (!isset($headerData['id'])) {
            throw new Exception('Header ID tidak tersedia untuk menyimpan PaymentSrc');
        }

        $dbPaymentSrc = PaymentSrc::where('trhdr_id', $headerData['id'])->get();
        $savedIds = [];
        foreach ($sourceData as &$source) {
            $source['trhdr_id'] = $headerData["id"];
            $source['tr_type'] = $headerData["tr_type"] . 'S'; // Gunakan tr_type dari header
            $source['tr_code'] = $headerData["tr_code"];

            if (!isset($source['id']) || empty($source['id'])) {
                $source['tr_seq'] = PaymentSrc::getNextTrSeq($headerData['id']);
                $paymentSrc = new PaymentSrc($source);
                $paymentSrc->fill($source);
                $paymentSrc->save();

                $source['id'] = $paymentSrc->id;
                $partnerBalId = $this->partnerBalanceService->updFromPayment($headerData, $source);
                $paymentSrc->partnerbal_id = $partnerBalId;
                $paymentSrc->save();
            } else {
                $paymentSrc = $dbPaymentSrc->where('partner_id', $source['bank_id'])->first();
                if ($paymentSrc) {
                    $paymentSrc->fill($source);
                    if ($paymentSrc->isDirty()) {
                        $this->partnerBalanceService->delPartnerLog(0 , $source['id']);
                        $paymentSrc->save();
                        $partnerBalId = $this->partnerBalanceService->updFromPayment($headerData, $source);
                        $paymentSrc->partnerbal_id = $partnerBalId;
                        $paymentSrc->save();
                    }
                }
            }
            $savedIds[] = $source['id'];
        }
        unset($source);

        foreach ($dbPaymentSrc as $dbData) {
            if (!in_array($dbData->id, $savedIds)) {
                $this->partnerBalanceService->delPartnerLog(0 , $dbData->id);
                $dbData->delete();
            }
        }
        return true;
    }

    public function savePaymentAdv(array $headerData, array $advanceData)
    {

        // Pastikan headerData memiliki id
        if (!isset($headerData['id'])) {
            throw new Exception('Header ID tidak tersedia untuk menyimpan PaymentAdv');
        }

        $dbPaymentAdv = PaymentAdv::where('trhdr_id', $headerData['id'])->get();
        $savedIds = [];
        foreach ($advanceData as $advance) {
            $advance['trhdr_id'] = $headerData['id'];
            $advance['tr_type'] = $headerData['tr_type'] . 'A';
            $advance['tr_code'] = $headerData['tr_code'];
            $advance['reff_id'] = $headerData['id']; // Set reff_id ke payment header ID
            $advance['reff_type'] = $headerData['tr_type']; // Set reff_type ke payment header type
            $advance['reff_code'] = $headerData['tr_code']; // Set reff_code ke payment header code
            $advance['adv_type_code'] = 'ARADVPAY';
            $advance['adv_type_id'] = app(ConfigService::class)->getConstIdByStr1('TRX_PAYMENT_TYPE_ADVS', 'ARADVPAY');
            // Buat amt menjadi negatif agar menjadi positive saat updfromPayment
            $advance['amt'] = -abs($advance['amt']);

            if (!isset($advance['id']) || empty($advance['id'])) {
                $advance['tr_seq'] = PaymentAdv::getNextTrSeq($headerData['id']);
                $paymentAdv = new PaymentAdv($advance);
                $paymentAdv->fill($advance);
                $paymentAdv->save();

                $advance['id'] = $paymentAdv->id;

                try {
                    $partnerBalId = $this->partnerBalanceService->updFromPayment($headerData, $advance);
                } catch (Exception $e) {
                    throw $e;
                }

                $paymentAdv->partnerbal_id = $partnerBalId;
                $paymentAdv->save();

                // Set ID untuk savedIds
                $advance['id'] = $paymentAdv->id;
            } else {
                $paymentAdv = $dbPaymentAdv->where('partnerbal_id', $advance['partnerbal_id'])->first();
                if ($paymentAdv) {
                    $paymentAdv->fill($advance);
                    if ($paymentAdv->isDirty()) {
                        $this->partnerBalanceService->delPartnerLog(0 , $advance['id']);
                        $paymentAdv->save();
                        $partnerBalId = $this->partnerBalanceService->updFromPayment($headerData, $advance);
                        $paymentAdv->partnerbal_id = $partnerBalId;
                        $paymentAdv->save();
                    }
                }
            }

            // Pastikan ID ada sebelum menambahkan ke savedIds
            if (isset($advance['id']) && !empty($advance['id'])) {
                $savedIds[] = $advance['id'];
            }
                }
        unset($advance);

        foreach ($dbPaymentAdv as $dbData) {
            if (!in_array($dbData->id, $savedIds)) {
                $this->partnerBalanceService->delPartnerLog(0 , $dbData->id);
                $dbData->delete();
            }
        }
        return true;
    }

    private function saveOverPayment(array $headerData, float $overAmt): void
    {
        // dd($headerData, $overAmt);
        // Pastikan headerData memiliki id
        if (!isset($headerData['id'])) {
            throw new Exception('Header ID tidak tersedia untuk menyimpan overPayment');
        }

        if ($overAmt == 0) {
            // Hanya hapus record over payment (yang dibuat oleh saveOverPayment), bukan record advance payment
            // Record advance payment memiliki amt negatif, record over payment memiliki amt positif
            $dbPaymentAdv = PaymentAdv::where('trhdr_id', $headerData['id'])
                ->where('reff_id', $headerData['id'])
                ->where('tr_type', $headerData['tr_type'] . 'A')
                ->where('amt', '>', 0) // Hanya record dengan amt positif (over payment)
                ->get();

            if ($dbPaymentAdv->count() > 0) {
                foreach ($dbPaymentAdv as $paymentAdv) {
                    $this->partnerBalanceService->delPartnerLog(0 , $paymentAdv->id);
                    $paymentAdv->delete();
                }
            } else {
                Log::info('No over payment records found to delete');
            }
            return;
        }

        $overPaymentData = [
            'trhdr_id' => $headerData['id'],
            'tr_type' => $headerData['tr_type'] . 'A',
            'tr_code' => $headerData['tr_code'],
            'tr_seq' => PaymentAdv::getNextTrSeq($headerData['id']),
            'adv_type_code' => 'ARADVPAY',
            'adv_type_id' => app(ConfigService::class)->getConstIdByStr1('TRX_PAYMENT_TYPE_ADVS', 'ARADVPAY'),
            'reff_id' => $headerData['id'],
            'reff_type' => $headerData['tr_type'],
            'reff_code' => $headerData['tr_code'],
            'amt' => $overAmt, // Pakai positif untuk over payment (sisa pembayaran)
        ];

        $dbPaymentAdv = PaymentAdv::where('trhdr_id', $headerData['id'])
            ->where('reff_id', $headerData['id'])
            ->where('amt', '>', 0) // Hanya record over payment (amt positif)
            ->get();

        if ($dbPaymentAdv->count() == 0) {
            // Jika tidak ada data existing, buat baru
            $paymentAdv = new PaymentAdv();
            $paymentAdv->fill($overPaymentData);
            $paymentAdv->save();
            $overPaymentData['id'] = $paymentAdv->id;
            $partnerBalId = $this->partnerBalanceService->updFromOverPayment($headerData, $overPaymentData);
            $paymentAdv->partnerbal_id = $partnerBalId;
            $paymentAdv->save();
        } else {
            // Jika ada data existing, update yang pertama
            $paymentAdv = $dbPaymentAdv->first();
            if ($paymentAdv) {
                $paymentAdv->fill($overPaymentData);
                if ($paymentAdv->isDirty()) {
                    $this->partnerBalanceService->delPartnerLog(0 , $paymentAdv->id);
                    $paymentAdv->save();
                    $partnerBalId = $this->partnerBalanceService->updFromOverPayment($headerData, $overPaymentData);
                    $paymentAdv->partnerbal_id = $partnerBalId;
                    $paymentAdv->save();
                }
            }
        }
    }

    public function delPayment(int $paymentId)
    {
        // Ambil payment header untuk mendapatkan tr_code
        $paymentHdr = PaymentHdr::findOrFail($paymentId);
        $paymentTrCode = $paymentHdr->tr_code;

        // Hapus partner transaction adjustment jika ada
        $adjustmentHeader = PartnertrHdr::where('tr_code', $paymentTrCode . '_ADJ')->first();
        if ($adjustmentHeader) {
            app(PartnerTrxService::class)->delPartnerTrx($adjustmentHeader->id);
        }

        // Hapus header langsung
        $paymentHdr->forceDelete();

        // Update billing & hapus detail
        PaymentDtl::where('trhdr_id', $paymentId)
            ->get()
            ->each(function ($detail) {
                BillingHdr::updAmtReff($detail->billhdr_id, -$detail->amt);
                $detail->delete();
            });

        // Hapus sumber & adv payment
        PaymentSrc::where('trhdr_id', $paymentId)->delete();
        PaymentAdv::where('trhdr_id', $paymentId)->delete();

        // Hapus partner log
        app(PartnerBalanceService::class)->delPartnerLog($paymentId);
    }
 }
