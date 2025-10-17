<?php

namespace App\Services\TrdTire1;

use Exception;
use App\Models\SysConfig1\ConfigConst;
use Illuminate\Support\Facades\{DB, Log};
use App\Models\TrdTire1\Master\PartnerBal;
use App\Services\SysConfig1\ConfigService;
use App\Models\TrdTire1\Transaction\{PaymentHdr, PaymentDtl, PaymentSrc, BillingHdr, BillingDtl, PartnertrHdr, PaymentAdv, OrderHdr};
use App\Enums\TrdTire1\Status as TrdStatus;

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

            $amtAdvance = array_sum(array_column($advanceData, 'amt'));
            $amtDetail = array_sum(array_column($detailData, 'amt'));
            $amtSource = array_sum(array_column($sourceData, 'amt'));
            if (($amtSource + $amtAdvance) !=  ($amtDetail + $overAmt)) {
                throw new Exception('Total sumber pembayaran tidak sesuai dengan total pembayaran dan uang muka.');
            }
            // dd($headerData, $detailData, $sourceData, $advanceData, $overAmt);

            // Simpan header
            $paymentHdr = $this->saveHeader($headerData);
            $headerData['id'] = $paymentHdr->id;
            // dd($headerData);

            $this->savePaymentDtl($headerData, $detailData);
            // dd($headerData, $detailData);

            $this->savePaymentSrc($headerData, $sourceData);
            // dd($headerData, $sourceData);

            $this->savePaymentAdv($headerData, $advanceData);
            // dd($headerData, $advanceData);

            $this->saveOverPayment($headerData, $overAmt);
            // dd($headerData, $overAmt);

            return $paymentHdr;
        } catch (Exception $e) {

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

            if (!isset($detail['id']) || empty($detail['id'])) {
                $detail['tr_seq'] = PaymentDtl::getNextTrSeq($headerData['id']);
                $paymentDtl = new PaymentDtl($detail);
                $paymentDtl->fill($detail);
                $paymentDtl->save();
                BillingHdr::updAmtReff($detail['billhdr_id'], $detail['amt']);

                $detail['id'] = $paymentDtl->id;
                $partnerBalId = $this->partnerBalanceService->updFromPayment($headerData, $detail);
                // dd($paymentDtl);
                $detail['partnerbal_id'] = $partnerBalId;
                $paymentDtl->partnerbal_id = $partnerBalId;
                $paymentDtl->save();

                // Update order status to 'T' (BILL) when the related billing is fully paid
                $billing = BillingHdr::find($detail['billhdr_id']);
                if ($billing) {
                    $outstanding = ($billing->amt + ($billing->amt_shipcost ?? 0)) - ($billing->amt_reff ?? 0);
                    if ($outstanding <= 0) {
                        $order = OrderHdr::where('tr_code', $billing->tr_code)->where('tr_type', 'SO')->first();
                        if ($order && $order->status_code !== TrdStatus::BILL) {
                            $order->status_code = TrdStatus::BILL;
                            $order->save();
                        }
                    }
                }
            } else {
                $paymentDtl = $dbPaymentDtl->where('billhdr_id', $detail['billhdr_id'])->first();
                if ($paymentDtl) {
                    $originalAmt = $paymentDtl->getOriginal('amt');
                    $paymentDtl->fill($detail);
                    if ($paymentDtl->isDirty()) {
                        $this->partnerBalanceService->delPartnerLog(0, $detail['id']);
                        BillingHdr::updAmtReff($detail['billhdr_id'], -$originalAmt);
                        $paymentDtl->save();
                        BillingHdr::updAmtReff($detail['billhdr_id'], $detail['amt']);
                        $partnerBalId = $this->partnerBalanceService->updFromPayment($headerData, $detail);
                        $detail['partnerbal_id'] = $partnerBalId;
                        $paymentDtl->partnerbal_id = $partnerBalId;
                        $paymentDtl->save();

                        // Update order status to 'T' (BILL) when the related billing is fully paid
                        $billing = BillingHdr::find($detail['billhdr_id']);
                        if ($billing) {
                            $outstanding = ($billing->amt + ($billing->amt_shipcost ?? 0)) - ($billing->amt_reff ?? 0);
                            if ($outstanding <= 0) {
                                $order = OrderHdr::where('tr_code', $billing->tr_code)->where('tr_type', 'SO')->first();
                                if ($order && $order->status_code !== TrdStatus::BILL) {
                                    $order->status_code = TrdStatus::BILL;
                                    $order->save();
                                }
                            }
                        }
                    }
                }
            }
            $savedIds[] = $detail['id'];
        }
        unset($detail);

        foreach ($dbPaymentDtl as $dbData) {
            if (!in_array($dbData->id, $savedIds)) {
                $this->partnerBalanceService->delPartnerLog(0, $dbData->id);
                BillingHdr::updAmtReff($dbData->billhdr_id, -$dbData->amt);
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
        foreach ($sourceData as $source) {
            $source['trhdr_id'] = $headerData["id"];

            if (!isset($source['id']) || empty($source['id'])) {
                $source['tr_seq'] = PaymentSrc::getNextTrSeq($headerData['id']);
                $paymentSrc = new PaymentSrc($source);
                $paymentSrc->fill($source);
                $paymentSrc->save();

                if ($paymentSrc->bank_code == ConfigConst::getAppEnv('CHEQUE_DEPOSIT')) {
                    $paymentSrc->reff_id = $paymentSrc->id;
                    $paymentSrc->reff_type = $paymentSrc->tr_type;
                    $paymentSrc->reff_code = $paymentSrc->tr_code;
                    $paymentSrc->save();
                    $source['reff_id'] = $paymentSrc->id;
                    $source['reff_type'] = $paymentSrc->tr_type;
                    $source['reff_code'] = $paymentSrc->tr_code;
                }

                $source['id'] = $paymentSrc->id;
                $partnerBalId = $this->partnerBalanceService->updFromPayment($headerData, $source);
                $paymentSrc->partnerbal_id = $partnerBalId;
                $paymentSrc->save();
            } else {
                $paymentSrc = $dbPaymentSrc->where('id', $source['id'])->first();
                // dd($paymentSrc, $source);
                if ($paymentSrc) {
                    $paymentSrc->fill($source);
                    if ($paymentSrc->isDirty()) {
                        $this->partnerBalanceService->delPartnerLog(0, $source['id']);
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
                $this->partnerBalanceService->delPartnerLog(0, $dbData->id);
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

        $dbPaymentAdv = PaymentAdv::where('trhdr_id', $headerData['id'])
            ->whereColumn('reff_id', '<>', 'id')->get();
        $savedIds = [];
        foreach ($advanceData as &$advance) {
            $advance['trhdr_id'] = $headerData['id'];

            // Buat amt menjadi negatif agar menjadi positive saat updfromPayment
            $advance['amt'] = -abs($advance['amt']);
            $advance['amt_base'] = -abs($advance['amt_base']);

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
                        $this->partnerBalanceService->delPartnerLog(0, $advance['id']);
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
                $this->partnerBalanceService->delPartnerLog(0, $dbData->id);
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

        $dbPaymentAdv = PaymentAdv::where('trhdr_id', $headerData['id'])
            ->whereColumn('reff_id', '=', 'id')->first();

        if ($overAmt == 0) {
            if ($dbPaymentAdv) {
                $this->partnerBalanceService->delPartnerLog(0, $dbPaymentAdv->id);
                $dbPaymentAdv->delete();
            }
            return;
        }

        if ($dbPaymentAdv) {
            $overPaymentData = $dbPaymentAdv->toArray();
            // dd($overPaymentData);
            $overPaymentData['amt'] = $overAmt;
            $overPaymentData['amt_base'] = $overAmt / ($headerData['curr_rate'] ?? 1);
        } else {
            $overPaymentData = [
                'trhdr_id' => $headerData['id'],
                'tr_type' => $headerData['tr_type'] . 'A',
                'tr_code' => $headerData['tr_code'],
                'adv_type_code' => 'ARADVPAY',
                'adv_type_id' => app(ConfigService::class)->getConstIdByStr1('TRX_PAYMENT_TYPE_ADVS', 'ARADVPAY'),
                'reff_type' => $headerData['tr_type'] . 'A',
                'reff_code' => $headerData['tr_code'],
                'amt' => $overAmt, // Pakai positif untuk over payment (sisa pembayaran)
                'amt_base' => $overAmt / ($headerData['curr_rate'] ?? 1),
            ];
        }
        // dd($overPaymentData);
        // Perbaiki pengecekan kunci 'id' (sebelumnya 'id]' menyebabkan selalu dianggap kosong)
        if (!isset($overPaymentData['id']) || empty($overPaymentData['id'])) {
            $overPaymentData['tr_seq'] = PaymentAdv::getNextTrSeq($headerData['id']);
            $paymentAdv = new PaymentAdv();
            $paymentAdv->fill($overPaymentData);
            $paymentAdv->save();
            $overPaymentData['id'] = $paymentAdv->id;
            $overPaymentData['reff_id'] = $paymentAdv->id;
            $paymentAdv->reff_id = $paymentAdv->id;
            $partnerBalId = $this->partnerBalanceService->updFromOverPayment($headerData, $overPaymentData);
            $paymentAdv->partnerbal_id = $partnerBalId;
            $paymentAdv->save();
        } else {
            $paymentAdv = $dbPaymentAdv->where('id', $overPaymentData['id'])->first();
            $paymentAdv->fill($overPaymentData);
            if ($paymentAdv->isDirty()) {
                $this->partnerBalanceService->delPartnerLog(0, $paymentAdv->id);
                $paymentAdv->save();
                $partnerBalId = $this->partnerBalanceService->updFromOverPayment($headerData, $overPaymentData);
                $paymentAdv->partnerbal_id = $partnerBalId;
                $paymentAdv->save();
            }
        }
        return;
    }

    public function delPayment(int $paymentId)
    {
        PaymentHdr::findOrFail($paymentId)->forceDelete();

        // Update billing & hapus detail
        PaymentDtl::where('trhdr_id', $paymentId)->get()
            ->each(
                function ($detail) {
                    BillingHdr::updAmtReff($detail->billhdr_id, -$detail->amt);
                    $detail->delete();
                }
            );

        // Hapus sumber & adv payment
        PaymentSrc::where('trhdr_id', $paymentId)->delete();
        PaymentAdv::where('trhdr_id', $paymentId)->delete();

        // Hapus partner log
        app(PartnerBalanceService::class)->delPartnerLog($paymentId);
    }
}
