<?php

namespace App\Services\TrdTire2;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Models\TrdTire2\Transaction\DelivHdr;
use App\Models\TrdTire2\Transaction\BillingDtl;
use App\Models\TrdTire2\Transaction\BillingHdr;
use App\Models\TrdTire2\Transaction\BillingDeliv;
use App\Models\TrdTire2\Transaction\BillingOrder;

class BillingService
{
    protected $deliveryService;
    protected $partnerBalanceService;

    public function __construct(DeliveryService $deliveryService, PartnerBalanceService $partnerBalanceService)
    {
        $this->deliveryService = $deliveryService;
        $this->partnerBalanceService = $partnerBalanceService;
    }

    public function saveBilling(array $headerData, array $detailData)
    {
        // dd($headerData, $detailData);
        $result = $this->validateAndMapData($headerData,$detailData);
        // dd($result);

        $dataBillingHdr = $result['data_billing_hdr'];
        $dataBillingDeliv = $result['data_billing_deliv'];
        $dataBillingOrder = $result['data_billing_order'];

        // dd($dataBillingHdr, $dataBillingDeliv, $dataBillingOrder);

        $billingHdr = $this->saveHeader($dataBillingHdr);
        $dataBillingHdr['id'] = $billingHdr->id;

        // dd($dataBillingHdr);

        $billingDeliv = $this->saveBillingDeliv($dataBillingHdr, $dataBillingDeliv);
        $billingOrder = $this->saveBillingOrder($dataBillingHdr, $dataBillingOrder);

        return [
            'billing_hdr' => $billingHdr,
            'billing_deliv' => $billingDeliv,
            'billing_order' => $billingOrder,
        ];
    }

    private function saveHeader(array $dataBillingHdr)
    {
        // dd($dataBillingHdr);
        if (!$dataBillingHdr['id']) {
            $billingHdr = new BillingHdr();
            $billingHdr->fill($dataBillingHdr);
            $billingHdr->save();
            $dataBillingHdr['id'] = $billingHdr->id;
            // Hanya panggil updFromBilling saat create billing baru
            $partnerBalId = $this->partnerBalanceService->updFromBilling($dataBillingHdr);
            $billingHdr->partnerbal_id = $partnerBalId;
            $billingHdr->save();
        } else {
            $billingHdr = BillingHdr::findOrFail($dataBillingHdr['id']);
            $billingHdr->fill($dataBillingHdr);
            if ($billingHdr->isDirty()){
                $billingHdr->save();
            }
        }
        return $billingHdr;
    }

    private function saveBillingDeliv(array $dataBillingHdr, array $dataBillingDeliv)
    {

        $dbBillingDeliv = BillingDeliv::where('trhdr_id', $dataBillingHdr['id'])->get();
        // dd($dataBillingHdr, $dataBillingDeliv);

        $savedIds = [];
        foreach ($dataBillingDeliv as $detail) {
            $billingDeliv = $dbBillingDeliv->where('deliv_id', $detail['deliv_id'])->first();
            if (!$billingDeliv) {
                $detail['trhdr_id'] = $dataBillingHdr['id'];
                $billingDeliv = new BillingDeliv();
                $billingDeliv->fill($detail);
                $billingDeliv->save();
                $detail['id'] = $billingDeliv->id;
                // dd($detail, $dataBillingDeliv, $billingDeliv);
            } else {
                $detail['id'] = $billingDeliv->id;
                $billingDeliv->fill($detail);
                if ($billingDeliv->isDirty()) {
                    $billingDeliv->save();
                }
            }
            // dd($detail);
            DelivHdr::updateBillHdrId($detail['deliv_id'], $dataBillingHdr['id']);
            $savedIds[] = $billingDeliv->id;
        }
        // dd($dataBillingDeliv);
        foreach ($dbBillingDeliv as $existing) {
            if (!in_array($existing->id, $savedIds)) {
                DelivHdr::updateBillHdrId($existing->deliv_id, 0);
                $existing->delete();
            }
        }
        // dd($dataBillingDeliv);
        return $dbBillingDeliv;
    }

