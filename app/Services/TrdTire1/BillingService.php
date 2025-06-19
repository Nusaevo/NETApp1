<?php

namespace App\Services\TrdTire1;
use Illuminate\Support\Facades\DB;
use App\Models\TrdTire1\Transaction\BillingDtl;
use App\Models\TrdTire1\Transaction\BillingHdr;

class BillingService
{
    protected $deliveryService;
    protected $partnerBalanceService;

    public function __construct(DeliveryService $deliveryService, PartnerBalanceService $partnerBalanceService)
    {
        $this->deliveryService = $deliveryService;
        $this->partnerBalanceService = $partnerBalanceService;
    }

    public function addBilling(array $headerData, array $detailData)
    {
        // Simpan header
        $this->saveHeader($headerData);
        $this->saveDetail($headerData, $detailData);
    }

    public function updBilling(int $billingId, array $headerData, array $detailData)
    {
        // Update header
        $this->partnerBalanceService->delPartnerLog('-', $billingId);
        $billingHdr = $this->saveHeader($headerData);
        $this->deleteDetail($billingId);
        $this->saveDetail($headerData, $detailData);
    }

    public function delBilling(int $billingId)
    {
        $this->deleteDetail($billingId);
        $this->deleteHeader($billingId);
    }

    private function saveHeader(array $headerData)
    {
        $billingHdr = BillingHdr::findOrFail($$headerData['id'] ?? null);
        if ($billingHdr) {
            $billingHdr->update($headerData);
        } else {
            $billingHdr = BillingHdr::create($headerData);
        }
        $this->partnerBalanceService->updPartnerBalance('+', $headerData);
    }

    private function saveDetail(array $headerData, array $detailData)
    {
        foreach ($detailData as $detail) {
            // Simpan detail
            $billingDetail = new BillingDtl($detail);
            $billingDetail->save();
            // Update qty_reff di DelivDtl
            if ($billingDetail->dlvdtl_id) {
                $this->deliveryService->updQtyReff('+', $billingDetail->qty, $billingDetail->dlvdtl_id);
            }
        }
    }

    private function deleteHeader(int $billingId)
    {
        $billingHdr = BillingHdr::findOrFail($billingId);
        $billingHdr->delete();
        $this->partnerBalanceService->delPartnerLog('-', $billingId);
    }

    private function deleteDetail(int $billingId)
    {
        // Get existing details
        $existingDetails = billingDtl::where('trhdr_id', $billingId)->get();

        // Delete onhand and reservation for each detail
        foreach ($existingDetails as $detail) {
            $this->deliveryService->updQtyReff('-', $detail->qty, $detail->dlvdtl_id);
            $detail->forceDelete();
        }
    }
}