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
use Illuminate\Support\Facades\Session;

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

        // dd('addBilling called with headerData:', $headerData, 'and detailData:', $detailData);
        $billingHdr = $this->saveHeader($headerData);

        $headerData['id'] = $billingHdr->id;

        foreach ($detailData as &$detail) {
            $detail['trhdr_id'] = $billingHdr->id;
            $detail['tr_type'] = $billingHdr->tr_type;
            $detail['tr_code'] = $billingHdr->tr_code;
        }
        unset($detail);
        // dd($newDetailData);
        $this->saveDetail($headerData, $detailData);
    }

    public function updBilling(int $billingId, array $headerData, array $detailData)
    {
        // Pastikan headerData memiliki ID untuk update
        $headerData['id'] = $billingId;

        // Hapus partner balance log lama
        $this->partnerBalanceService->delPartnerLog($billingId);

        // Update header (akan membuat partner balance baru)
        $billingHdr = $this->saveHeader($headerData);

        // Hapus detail lama
        $this->deleteDetail($billingId);

        // Update detailData dengan info header yang terbaru
        foreach ($detailData as &$detail) {
            $detail['trhdr_id'] = $billingHdr->id;
            $detail['tr_type'] = $billingHdr->tr_type;
            $detail['tr_code'] = $billingHdr->tr_code;
        }
        unset($detail);

        // Simpan detail baru
        $this->saveDetail($headerData, $detailData);
    }

    public function delBilling(int $billingId)
    {
        $this->deleteDetail($billingId);
        $this->deleteHeader($billingId);
    }

    public function addfromDelivery(int $deliveryId)
    {
        $dataBilling = $this->prepareDataFromDelivery($deliveryId);
        $headerData = $dataBilling['headerData'];
        $detailData = $dataBilling['detailData'];
        // Simpan billing menggunakan method yang sudah ada
        $this->addBilling($headerData, $detailData);
    }

    public function updFromDelivery(int $deliveryId)
    {
        $dataBilling = $this->prepareDataFromDelivery($deliveryId);
        $headerData = $dataBilling['headerData'];
        $detailData = $dataBilling['detailData'];

        $billingDtl = BillingDtl::where('dlvhdr_id', $deliveryId)->first();
        // $billingHdr = BillingHdr::find($billingDtl->trhdr_id);
        // dd($billingDtl, $headerData, $detailData);
        // 4. Update billing
        $this->updBilling($billingDtl->trhdr_id, $headerData, $detailData);
    }

    public function delFromDelivery(int $deliveryId)
    {
        // Ambil data delivery header
        $delivHdr = DelivHdr::findOrFail($deliveryId);

        // Cari billing yang terkait dengan delivery berdasarkan tr_code
        $billingHdr = BillingHdr::where('tr_code', $delivHdr->tr_code)->first();
        if ($billingHdr) {
            // Hapus billing yang terkait
            $this->delBilling($billingHdr->id);
        }
    }

    private function prepareDataFromDelivery($deliveryId): array
    {
        $sql = "select
                CASE WHEN dh.tr_type='PD' THEN 'APB' WHEN dh.tr_type='SD' THEN 'ARB' ELSE '' END tr_type,
                dh.tr_date, '' tr_code, '' reff_code, dh.partner_id, dh.partner_code,oh.payment_term_id,oh.payment_term,oh.payment_due_days,
                oh.curr_id,oh.curr_code,oh.curr_rate, null print_date
                ,dh.id dlvhdr_id, dd.id dlvdtl_id, dh.tr_type dlvhdrtr_type, dh.tr_code dlvhdrtr_code, dd.tr_seq dlvdtltr_seq,
                dd.matl_id, dd.matl_code, dd.matl_uom, dd.matl_descr,
                dd.qty,od.qty_uom,od.qty_base,od.price_uom,od.price_base, 'O' status_code,
                ROUND(od.price*(1-od.disc_pct/100), 5) as price,
                case when oh.tax_flag in('I', 'N') then ROUND(od.price*(1-od.disc_pct/100)*dd.qty, 5)
                when oh.tax_flag='E' then ROUND(od.price*(1-od.disc_pct/100)*dd.qty *(1+oh.tax_pct/100), 5)
                else 0 end amt
                from deliv_dtls dd
                join deliv_hdrs dh on dh.id=dd.trhdr_id
                join order_dtls od on od.id=dd.reffdtl_id
                join order_hdrs oh on oh.id=od.trhdr_id
                where dd.trhdr_id= ?";
        $dataBilling = DB::connection(Session::get('app_code'))->select($sql, [$deliveryId]);
        // dd($dataBilling);

        $header = (array) $dataBilling[0];

        // Generate tr_code billing yang baru
        $billingTrType = $header['tr_type'];
        $newTrCode = $this->generateBillingCode($billingTrType);

        $headerData = [
            'tr_code' => $newTrCode, // Gunakan kode billing yang baru
            'tr_type' => $header['tr_type'],
            'tr_date' => $header['tr_date'],
            'reff_code' => $header['reff_code'],
            'partner_id' => $header['partner_id'],
            'partner_code' => $header['partner_code'],
            'payment_term_id' => $header['payment_term_id'],
            'payment_term' => $header['payment_term'],
            'payment_due_days' => $header['payment_due_days'],
            'curr_id' => $header['curr_id'],
            'curr_code' => $header['curr_code'],
            'curr_rate' => $header['curr_rate'],
            'print_date' => $header['print_date'],
            'amt_reff' => 0,
            'status_code' => $header['status_code'],
        ];
        // dd($headerData);

        $totalAmount = 0;
        $detailData = [];
        $data = 0;
        foreach ($dataBilling as $detail) {
            $detailData[] = [
                'tr_seq' => $data += 1,
                'dlvhdr_id' => $detail->dlvhdr_id,
                'dlvdtl_id' => $detail->dlvdtl_id,
                'dlvhdrtr_type' => $detail->dlvhdrtr_type,
                'dlvhdrtr_code' => $detail->dlvhdrtr_code,
                'dlvdtltr_seq' => $detail->dlvdtltr_seq,
                'matl_id' => $detail->matl_id,
                'matl_code' => $detail->matl_code,
                'matl_uom' => $detail->matl_uom,
                'descr' => $detail->matl_descr,
                'qty' => (float) $detail->qty,
                'qty_uom' => $detail->qty_uom,
                'qty_base' => (float) $detail->qty_base,
                'price' => (float) $detail->price,
                'price_uom' => $detail->price_uom,
                'price_base' => (float) $detail->price_base,
                'amt' => (float) $detail->amt,
                'amt_reff' => 0,
            ];
            $totalAmount += (float) $detail->amt;
        }

        // dd($headerData, $detailData);
        // Update total amount di header
        $headerData['total_amt'] = $totalAmount;
        return [
            'headerData' => $headerData,
            'detailData' => $detailData
        ];
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

        // Pastikan headerData ada id-nya sebelum update partner balance
        $headerData['id'] = $billingHdr->id;
        $this->partnerBalanceService->updPartnerBalance('+', $headerData);
        return $billingHdr;
    }

    private function saveDetail(array $headerData, array $detailData)
    {
        foreach ($detailData as $detail) {
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
        $billingHdr->forceDelete();
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
}