    private function saveBillingOrder(array $dataBillingHdr, array $dataBillingOrder)
    {
        // dd($dataBillingHdr, $dataBillingOrder);
        $dbBillingOrder = BillingOrder::where('trhdr_id', $dataBillingHdr['id'])->get();

        $savedIds = [];
        // dd($dataBillingOrder);
        foreach ($dataBillingOrder as $key => $detail) {
            $billingOrder = $dbBillingOrder->where('reffdtl_id', $detail['reffdtl_id'])->first();
            if (!$billingOrder) {
                // Always set trhdr_id to the actual billing header ID
                $detail['trhdr_id'] = $dataBillingHdr['id'];

                // Generate the next sequence number for this billing header
                $detail['tr_seq'] = BillingOrder::getNextTrSeq($detail['trhdr_id']);

                $billingOrder = new BillingOrder();
                $billingOrder->fill($detail);
                $billingOrder->save();
                // dd($detail, $billingOrder);

                $detail['id'] = $billingOrder->id;
                // dd($detail, $billingOrder);
            } else {
                $detail['id'] = $billingOrder->id;
                $detail['tr_seq'] = $billingOrder->tr_seq;
                $billingOrder->fill($detail);
                if ($billingOrder->isDirty()) {
                    $billingOrder->save();
                }
            }

            // dd($detail, $billingOrder);
            $savedIds[] = $billingOrder->id;
        }
        foreach ($dbBillingOrder as $existing) {
            if (!in_array($existing->id, $savedIds)) {
                $existing->delete();
            }
        }
        // dd($detail, $billingOrder);
        return $dbBillingOrder;
    }

