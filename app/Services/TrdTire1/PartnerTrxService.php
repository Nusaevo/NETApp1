<?php

namespace App\Services\TrdTire1;

use Exception;
use App\Models\TrdTire1\Transaction\{PartnertrDtl, PartnertrHdr, PaymentSrc};
use Illuminate\Support\Facades\Log;


class PartnerTrxService
{
    protected $partnerBalanceService;

    public function __construct(PartnerBalanceService $partnerBalanceService)
    {
        $this->partnerBalanceService = $partnerBalanceService;
    }


    public function savePartnerTrx(array $headerData, array $detailData)
    {
        $header = $this->saveHeaderTrx($headerData);
        $headerData['id'] = $header->id;

        // dd($headerData, $detailData);
        $details = $this->saveDetailTrx($headerData, $detailData);

        return [
            'header' => $header,
            'detail' => $details
        ];
    }

    private function saveHeaderTrx(array $headerData)
    {
        if (!isset($headerData['id']) || empty($headerData['id'])) {
            $partnertrHdr = PartnertrHdr::create($headerData);
        } else {
            $partnertrHdr = PartnertrHdr::findOrFail($headerData['id']);
            $partnertrHdr->fill($headerData);
            if ($partnertrHdr->isDirty()) {
                $partnertrHdr->save();
            }
        }
        return $partnertrHdr;
    }

