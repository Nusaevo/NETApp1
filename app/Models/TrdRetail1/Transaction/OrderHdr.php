<?php

namespace App\Models\TrdRetail1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdRetail1\Master\Partner;
use App\Models\TrdRetail1\Master\Material;
use App\Models\TrdRetail1\Master\MatlUom;
use App\Models\TrdRetail1\Transaction\{DelivDtl, BillingDtl, DelivHdr, BillingHdr, PaymentHdr, PaymentDtl};
use App\Models\TrdRetail1\Inventories\{IvtBal, IvtLog};
use App\Models\SysConfig1\{ConfigSnum, ConfigConst};
use App\Enums\Status;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderHdr extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'tr_id', 'tr_type', 'tr_date', 'reff_code', 'partner_id', 'partner_code',
        'sales_id', 'sales_code', 'deliv_by', 'payment_term_id', 'payment_term',
        'curr_id', 'curr_code', 'curr_rate', 'status_code',
    ];

    /* ======================================================
     *                     BOOT METHOD
     * ====================================================== */
    protected static function boot()
    {
        parent::boot();

        // Cascade deletes untuk record terkait
        static::deleting(function ($orderHdr) {
            DB::beginTransaction();
            try {
                // Delete related OrderDtl records first with inventory management
                foreach ($orderHdr->OrderDtl as $orderDtl) {
                    $values = $orderHdr->getTrTypeValues($orderDtl->tr_type);

                    $delivDtls = DelivDtl::where('tr_id', $orderDtl->tr_id)
                        ->where('tr_seq', $orderDtl->tr_seq)
                        ->where('tr_type', $values['delivTrType'])
                        ->get();

                    foreach ($delivDtls as $delivDtl) {
                        if (empty($delivDtl->wh_id)) {
                            $warehouse = ConfigConst::where('str1', $delivDtl->wh_code)->first();
                            if ($warehouse) {
                                $delivDtl->wh_id = $warehouse->id;
                            }
                        }
                        $existingBal = IvtBal::where([
                            'matl_id' => $delivDtl->matl_id,
                            'wh_id' => $delivDtl->wh_id,
                            'matl_uom' => $delivDtl->matl_uom,
                            'batch_code' => $delivDtl->batch_code,
                        ])->first();

                        if ($existingBal) {
                            $qtyRevert = match ($delivDtl->tr_type) {
                                'PD' => -$delivDtl->qty,  // Purchase Delivery: reduce stock on deletion
                                'RD' => -$delivDtl->qty,  // Return Delivery: reduce stock on deletion (reverse the stock increase)
                                'SD' => $delivDtl->qty,   // Sales Delivery: increase stock on deletion
                                default => 0,
                            };

                            if ($qtyRevert != 0) {
                                $existingBal->increment('qty_oh', $qtyRevert);
                                IvtLog::removeIvtLogIfExists(
                                    $delivDtl->trhdr_id,
                                    $delivDtl->tr_type,
                                    $delivDtl->tr_seq
                                );
                            }
                        }

                        MatlUom::recalcMatlUomQtyOh($delivDtl->matl_id, $delivDtl->matl_uom);
                        $delivDtl->delete();
                    }

                    BillingDtl::where('tr_id', $orderDtl->tr_id)
                        ->where('tr_seq', $orderDtl->tr_seq)
                        ->where('tr_type', $values['billingTrType'])
                        ->delete();

                    $orderDtl->delete();
                }

                // Delete related DelivHdr
                $delivHdr = $orderHdr->DelivHdr;
                if ($delivHdr) {
                    $delivHdr->delete();
                }

                // Delete related BillingHdr
                $billingHdr = $orderHdr->BillingHdr;
                if ($billingHdr) {
                    $billingHdr->delete();
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        });

        // Contoh: format goldprice_curr saat retrieval
        static::retrieved(function ($model) {
        });
    }

    /* ======================================================
     *                   RELATIONSHIPS
     * ====================================================== */
    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function OrderDtl()
    {
        return $this->hasMany(OrderDtl::class, 'tr_id', 'tr_id')
                    ->where('tr_type', $this->tr_type)
                    ->orderBy('tr_seq');
    }

    public function Materials()
    {
        return $this->hasManyThrough(
            Material::class,
            OrderDtl::class,
            'tr_id',    // FK pada OrderDtl
            'id',       // FK pada Material
            'tr_id',    // Local key pada OrderHdr
            'matl_id'   // Local key pada OrderDtl
        )->where('order_dtls.tr_type', $this->tr_type);
    }

    public function DelivHdr()
    {
        $values = $this->getTrTypeValues($this->tr_type);
        return $this->hasOne(DelivHdr::class, 'tr_id', 'tr_id')
                    ->where('tr_type', $values['delivTrType']);
    }

    public function BillingHdr()
    {
        $values = $this->getTrTypeValues($this->tr_type);
        return $this->hasOne(BillingHdr::class, 'tr_id', 'tr_id')
                    ->where('tr_type', $values['billingTrType']);
    }

    /* ======================================================
     *                  ACCESSORS / SCOPES
     * ====================================================== */
    public function getTotalQtyAttribute()
    {
        return (int) $this->OrderDtl()->sum('qty');
    }

    public function getTotalAmtAttribute()
    {
        return (int) $this->OrderDtl()->sum('amt');
    }

    public function getMatlCodesAttribute()
    {
        $matlCodes = $this->OrderDtl()->pluck('matl_code')->toArray();
        return implode(', ', $matlCodes);
    }

    /* ======================================================
     *         HELPER / BUSINESS LOGIC CHECKS
     * ====================================================== */
    public function isOrderCompleted(): bool
    {
        return $this->status_code === Status::COMPLETED;
    }

    /* ======================================================
     *             PRIVATE HELPER METHODS
     * ====================================================== */
    /**
     * Tentukan nilai tr_type untuk Delivery, Billing, dan Payment.
     *
     * @param  string $trType
     * @return array Contoh: ['delivTrType' => 'PD', 'billingTrType' => 'APB', 'paymentTrType' => 'APP']
     */
    private function getTrTypeValues($trType)
    {
        if ($trType === 'PO') {
            return [
                'delivTrType'   => 'PD',
                'billingTrType' => 'APB',
                'paymentTrType' => 'APP',
            ];
        } else {
            // Untuk 'SO' atau tipe lainnya
            return [
                'delivTrType'   => 'SD',
                'billingTrType' => 'ARB',
                'paymentTrType' => 'ARP',
            ];
        }
    }

    /**
     * Generate tr_id jika record baru dan tr_id belum ada.
     *
     * @param string $trType
     */
    private function generateTransactionId($trType)
    {
        // Jika tr_id sudah ada (tidak null dan tidak kosong), tidak perlu generate lagi
        if (!empty($this->tr_id) && $this->tr_id !== null && $this->tr_id != 0) {
            return;
        }

        $configCode = $trType === 'PO' ? 'PURCHORDER_LASTID' : 'SALESORDER_LASTID';

        $configSnum = ConfigSnum::where('code', '=', $configCode)->first();
        if ($configSnum) {
            $stepCnt = $configSnum->step_cnt;
            $proposedTrId = $configSnum->last_cnt + $stepCnt;

            if ($proposedTrId > $configSnum->wrap_high) {
                $proposedTrId = $configSnum->wrap_low;
            }
            $proposedTrId = max($proposedTrId, $configSnum->wrap_low);

            $configSnum->last_cnt = $proposedTrId;
            $configSnum->save();

            $this->tr_id = $proposedTrId;
        }
    }

    public function isOrderEnableToDelete(): bool
    {
        if ($this->tr_type == 'PO') {
            foreach ($this->OrderDtl as $orderDtl) {
                $relatedOrderDtl = OrderDtl::where('matl_id', $orderDtl->matl_id)
                    ->where('tr_type', 'SO')
                    ->where('tr_id', '!=', $this->tr_id)
                    ->first();
                if ($relatedOrderDtl) {
                    return false;
                }
            }
        }

        if ($this->tr_type == 'SO') {
            foreach ($this->OrderDtl as $orderDtl) {
                $relatedOrderDtl = OrderDtl::where('matl_id', $orderDtl->matl_id)
                    ->where('tr_type', 'BB')
                    ->where('tr_id', '!=', $this->tr_id)
                    ->first();
                if ($relatedOrderDtl) {
                    return false;
                }
            }
        }
        return true;
    }

    /* ======================================================
     *      SAVE ORDER (HEADER & DETAIL BERSAMA)
     * ====================================================== */
    /**
     * Simpan atau update seluruh order (header dan detail) sekaligus.
     *
     * @param  string $trType
     * @param  array  $inputs           Data header (misalnya dari Livewire)
     * @param  array  $inputDetails     Data detail (array of detail)
     * @param  bool   $createBillingDelivery  Jika true, otomatis buat DelivDtl, BillingDtl & PaymentDtl.
     * @return $this
     * @throws \Exception
     */
    public function saveOrder(string $trType, array $inputs, array $inputDetails, bool $createBillingDelivery = false)
    {
        // --- Simpan/update HEADER ---
        $this->fill($inputs);
        $this->generateTransactionId($trType);

        if ($this->isNew()) {
            $this->status_code = Status::OPEN;
        }

        $this->save();

        // --- Optional: Buat Header Delivery, Billing, & Payment ---
        if ($createBillingDelivery) {
            $this->createDeliveryAndBillingHeader($trType, $inputs);
        }

        // --- Sinkronisasi & Simpan DETAIL ---
        $existingDetails = OrderDtl::where('trhdr_id', $this->id)
            ->where('tr_type', $trType)
            ->get()
            ->keyBy('tr_seq')
            ->toArray();

        $inputDetailsKeyed = collect($inputDetails)->keyBy('tr_seq')->toArray();
        $itemsToDelete = array_diff_key($existingDetails, $inputDetailsKeyed);

        foreach ($itemsToDelete as $tr_seq => $detail) {
            $orderDtl = OrderDtl::find($detail['id']);
            if ($orderDtl) {
                $orderDtl->forceDelete();
            }
        }

        $trTypeValues = $this->getTrTypeValues($trType);

        foreach ($inputDetails as $index => $detail) {
            $detail['tr_seq'] = $index + 1;
            $detail['tr_id'] = $this->tr_id ?? 0;
            $detail['trhdr_id'] = $this->id ?? 0;
            $detail['tr_type'] = $trType ?? '';

            if (isset($detail['qty'])) {
                $detail['qty_reff'] = $detail['qty'];
            }

            // Ensure all fields have default values
            $detail['matl_id'] = $detail['matl_id'] ?? 0;
            $detail['matl_code'] = $detail['matl_code'] ?? '';
            $detail['matl_uom'] = $detail['matl_uom'] ?? '';
            $detail['wh_id'] = $detail['wh_id'] ?? 0;
            $detail['wh_code'] = $detail['wh_code'] ?? '';
            $detail['qty'] = $detail['qty'] ?? 0;
            $detail['price'] = $detail['price'] ?? 0;
            $detail['amt'] = $detail['amt'] ?? 0;

            $orderDtl = OrderDtl::firstOrNew([
                'tr_id' => $detail['tr_id'],
                'tr_seq' => $detail['tr_seq'],
            ]);

            $orderDtl->fill($detail);

            if ($orderDtl->isNew()) {
                $orderDtl->status_code = Status::OPEN;
            }

            $orderDtl->save();

            if ($createBillingDelivery) {
                $this->createDeliveryAndBillingDetail(
                    $orderDtl,
                    $detail,
                    $trTypeValues['delivTrType'],
                    $trTypeValues['billingTrType'],
                    $trTypeValues['paymentTrType']
                );
            }
        }

        return $this;
    }

    /**
     * Create atau update header Delivery, Billing, dan Payment.
     *
     * @param  string $trType  e.g. 'PO', 'SO'
     * @param  array  $inputs  Data header dari form
     * @return void
     */
    private function createDeliveryAndBillingHeader($trType, array $inputs)
    {
        $values = $this->getTrTypeValues($trType);

        // --- DelivHdr ---
        $delivHdr = DelivHdr::firstOrNew([
            'tr_id'   => $this->tr_id,
            'tr_type' => $values['delivTrType'],
        ]);

        $delivHdr->fill([
            'tr_id'        => $this->tr_id ?? 0,
            'tr_type'      => $values['delivTrType'] ?? '',
            'tr_date'      => $this->tr_date ?? date('Y-m-d'),
            'partner_id'   => $this->partner_id ?? 0,
            'partner_code' => $this->partner_code ?? '',
            'deliv_by'     => $inputs['deliv_by'] ?? '',
        ]);

        if ($delivHdr->isNew()) {
            $delivHdr->status_code = Status::OPEN;
        }
        $delivHdr->save();

        // --- BillingHdr ---
        $billingHdr = BillingHdr::firstOrNew([
            'tr_id'   => $this->tr_id,
            'tr_type' => $values['billingTrType'],
        ]);

        $billingHdr->fill([
            'tr_id'        => $this->tr_id ?? 0,
            'tr_type'      => $values['billingTrType'] ?? '',
            'tr_date'      => $this->tr_date ?? date('Y-m-d'),
            'partner_id'   => $this->partner_id ?? 0,
            'partner_code' => $this->partner_code ?? '',
            'payment_term_id' => $this->payment_term_id ?? 0,
            'payment_term' => $this->payment_term ?? '',
            'payment_due_days' => 0,
        ]);

        if ($billingHdr->isNew()) {
            $billingHdr->status_code = Status::OPEN;
        }
        $billingHdr->save();

        // --- PaymentHdr ---
        $paymentHdr = PaymentHdr::firstOrNew([
            'tr_id'   => $this->tr_id,
            'tr_type' => $values['paymentTrType'],
        ]);

        $paymentHdr->fill([
            'tr_id'        => $this->tr_id ?? 0,
            'tr_type'      => $values['paymentTrType'] ?? '',
            'tr_date'      => $this->tr_date ?? date('Y-m-d'),
            'partner_id'   => $this->partner_id ?? 0,
            'partner_code' => $this->partner_code ?? '',
            'bank_id'      => $inputs['bank_id'] ?? 0,
            'bank_code'    => $inputs['bank_code'] ?? '',
            'bank_reff'    => $inputs['bank_reff'] ?? '',
            'bank_due'     => $inputs['bank_due'] ?? '1900-01-01',
            'bank_rcv'     => $inputs['bank_rcv'] ?? 0,
            'bank_rcv_base'=> $inputs['bank_rcv_base'] ?? 0,
            'bank_note'    => $inputs['bank_note'] ?? '',
            'curr_id'      => $this->curr_id ?? 0,
            'curr_rate'    => $this->curr_rate ?? 0,
        ]);

        if ($paymentHdr->isNew()) {
            $paymentHdr->status_code = Status::OPEN;
        }
        $paymentHdr->save();
    }

    /**
     * Create atau update detail Delivery, Billing, dan Payment untuk sebuah OrderDtl.
     *
     * Untuk delivery dan billing detail, trhdr_id diambil dari header yang bersangkutan,
     * begitu pula dengan payment detail.
     *
     * @param  OrderDtl $orderDtl       OrderDtl yang baru disimpan/diupdate.
     * @param  array    $detailData     Data tambahan (misalnya 'wh_id') dari form.
     * @param  string   $delivTrType    Tipe transaksi untuk Delivery (misalnya 'PD', 'SD').
     * @param  string   $billingTrType  Tipe transaksi untuk Billing (misalnya 'APB', 'ARB').
     * @param  string   $paymentTrType  Tipe transaksi untuk Payment.
     * @return void
     */
    private function createDeliveryAndBillingDetail($orderDtl, array $detailData, $delivTrType, $billingTrType, $paymentTrType)
    {
        // Ambil header terkait berdasarkan tr_id dan tr_type-nya
        $delivHdr = DelivHdr::where('tr_id', $this->tr_id)
            ->where('tr_type', $delivTrType)
            ->first();

        $billingHdr = BillingHdr::where('tr_id', $this->tr_id)
            ->where('tr_type', $billingTrType)
            ->first();

        $paymentHdr = PaymentHdr::where('tr_id', $this->tr_id)
            ->where('tr_type', $paymentTrType)
            ->first();

        // --- DelivDtl ---
        $delivDtl = DelivDtl::firstOrNew([
            'trhdr_id' => $delivHdr->id, // gunakan ID header Delivery
            'tr_seq'   => $orderDtl->tr_seq,
            'tr_type'  => $delivTrType,
        ]);

        $delivDtl->fill([
            'trhdr_id'      => $delivHdr->id,
            'tr_type'       => $delivTrType,
            'tr_id'         => $this->tr_id,
            'tr_seq'        => $orderDtl->tr_seq,
            'reffdtl_id'    => $orderDtl->id ?? 0,
            'reffhdrtr_type'=> $this->tr_type ?? '',
            'reffhdrtr_id'  => $this->tr_id ?? 0,
            'reffdtltr_seq' => $orderDtl->tr_seq ?? 0,
            'matl_id'       => $orderDtl->matl_id ?? 0,
            'matl_code'     => $orderDtl->matl_code ?? '',
            'matl_descr'    => $orderDtl->matl_descr ?? '',
            'matl_uom'      => $orderDtl->matl_uom ?? '',
            'wh_id'         => $detailData['wh_id'] ?? 0,
            'wh_code'       => $detailData['wh_code'] ?? '',
            'qty'           => $orderDtl->qty ?? 0,
            'qty_reff'      => $orderDtl->qty_reff ?? 0,
        ]);

        if ($delivDtl->isNew()) {
            $delivDtl->status_code = Status::OPEN;
        }
        $delivDtl->save();

        // --- BillingDtl ---
        $billingDtl = BillingDtl::firstOrNew([
            'trhdr_id' => $billingHdr->id, // gunakan ID header Billing
            'tr_seq'   => $orderDtl->tr_seq,
            'tr_type'  => $billingTrType,
        ]);

        $billingDtl->fill([
            'trhdr_id'      => $billingHdr->id,
            'tr_type'       => $billingTrType,
            'tr_id'         => $orderDtl->tr_id ?? 0,
            'tr_seq'        => $orderDtl->tr_seq ?? 0,
            'dlvdtl_id'     => $delivDtl->id ?? 0,
            'dlvhdrtr_type' => $delivDtl->tr_type ?? '',
            'dlvhdrtr_id'   => $delivDtl->tr_id ?? 0,
            'dlvdtltr_seq'  => $delivDtl->tr_seq ?? 0,
            'matl_id'       => $orderDtl->matl_id ?? 0,
            'matl_code'     => $orderDtl->matl_code ?? '',
            'matl_uom'      => $orderDtl->matl_uom ?? '',
            'descr'         => '',
            'qty'           => $orderDtl->qty ?? 0,
            'qty_base'      => $orderDtl->qty ?? 0,
            'price'         => $orderDtl->price ?? 0,
            'price_base'    => $orderDtl->price ?? 0,
            'amt'           => $orderDtl->amt ?? 0,
            'amt_reff'      => $orderDtl->amt ?? 0,
        ]);

        if ($billingDtl->isNew()) {
            $billingDtl->status_code = Status::OPEN;
        }
        $billingDtl->save();

        // --- PaymentDtl ---
        $paymentDtl = PaymentDtl::firstOrNew([
            'trhdr_id' => $paymentHdr->id, // gunakan ID header Payment
            'tr_seq'   => $orderDtl->tr_seq,
            'tr_type'  => $paymentTrType,
        ]);

        $paymentDtl->fill([
            'trhdr_id'      => $paymentHdr->id,
            'tr_type'       => $paymentTrType,
            'tr_id'         => $orderDtl->tr_id ?? 0,
            'tr_seq'        => $orderDtl->tr_seq ?? 0,
            'billdtl_id'    => $billingDtl->id ?? 0,
            'billhdrtr_type'=> $billingDtl->tr_type ?? '',
            'billhdrtr_id'  => $billingDtl->tr_id ?? 0,
            'billdtltr_seq' => $billingDtl->tr_seq ?? 0,
            'amt'           => $billingDtl->amt ?? 0,
            'amt_base'      => $billingDtl->amt ?? 0,
            'status_code'   => Status::OPEN,
        ]);
        $paymentDtl->save();
    }
}
