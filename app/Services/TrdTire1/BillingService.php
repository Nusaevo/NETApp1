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
        // dd($headerData, $detailData);
        $billingHdr = $this->saveHeader($headerData);

        $headerData['id'] = $billingHdr->id;

        foreach ($detailData as &$detail) {
            $detail['trhdr_id'] = $billingHdr->id;
            $detail['tr_type'] = $billingHdr->tr_type;
            $detail['tr_code'] = $billingHdr->tr_code;
            // $detail['amt_tax'] = (float) $detail['price_beforetax'] * (float) $detail['qty'] * (float) $taxPct / 100;
        }
        unset($detail);
        // dd($detailData);
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
        $taxPct = $billingHdr->tax_pct;

        // Hapus detail lama
        $this->deleteDetail($billingId);

        // Update detailData dengan info header yang terbaru
        foreach ($detailData as &$detail) {
            $detail['trhdr_id'] = $billingHdr->id;
            $detail['tr_type'] = $billingHdr->tr_type;
            $detail['tr_code'] = $billingHdr->tr_code;
            $detail['amt_tax'] = (float) $detail['price_beforetax'] * (float) $detail['qty'] * (float) $taxPct / 100;
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
        // dd($dataBilling);
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
        // Cari semua billing detail yang terkait dengan delivery ini
        $billingDtls = BillingDtl::where('dlvhdr_id', $deliveryId)->get();

        if ($billingDtls->isNotEmpty()) {
            // Ambil trhdr_id yang unik (dalam kasus ada multiple details dengan header yang sama)
            $billingHeaderIds = $billingDtls->pluck('trhdr_id')->unique();

            // Hapus semua billing header yang terkait
            foreach ($billingHeaderIds as $billingId) {
                $this->delBilling($billingId);
            }
        }
    }

    private function prepareDataFromDelivery($deliveryId): array
    {
        $dataBilling = DB::connection(Session::get('app_code'))
            ->table('deliv_dtls as dd')
            ->join('deliv_hdrs as dh', 'dh.id', '=', 'dd.trhdr_id')
            ->join('order_dtls as od', 'od.id', '=', 'dd.reffdtl_id')
            ->join('order_hdrs as oh', 'oh.id', '=', 'od.trhdr_id')
            ->where('dd.trhdr_id', $deliveryId)
            ->selectRaw("
                CASE
                    WHEN dh.tr_type = 'PD' THEN 'APB'
                    WHEN dh.tr_type = 'SD' THEN 'ARB'
                    ELSE ''
                END AS tr_type,
                dh.tr_date,
                dh.tr_code,
                '' AS reff_code,
                dh.partner_id,
                dh.partner_code,
                dh.amt_shipcost,
                oh.payment_term_id,
                oh.payment_term,
                oh.payment_due_days,
                oh.curr_id,
                oh.curr_code,
                oh.curr_rate,
                oh.tax_pct,
                oh.tax_code,
                oh.tax_id,
                NULL AS print_date,
                dh.id AS dlvhdr_id,
                dd.id AS dlvdtl_id,
                dh.tr_type AS dlvhdrtr_type,
                dh.tr_code AS dlvhdrtr_code,
                dd.tr_seq AS dlvdtltr_seq,
                dd.matl_id,
                dd.matl_code,
                dd.matl_uom,
                dd.matl_descr,
                dd.qty,
                od.qty_uom,
                od.qty_base,
                od.price_uom,
                od.price_base,
                'O' AS status_code,
                od.price AS price,
                od.price_afterdisc,
                od.price_beforetax,
                od.price_beforetax * dd.qty AS amt_beforetax,
                ROUND(od.price_beforetax * dd.qty * (oh.tax_pct / 100), 0) AS amt_tax,
                CASE
                    WHEN oh.tax_code IN ('I', 'N') THEN
                        od.price_afterdisc * dd.qty
                    WHEN oh.tax_code = 'E' THEN
                        od.price_afterdisc * dd.qty + ROUND(od.price_beforetax * dd.qty * (1 + oh.tax_pct / 100), 0)
                    ELSE 0
                END AS amt
            ")
            ->get();

        $header = (array) $dataBilling[0];
        // $taxPct = (float) ($header['tax_pct']);
        // $headerData = [];

        // dd($headerData);

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
            'print_date' => null,
            'amt' => 0,
            'amt_beforetax' => 0,
            'amt_tax' => 0,
            'amt_adjustdtl' => 0,
            'amt_reff' => 0,
            'amt_shipcost' => $header['amt_shipcost'],
            'status_code' => $header['status_code'],
            'tax_pct' => $header['tax_pct'],
            'tax_code' => $header['tax_code'],
            'tax_id' => $header['tax_id'],
        ];
        // dd($headerData);

        $detailData = [];
        $trSeq = 1;
        foreach ($dataBilling as $detail) {

            $row = [
                'tr_seq' => $trSeq,
                'tr_code' => $header['tr_code'],
                'dlvhdr_id' => $detail->dlvhdr_id,
                'dlvdtl_id' => $detail->dlvdtl_id,
                'dlvhdrtr_type' => $detail->dlvhdrtr_type,
                'dlvhdrtr_code' => $detail->dlvhdrtr_code,
                'dlvdtltr_seq' => $detail->dlvdtltr_seq,
                'matl_id' => $detail->matl_id,
                'matl_code' => $detail->matl_code,
                'matl_uom' => $detail->matl_uom,
                'descr' => $detail->matl_descr,
                'qty' => $detail->qty,
                'qty_uom' => $detail->qty_uom,
                'qty_base' => (float) $detail->qty_base,
                'price' => (float) $detail->price,
                'price_uom' => $detail->price_uom,
                'price_base' => (float) $detail->price_base,
                'amt' => $detail->amt,
                'amt_tax' => $detail->amt_tax,
                'amt_reff' => 0,
                'price_beforetax' => $detail->price_beforetax,
                'amt_beforetax' => $detail->amt_beforetax,
                'price_afterdisc' => (float) $detail->price_afterdisc,
                'amt_adjustdtl' => (float) $detail->amt - (float) $detail->amt_beforetax - (float) $detail->amt_tax,
            ];
            if ($trSeq == 1) {
                $row['amt_shipcost'] = $header['amt_shipcost'];
            }
            $detailData[] = $row;
            $trSeq++;
            $headerData['amt'] += (float) $detail->amt;
            $headerData['amt_beforetax'] += (float) $detail->amt_beforetax;
            $headerData['amt_tax'] += (float) $detail->amt_tax;
            $headerData['amt_adjustdtl'] += ((float) $detail->amt - (float) $detail->amt_beforetax - (float) $detail->amt_tax);
        }

        // dd($headerData, $detailData);
        // Update total amount di header
        return [
            'headerData' => $headerData,
            'detailData' => $detailData
        ];
    }

    private function saveHeader(array $headerData)
    {
        $billingHdr = null;
        // Pastikan print_date tidak null, gunakan default '1900-01-01' jika kosong
        if (empty($headerData['print_date'])) {
            $headerData['print_date'] = '1900-01-01';
        }
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
        $headerData['reff_id'] = $billingHdr->id;
        $headerData['reff_type'] = $billingHdr->tr_type;
        $headerData['reff_code'] = $billingHdr->tr_code;
        $headerData['amt'] = $billingHdr->amt;
        $headerData['print_date'] = $billingHdr->print_date;

        // Update partner balance dan dapatkan partnerbal_id
        $partnerBalId = $this->partnerBalanceService->updFromBilling($headerData);

        // Update BillingHdr dengan partnerbal_id
        $billingHdr->partnerbal_id = $partnerBalId;
        $billingHdr->save();

        return $billingHdr;
    }

    private function saveDetail(array $headerData, array $detailData)
    {
        // dd($detailData);
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

    public function updAmtReff(string $mode, float $Amt, int $billHdrId)
    {
        // Update amt_reff di BillingHdr
        $billingHdr = BillingHdr::find($billHdrId);
        if ($billingHdr) {
            if ($mode === '+') {
                $billingHdr->amt_reff = ($billingHdr->amt_reff ?? 0) + $Amt;
            } else if ($mode === '-') {
                $billingHdr->amt_reff = ($billingHdr->amt_reff ?? 0) - $Amt;
            }
            $billingHdr->save();
        }
    }

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
     * Ambil daftar BillingHdr yang outstanding untuk partner tertentu,
     * hanya yang (amt - amt_reff) > 0
     */
    public function getOutstandingBillsByPartner($partnerId = null)
    {
        $bills = BillingHdr::select([
                'id as billhdr_id',
                'tr_code as billhdrtr_code',
                DB::raw('tr_date + make_interval(days => payment_due_days) AS due_date'),
                DB::raw('(amt - amt_reff) as outstanding_amt')
            ])
            ->where('partner_id', $partnerId)
            ->get();

        // Pastikan outstanding_amt integer (tanpa ribuan/desimal)
        foreach ($bills as $bill) {
            $bill->outstanding_amt = (int) $bill->outstanding_amt;
        }

        return $bills;
    }
}