    private function validateAndMapData(array $headerData, array $detailData)
    {
        // dd($headerData, $detailData);
        // Requirements for headerData <-----------------------------------------
        if (!array_key_exists('id',$headerData) ||
            empty($headerData['tr_type']) ||
            empty($headerData['tr_code']) ||
            empty($headerData['tr_date']))
        {
            throw new \InvalidArgumentException('Header data is incomplete.');
        }
        // dd($headerData, $detailData);

        $dataBillingDeliv = [];
        $amtShipCost = 0;
        $billingId = 0;
        foreach ($detailData as $detail) {
            // Requirements for each detail <------------------------------------
            if (empty($detail['deliv_id'])) {
                throw new \InvalidArgumentException('Detail data is incomplete.');
            }

            $delivHdr = DelivHdr::where('id','=',$detail['deliv_id'])->first();
            if (!$delivHdr) {
                throw new \InvalidArgumentException('Delivery header not found for ID: ' . $detail['deliv_id']);
            }

            $dataBillingDeliv[] = [
                'id' => 0,
                'trhdr_id' => $delivHdr->billhdr_id,
                'deliv_id' => $delivHdr->id,
                'deliv_type' => $delivHdr->tr_type,
                'deliv_code' => $delivHdr->tr_code,
                'amt_shipcost' => $delivHdr->amt_shipcost,
            ];
            $amtShipCost += $delivHdr->amt_shipcost;
            $billingId = $delivHdr->billhdr_id ?? 0;
        }

        $dataBillingOrder = [];
        $delivIds = array_column($detailData, 'deliv_id');

        if (empty($delivIds)) {
            throw new \InvalidArgumentException('No delivery IDs found in detail data.');
        }

        $connectionName = Session::get('app_code');
        // Subquery builder
        $sub = DB::connection($connectionName)->table('deliv_packings')
            ->select('reffdtl_id', DB::raw('SUM(qty) as qty'))
            ->whereIn('trhdr_id', $delivIds)
            ->groupBy('reffdtl_id');
        // Main query
        $delivPacking = DB::connection($connectionName)
            ->table(DB::raw("({$sub->toSql()}) as d"))
            ->mergeBindings($sub) // penting agar binding dari subquery ikut
            ->join('order_dtls as od', 'od.id', '=', 'd.reffdtl_id')
            ->join('order_hdrs as oh', 'oh.id', '=', 'od.trhdr_id')
            ->selectRaw("
                oh.partner_id,
                oh.partner_code,
                oh.payment_term_id,
                oh.payment_term,
                oh.payment_due_days,
                oh.curr_id,
                oh.curr_code,
                oh.curr_rate,
                od.id as reffdtl_id,
                od.trhdr_id as reffhdr_id,
                od.tr_type as reffhdrtr_type,
                od.tr_code as reffhdrtr_code,
                od.tr_seq as reffdtltr_seq,
                od.matl_descr,
                d.qty,
                od.qty_uom,
                od.qty_base,
                CASE WHEN d.qty=od.qty THEN od.amt ELSE 0 END as amt,
                CASE WHEN d.qty=od.qty THEN od.amt_beforetax ELSE 0 END as amt_beforetax,
                CASE WHEN d.qty=od.qty THEN od.amt_tax ELSE 0 END as amt_tax,
                CASE WHEN d.qty=od.qty THEN od.amt_adjustdtl ELSE 0 END as amt_adjustdtl,
                od.price,
                od.disc_pct,
                oh.tax_code,
                oh.tax_pct
            ")
            ->get();

        if ($delivPacking->isEmpty()) {
            throw new \InvalidArgumentException('No delivery packing data found for billing.');
        }

        $dataBillingHdr = [
            'id'=> $billingId,
            'tr_type' => $headerData['tr_type'],
            'tr_code' => $headerData['tr_code'],
            'tr_date' => $headerData['tr_date'],
            'reff_code' => 0,
            'partner_id' => $delivPacking[0]->partner_id,
            'partner_code' => $delivPacking[0]->partner_code,
            'payment_term_id' => $delivPacking[0]->payment_term_id,
            'payment_term' => $delivPacking[0]->payment_term,
            'payment_due_days' => $delivPacking[0]->payment_due_days,
            'curr_id' => $delivPacking[0]->curr_id,
            'curr_code' => $delivPacking[0]->curr_code,
            'curr_rate' => $delivPacking[0]->curr_rate,
            'partnerbal_id' => 0,
            'amt' => 0,
            'amt_beforetax' => 0,
            'amt_tax' => 0,
            'amt_adjustdtl' => 0,
            'amt_adjusthdr' => 0,
            'amt_shipcost' => $amtShipCost,
            'amt_reff' => 0,
            'print_date' => null,
        ];

        $amt = 0;
        $amtBeforetax = 0;
        $amtTax = 0;
        $amtAdjustdtl = 0;
        foreach ($delivPacking as $key => $packing) {
            $dataBillingOrder[] = [
                'trhdr_id' => 0, // Will be set to actual billing header ID after header is saved
                'tr_type' => $headerData['tr_type'],
                'tr_code' => $headerData['tr_code'],
                'tr_seq' => 0, // Will be set to actual sequence number after header is saved
                'reffdtl_id' => $packing->reffdtl_id,
                'reffhdr_id' => $packing->reffhdr_id,
                'reffhdrtr_type' => $packing->reffhdrtr_type,
                'reffhdrtr_code' => $packing->reffhdrtr_code,
                'reffdtltr_seq' => $packing->reffdtltr_seq,
                'matl_descr' => $packing->matl_descr,
                'qty' => $packing->qty,
                'qty_uom' => $packing->qty_uom,
                'qty_base' => $packing->qty_base,
                'amt' => $packing->amt,
                'amt_beforetax' => $packing->amt_beforetax,
                'amt_tax' => $packing->amt_tax,
                'amt_adjustdtl' => $packing->amt_adjustdtl,
                'amt_reff' => 0,
            ];

            if ($packing->amt == 0) {
                $result = $this->calculateAmounts(
                    $packing->qty,
                    $packing->price,
                    $packing->disc_pct,
                    $packing->tax_pct,
                    $packing->tax_code);
                $dataBillingOrder[$key]['amt'] = $result['amt'];
                $dataBillingOrder[$key]['amt_beforetax'] = $result['amt_beforetax'];
                $dataBillingOrder[$key]['amt_tax'] = $result['amt_tax'];
                $dataBillingOrder[$key]['amt_adjustdtl'] = $result['amt_adjust'];
            }
            $amt += $dataBillingOrder[$key]['amt'];
            $amtBeforetax += $dataBillingOrder[$key]['amt_beforetax'];
            $amtTax += $dataBillingOrder[$key]['amt_tax'];
            $amtAdjustdtl += $dataBillingOrder[$key]['amt_adjustdtl'];
        }
        $dataBillingHdr['amt'] = $amt;
        $dataBillingHdr['amt_beforetax'] = $amtBeforetax;
        $dataBillingHdr['amt_tax'] = $amtTax;
        $dataBillingHdr['amt_adjustdtl'] = $amtAdjustdtl;

        return [
            'data_billing_hdr' => $dataBillingHdr,
            'data_billing_deliv' => $dataBillingDeliv,
            'data_billing_order' => $dataBillingOrder,
        ];

    }

    private function calculateAmounts(float $qty, float $price, float $discPct, float $taxPct, string $taxCode)
    {
        // Calculate basic amount with discount
        $discount = $discPct / 100;
        $tax = $taxPct / 100;
        $priceAfterDisc = $price * (1 - $discount);
        $priceBeforeTax = round($priceAfterDisc / (1 + $tax),0);
        $amtDiscount = round($qty * $price * $discount,0);

        $amt = 0;
        $amtBeforeTax = 0;
        $amtTax = 0;
        if ($taxCode === 'I') {
            // Catatan: khusus untuk yang include PPN
            // DPP dihitung dari harga setelah disc dikurangi PPN dibulatkan ke rupiah * qty
            $amtBeforeTax = $priceBeforeTax * $qty ;
            // PPN dihitung dari DPP * PPN dibulatkan ke rupiah
            $amtTax = round($amtBeforeTax * $tax,0);
            // Total Nota dihiitung dari harga setelah disc * qty
            // selisih yang timbul antara Total Nota dan DPP + PPN diabaikan
            // priceAdjustment
            $amt = $priceAfterDisc * $qty;
        } else if ($taxCode === 'E') {
            $priceBeforeTax = $priceAfterDisc;
            $amtBeforeTax = $priceAfterDisc * $qty;
            $amtTax = round($priceAfterDisc * $qty * $tax,0);
            $amt = $amtBeforeTax + $amtTax;
        } else if ($taxCode === 'N') {
            $priceBeforeTax = $priceAfterDisc;
            $amtBeforeTax = $priceAfterDisc * $qty;
            $amtTax = 0;
            $amt = $amtBeforeTax;
        }
        $amtAdjust = $amt - $amtBeforeTax - $amtTax;

        return [
            'price_afterdisc' => $priceAfterDisc,
            'price_beforetax' => $priceBeforeTax,
            'amt' => $amt,
            'amt_beforetax' => $amtBeforeTax,
            'amt_tax' => $amtTax,
            'amt_adjust' => $amtAdjust,
            'amt_discout' => $amtDiscount,
        ];
    }

    public function delBilling(int $billingId)
    {
        $this->deleteDetail($billingId);
        $this->deleteHeader($billingId);
    }

    private function deleteHeader(int $billingId)
    {
        $this->partnerBalanceService->delPartnerLog($billingId);
        $billingHdr = BillingHdr::findOrFail($billingId);
        $billingHdr->forceDelete();
    }

    private function deleteDetail(int $billingId)
    {
        // Get existing details
        $existingDetails = BillingDeliv::where('trhdr_id', $billingId)->get();
        foreach ($existingDetails as $detail) {
            DelivHdr::updateBillHdrId($detail->deliv_id, 0);
            $detail->delete();
        }
        $existingDetails = BillingOrder::where('trhdr_id', $billingId)->delete();
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
            'tr_type as billhdrtr_type',
            'tr_code as billhdrtr_code',
            DB::raw('tr_date + make_interval(days => payment_due_days) AS due_date'),
            DB::raw('(amt - amt_reff)::int as outstanding_amt')])
            ->where('partner_id', $partnerId)
            ->whereRaw('(amt - amt_reff) > 0')
            ->get();

        return $bills;
    }
}
