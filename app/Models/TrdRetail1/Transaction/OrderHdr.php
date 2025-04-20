<?php

namespace App\Models\TrdRetail1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdRetail1\Master\Partner;
use App\Models\TrdRetail1\Master\Material;
use App\Enums\Status;
use App\Models\SysConfig1\ConfigSnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderHdr extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'tr_id', 'tr_type', 'tr_date', 'reff_code', 'partner_id', 'partner_code',
        'sales_id', 'sales_code', 'deliv_by', 'payment_term_id', 'payment_term',
        'curr_id', 'curr_code', 'curr_rate', 'status_code', 'print_settings', 'print_remarks'
    ];

    /* ======================================================
     *                     BOOT METHOD
     * ====================================================== */
    protected static function boot()
    {
        parent::boot();

        // Cascade deletes untuk record terkait
        static::deleting(function ($orderHdr) {
            // Hapus DelivHdr dan detail-nya
            $delivHdr = $orderHdr->DelivHdr;
            if ($delivHdr) {
                foreach ($delivHdr->DelivDtl as $delivDtl) {
                    $delivDtl->delete();
                }
                $delivHdr->delete();
            }

            // Hapus BillingHdr dan detail-nya
            $billingHdr = $orderHdr->BillingHdr;
            if ($billingHdr) {
                foreach ($billingHdr->BillingDtl as $billingDtl) {
                    $billingDtl->delete();
                }
                $billingHdr->delete();
            }

            // Hapus OrderDtl
            foreach ($orderHdr->OrderDtl as $orderDtl) {
                $orderDtl->delete();
            }
        });

        // Contoh: format goldprice_curr saat retrieval
        static::retrieved(function ($model) {
            if (array_key_exists('goldprice_curr', $model->attributes)) {
                $model->goldprice_curr = numberFormat($model->attributes['goldprice_curr'], 2);
            }
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
     * Generate tr_id jika record baru.
     *
     * @param string $trType
     */
    private function generateTransactionId($trType)
    {
        $configCode = $trType === 'PO' ? 'PURCHORDER_LASTID' : 'SALESORDER_LASTID';

        if ($this->tr_id === null || $this->tr_id == 0) {
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
            $detail['tr_id'] = $this->tr_id;
            $detail['trhdr_id'] = $this->id;
            $detail['tr_type'] = $trType;

            if (isset($detail['qty'])) {
                $detail['qty_reff'] = $detail['qty'];
            }

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
            'tr_id'        => $this->tr_id,
            'tr_type'      => $values['delivTrType'],
            'tr_date'      => $this->tr_date,
            'partner_id'   => $this->partner_id,
            'partner_code' => $this->partner_code,
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
            'tr_id'        => $this->tr_id,
            'tr_type'      => $values['billingTrType'],
            'tr_date'      => $this->tr_date,
            'partner_id'   => $this->partner_id,
            'partner_code' => $this->partner_code,
            'payment_term_id' => $this->payment_term_id,
            'payment_term' => '',
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
            'tr_id'        => $this->tr_id,
            'tr_type'      => $values['paymentTrType'],
            'tr_date'      => $this->tr_date,
            'partner_id'   => $this->partner_id,
            'partner_code' => $this->partner_code,
            'bank_id'      => $inputs['bank_id'] ?? 0,
            'bank_code'    => $inputs['bank_code'] ?? '',
            'bank_reff'    => $inputs['bank_reff'] ?? '',
            'bank_due'     => $inputs['bank_due'] ?? '1900-01-01',
            'bank_rcv'     => $inputs['bank_rcv'] ?? 0,
            'bank_rcv_base'=> $inputs['bank_rcv_base'] ?? 0,
            'bank_note'    => $inputs['bank_note'] ?? '',
            'curr_id'      => $this->curr_id,
            'curr_rate'    => $this->curr_rate,
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
            'reffdtl_id'    => $orderDtl->id,
            'reffhdrtr_type'=> $this->tr_type,
            'reffhdrtr_id'  => $this->tr_id,
            'reffdtltr_seq' => $orderDtl->tr_seq,
            'matl_id'       => $orderDtl->matl_id,
            'matl_code'     => $orderDtl->matl_code,
            'matl_descr'    => $orderDtl->matl_descr,
            'matl_uom'      => $orderDtl->matl_uom,
            'wh_id'         => $detailData['wh_id'] ?? '',
            'wh_code'       => $detailData['wh_code'] ?? '',
            'qty'           => $orderDtl->qty,
            'qty_reff'      => $orderDtl->qty_reff,
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
            'tr_id'         => $orderDtl->tr_id,
            'tr_seq'        => $orderDtl->tr_seq,
            'dlvdtl_id'     => $delivDtl->id,
            'dlvhdrtr_type' => $delivDtl->tr_type,
            'dlvhdrtr_id'   => $delivDtl->tr_id,
            'dlvdtltr_seq'  => $delivDtl->tr_seq,
            'matl_id'       => $orderDtl->matl_id,
            'matl_code'     => $orderDtl->matl_code,
            'matl_uom'      => $orderDtl->matl_uom,
            'descr'         => '',
            'qty'           => $orderDtl->qty,
            'qty_base'      => $orderDtl->qty,
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
            'tr_id'         => $orderDtl->tr_id,
            'tr_seq'        => $orderDtl->tr_seq,
            'billdtl_id'    => $billingDtl->id,
            'billhdrtr_type'=> $billingDtl->tr_type,
            'billhdrtr_id'  => $billingDtl->tr_id,
            'billdtltr_seq' => $billingDtl->tr_seq,
            'amt'           => $billingDtl->amt,
            'amt_base'      => $billingDtl->amt,
            'status_code'   => Status::OPEN,
        ]);
        $paymentDtl->save();
    }
}
