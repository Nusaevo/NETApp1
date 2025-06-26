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
        $sql = "select
                CASE WHEN dh.tr_type='PD' THEN 'APB' WHEN dh.tr_type='SD' THEN 'ARB' ELSE '' END tr_type,
                dh.tr_date, dh.tr_code, '' reff_code, dh.partner_id, dh.partner_code,oh.payment_term_id,oh.payment_term,oh.payment_due_days,
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
        $headerData = [
            'tr_code' => $header['tr_code'],
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

        // Simpan billing menggunakan method yang sudah ada
        $this->addBilling($headerData, $detailData);
    }

    public function updFromDelivery(int $deliveryId)
    {
        $sql = "select
                CASE WHEN dh.tr_type='PD' THEN 'APB' WHEN dh.tr_type='SD' THEN 'ARB' ELSE '' END tr_type,
                dh.tr_date, dh.tr_code, '' reff_code, dh.partner_id, dh.partner_code,oh.payment_term_id,oh.payment_term,oh.payment_due_days,
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

        if (empty($dataBilling)) {
            throw new \Exception('Data billing tidak ditemukan');
        }
        $first = (array) $dataBilling[0];
        $headerData = [
            'tr_code' => $first['tr_code'],
            'tr_type' => $first['tr_type'],
            'tr_date' => $first['tr_date'],
            'reff_code' => $first['reff_code'],
            'partner_id' => $first['partner_id'],
            'partner_code' => $first['partner_code'],
            'payment_term_id' => $first['payment_term_id'],
            'payment_term' => $first['payment_term'],
            'payment_due_days' => $first['payment_due_days'],
            'curr_id' => $first['curr_id'],
            'curr_code' => $first['curr_code'],
            'curr_rate' => $first['curr_rate'],
            'print_date' => $first['print_date'],
            'amt_reff' => 0,
            'status_code' => $first['status_code'],
        ];

        $totalAmount = 0;
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

        // Update total amount di header
        $headerData['total_amt'] = $totalAmount;

        $billingHdr = BillingHdr::where('dlvhdr_id', $deliveryId)->first();
        // 4. Update billing
        $this->updBilling($billingHdr->id, $headerData, $detailData);
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