    private function saveDetailTrx(array $headerData, array $detailData)
    {
        // dd($headerData, $detailData);
        if (!isset($headerData['id']) || empty($headerData['id'])) {
            throw new Exception('Header ID is required to save details');
        }

        $dbPartnertrDtl = PartnertrDtl::where('trhdr_id', $headerData['id'])->get();

        $savedIds = [];
        foreach ($detailData as &$detail){
            $detail['trhdr_id'] = $headerData['id'];
            $detail['tr_type'] = $headerData['tr_type'];
            $detail['tr_code'] = $headerData['tr_code'];

            if (!isset($detail['id']) || empty($detail['id'])) {
                if ($detail['tr_type'] == 'CQDEP' || $detail['tr_type'] == 'CQREJ') {
                    // Validasi data yang diperlukan untuk transaksi berpasangan
                    if (!isset($detail['partner_id2']) || !isset($detail['partner_code2'])) {
                        throw new Exception('partner_id2 dan partner_code2 diperlukan untuk transaksi CQDEP/CQREJ');
                    }

                    $detail['tr_seq'] = PartnertrDtl::getNextTrSeq($headerData['id']);

                    $detail1 = $detail;
                    $detail1['tr_seq'] = - $detail['tr_seq'];
                    $detail1['amt'] = - $detail['amt'];
                    $partnertrDtl = new PartnertrDtl();
                    $partnertrDtl->fill($detail1);
                    $partnertrDtl->save();
                    $detail['id'] = $partnertrDtl->id;

                    $detail1['id'] = $partnertrDtl->id;
                    $partnerBalId = $this->partnerBalanceService->updFromPartnerTrx($headerData, $detail1);
                    $partnertrDtl->partnerbal_id = $partnerBalId;
                    $partnertrDtl->save();

                    $savedIds[] = $partnertrDtl->id;

                    $detail2 = $detail;
                    $detail2['partner_id'] = $detail['partner_id2'];
                    $detail2['partner_code'] = $detail['partner_code2'];
                    $partnertrDtl = new PartnertrDtl();
                    $partnertrDtl->fill($detail2);
                    $partnertrDtl->save();
                    $detail['id2'] = $partnertrDtl->id;

                    $detail2['id'] = $partnertrDtl->id;
                    $partnerBalId = $this->partnerBalanceService->updFromPartnerTrx($headerData, $detail2);
                    $partnertrDtl->partnerbal_id = $partnerBalId;
                    $partnertrDtl->save();

                    if ($detail['tr_type'] == 'CQDEP') {
                        PaymentSrc::where('id', $detail['reff_id'])->update(['status_code' => 'D']);
                    } elseif ($detail['tr_type'] == 'CQREJ') {
                        PaymentSrc::where('id', $detail['reff_id'])->update(['status_code' => 'J']);
                    }

                    $savedIds[] = $partnertrDtl->id;

                } else if ($detail['tr_type'] == 'ARA' || $detail['tr_type'] == 'APA') {
                    $detail['tr_seq'] = PartnertrDtl::getNextTrSeq($headerData['id']);

                    $partnertrDtl = new PartnertrDtl();
                    $partnertrDtl->fill($detail);
                    $partnertrDtl->save();
                    $detail['id'] = $partnertrDtl->id;
                    $partnerBalId = $this->partnerBalanceService->updFromPartnerTrx($headerData, $detail);
                    $partnertrDtl->partnerbal_id = $partnerBalId;
                    $partnertrDtl->save();

                    $savedIds[] = $partnertrDtl->id;
                }
            // Buat Update
            } else {
                if ($detail['tr_type'] == 'CQDEP' || $detail['tr_type'] == 'CQREJ') {
                    $partnertrDtl = $dbPartnertrDtl->where('trhdr_id', -$detail['tr_seq'])->first();
                    $detail1 = $detail;
                    $partnertrDtl->fill($detail1);
                    if ($partnertrDtl->isDirty()) {
                        $this->partnerBalanceService->delPartnerLog(0, $detail1['id']);
                        $partnertrDtl->save();
                        $partnerBalId = $this->partnerBalanceService->updFromPartnerTrx($headerData, $detail1);
                        $partnertrDtl->partnerbal_id = $partnerBalId;
                        $partnertrDtl->save();
                    }
                    $savedIds[] = $partnertrDtl->id;

                    $partnertrDtl = $dbPartnertrDtl->where('trhdr_id', $detail['tr_seq'])->first();
                    $detail2 = $detail;
                    $detail2['partner_id'] = $detail['partner_id2'];
                    $detail2['partner_code'] = $detail['partner_code2'];
                    $partnertrDtl->fill($detail2);
                    if ($partnertrDtl->isDirty()) {
                        $this->partnerBalanceService->delPartnerLog(0, $detail2['id']);
                        $partnertrDtl->save();
                        $partnerBalId = $this->partnerBalanceService->updFromPartnerTrx($headerData, $detail2);
                        $partnertrDtl->partnerbal_id = $partnerBalId;
                        $partnertrDtl->save();
                    }
                    $savedIds[] = $partnertrDtl->id;
                } else if ($detail['tr_type'] == 'ARA' || $detail['tr_type'] == 'APA') {
                    $partnertrDtl = $dbPartnertrDtl->where('id', $detail['id'])->first();
                    if ($partnertrDtl) {
                        $partnertrDtl->fill($detail);
                        if ($partnertrDtl->isDirty()) {
                            $this->partnerBalanceService->delPartnerLog(0, $detail['id']);
                            $partnertrDtl->save();
                            $partnerBalId = $this->partnerBalanceService->updFromPartnerTrx($headerData, $detail);
                            $partnertrDtl->partnerbal_id = $partnerBalId;
                            $partnertrDtl->save();
                        }
                    }
                    $savedIds[] = $partnertrDtl->id;
                }
            }
        }
        unset($detail);

        foreach ($dbPartnertrDtl as $existing) {
            if (!in_array($existing->id, $savedIds)) {
                $this->partnerBalanceService->delPartnerLog(0, $existing->id);
                if ($existing->tr_type == 'CQDEP' && $existing->tr_seq < 0) {
                    PaymentSrc::where('id', $existing->reff_id)->update(['status_code' => 'O']);
                } elseif ($existing->tr_type == 'CQREJ' && $existing->tr_seq > 0) {
                    PaymentSrc::where('id', $existing->reff_id)->update(['status_code' => 'D']);
                }
                $existing->delete();
            }
        }
        return $savedIds;
    }

    public function delPartnerTrx($headerId)
    {
        if (empty($headerId)) {
            throw new Exception('Header ID is required to delete transaction');
        }

        PartnertrHdr::where('id', $headerId)->forceDelete();

        $this->partnerBalanceService->delPartnerLog($headerId);
        $partnertrDtls = PartnertrDtl::where('trhdr_id', $headerId)->get();
        foreach ($partnertrDtls as $detail) {
            if ($detail->tr_type == 'CQDEP' && $detail->tr_seq < 0) {
                PaymentSrc::where('id', $detail->reff_id)->update(['status_code' => 'O']);
            } elseif ($detail->tr_type == 'CQREJ' && $detail->tr_seq > 0) {
                PaymentSrc::where('id', $detail->reff_id)->update(['status_code' => 'D']);
            }
            $detail->delete();
        }

        return true;
    }
}
