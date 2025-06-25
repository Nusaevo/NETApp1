<?php

namespace App\Services\TrdTire1;
use Illuminate\Support\Facades\DB;
use App\Models\TrdTire1\Transaction\BillingDtl;
use App\Models\TrdTire1\Transaction\BillingHdr;
use App\Models\TrdTire1\Transaction\DelivHdr;
use App\Models\TrdTire1\Transaction\DelivDtl;
use App\Models\TrdTire1\Transaction\OrderHdr;
use App\Models\TrdTire1\Master\Material;
use App\Models\TrdTire1\Master\MatlUom;

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
        // dd($headerData, $detailData);
        // Simpan header
        $billingHdr = $this->saveHeader($headerData);

        // Update headerData dengan ID yang baru dibuat
        $headerData['id'] = $billingHdr->id;

        $this->saveDetail($headerData, $detailData);
        return $billingHdr;
    }

    public function updBilling(int $billingId, array $headerData, array $detailData)
    {
        // Update header
        $this->partnerBalanceService->delPartnerLog($billingId);
        $billingHdr = $this->saveHeader($headerData);
        $this->deleteDetail($billingId);
        $this->saveDetail($headerData, $detailData);
    }

    public function delBilling(int $billingId)
    {
        $this->deleteDetail($billingId);
        $this->deleteHeader($billingId);
    }

    public function addfromDelivery(int $deliveryId)
    {
        $delivHdr = DelivHdr::findOrFail($deliveryId);

        // Ambil data delivery detail
        $delivDtls = DelivDtl::where('trhdr_id', $deliveryId)->get();

        // Tentukan tr_type billing berdasarkan tr_type delivery
        $billingTrType = $this->getBillingTrType($delivHdr->tr_type);

        // Generate tr_code otomatis
        $trCode = $this->generateBillingCode($billingTrType);

        // Ambil partner data
        $partner = $delivHdr->Partner;
        $partnerCode = $partner ? $partner->code : '';

        // Ambil payment term dari OrderHdr berdasarkan reff_code
        $orderHdr = null;
        $paymentTermId = null;
        $paymentTerm = '';
        $paymentDueDays = 0;

        if (!empty($delivHdr->reff_code)) {
            $orderHdr = OrderHdr::find($delivHdr->reff_code);
            if ($orderHdr) {
                $paymentTermId = $orderHdr->payment_term_id;
                $paymentTerm = $orderHdr->payment_term ?? '';
                $paymentDueDays = $orderHdr->payment_due_days ?? 0;
            }
        }

        // Siapkan header data untuk billing
        $headerData = [
            'tr_code' => $trCode,
            'tr_type' => $billingTrType,
            'tr_date' => $delivHdr->tr_date,
            'reff_code' => $delivHdr->tr_code, // Referensi ke delivery
            'partner_id' => $delivHdr->partner_id,
            'partner_code' => $partnerCode,
            'payment_term_id' => $paymentTermId,
            'payment_term' => $paymentTerm,
            'payment_due_days' => $paymentDueDays,
            'curr_id' => 0,
            'curr_code' => '',
            'curr_rate' => 1,
            'print_date' => null,
            'total_amt' => 0
        ];

        // Siapkan detail data untuk billing
        $detailData = [];
        $totalAmount = 0;

        foreach ($delivDtls as $index => $delivDtl) {
            // Ambil data material untuk mendapatkan harga
            $material = Material::find($delivDtl->matl_id);
            $matlUom = MatlUom::where('matl_id', $delivDtl->matl_id)
                ->where('matl_uom', $delivDtl->matl_uom)
                ->first();

            $price = $matlUom ? ($matlUom->selling_price ?? 0) : 0;
            $amount = $delivDtl->qty * $price;
            $totalAmount += $amount;

            $detailData[] = [
                'trhdr_id' => null, // Akan diisi setelah header dibuat
                'tr_type' => $billingTrType,
                'tr_code' => $trCode,
                'tr_seq' => $index + 1,
                'dlvdtl_id' => $delivDtl->id,
                'dlvhdrtr_type' => $delivHdr->tr_type,
                'dlvhdrtr_id' => $delivHdr->id,
                'dlvhdrtr_code' => $delivHdr->tr_code,
                'dlvdtltr_seq' => $delivDtl->tr_seq,
                'matl_id' => $delivDtl->matl_id,
                'matl_code' => $delivDtl->matl_code,
                'matl_uom' => $delivDtl->matl_uom,
                'descr' => $delivDtl->matl_descr ?? '',
                'qty' => $delivDtl->qty,
                'qty_uom' => $delivDtl->matl_uom,
                'qty_base' => $delivDtl->qty,
                'price' => $price,
                'price_uom' => $delivDtl->matl_uom,
                'price_base' => '',
                'amt' => $amount,
                'amt_reff' => $amount,
                'status_code' => 'O' // Open status
            ];
        }

        // Update total amount di header
        $headerData['total_amt'] = $totalAmount;

        // Simpan billing menggunakan method yang sudah ada
        return $this->addBilling($headerData, $detailData);
    }

    /**
     * Generate kode billing otomatis dengan format: TRTYPE + 3 digit urut
     */
    private function generateBillingCode(string $billingTrType): string
    {
        $lastBilling = BillingHdr::where('tr_type', $billingTrType)
            ->orderByDesc('tr_code')
            ->first();
        if ($lastBilling && preg_match('/\d+$/', $lastBilling->tr_code, $matches)) {
            $lastNumber = intval($matches[0]);
        } else {
            $lastNumber = 0;
        }
        $newNumber = $lastNumber + 1;
        return $billingTrType . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Mendapatkan tr_type billing berdasarkan tr_type delivery
     */
    private function getBillingTrType(string $deliveryTrType): string
    {
        switch ($deliveryTrType) {
            case 'PD': // Purchase Delivery
                return 'APB'; // Accounts Payable Billing
            case 'SD': // Sales Delivery
                return 'ARB'; // Accounts Receivable Billing
            default:
                return 'ARB'; // Default ke ARB
        }
    }

    private function saveHeader(array $headerData)
    {
        $billingHdr = null;
        if (!empty($headerData['id'])) {
            $billingHdr = BillingHdr::find($headerData['id']);
        }
        if ($billingHdr) {
            $billingHdr->update($headerData);
        } else {
            $billingHdr = BillingHdr::create($headerData);
        }

        $this->partnerBalanceService->updPartnerBalance('+', $headerData);
        return $billingHdr;
    }

    private function saveDetail(array $headerData, array $detailData)
    {
        foreach ($detailData as $detail) {
            // Set trhdr_id jika belum diisi
            if (empty($detail['trhdr_id']) && !empty($headerData['id'])) {
                $detail['trhdr_id'] = $headerData['id'];
            }

            // Simpan detail
            $billingDetail = new BillingDtl($detail);
            $billingDetail->save();

            // Update qty_reff di DelivDtl jika ada dlvdtl_id
            if (!empty($billingDetail->dlvdtl_id)) {
                $this->deliveryService->updDelivQtyReff('+', $billingDetail->qty, $billingDetail->dlvdtl_id);
            }
        }
    }

    private function deleteHeader(int $billingId)
    {
        $billingHdr = BillingHdr::findOrFail($billingId);
        $billingHdr->delete();
        $this->partnerBalanceService->delPartnerLog($billingId);
    }

    private function deleteDetail(int $billingId)
    {
        // Get existing details
        $existingDetails = BillingDtl::where('trhdr_id', $billingId)->get();

        // Delete onhand and reservation for each detail
        foreach ($existingDetails as $detail) {
            if (!empty($detail->dlvdtl_id)) {
                $this->deliveryService->updDelivQtyReff('-', $detail->qty, $detail->dlvdtl_id);
            }
            $detail->forceDelete();
        }
    }
}
