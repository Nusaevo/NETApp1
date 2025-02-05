<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Master\Partner;
use App\Models\TrdTire1\Master\Material;
use App\Enums\Status;
use App\Models\SysConfig1\ConfigConst;
use App\Models\SysConfig1\ConfigSnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderHdr extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'tr_code',
        'tr_type',
        'tax_flag',
        'tax_pct',
        'partner_id',
        'partner_code',
        'payment_term_id',
        'tr_date',
        'due_date',
        'cust_reff',
        'curr_rate',
        'tax_payer',
        'type',
        'note',
        'send_to_name',
        'sales_type',
        'tax_invoice',
        'payment_term',
    ];

    protected $casts = [
        'tax_invoice' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Hook untuk menghapus relasi saat header dihapus
        static::deleting(function ($orderHdr) {
            $orderHdr->deleteDeliveryAndBilling();
            $orderHdr->deleteOrderDetails();
        });
    }

    #region Relasi
    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }
    // public function Material()
    // {
    //     return $this->belongsTo(Material::class, 'material_id', 'id');
    // }

    public function OrderDtl()
    {
        return $this->hasMany(OrderDtl::class, 'tr_code', 'tr_code')->where('tr_type', $this->tr_type)->orderBy('tr_seq');
    }

    public function DelivHdr()
    {
        return $this->hasOne(DelivHdr::class, 'tr_code', 'tr_code')->where('tr_type', $this->getDeliveryTrType());
    }

    public function BillingHdr()
    {
        return $this->hasOne(BillingHdr::class, 'tr_code', 'tr_code')->where('tr_type', $this->getBillingTrType());
    }
    #endregion
    public function saveOrderHeader($appCode, $trType, $inputs, $configCode)
    #region Metode Utama public function saveOrderHeader($appCode, $trType, $inputs, $configCode)
    {
        $this->fill($inputs);
        $this->tr_type = $trType; // Ensure tr_type is set

        // Ensure partner_code is set
        if (!empty($inputs['partner_id'])) {
            $partner = Partner::find($inputs['partner_id']);
            $this->partner_code = $partner->code;
        }

        // Set tax_pct based on tax_flag
        if (!empty($inputs['tax_flag'])) {
            $configData = ConfigConst::select('num1')
                ->where('const_group', 'TRX_SO_TAX')
                ->where('str1', $inputs['tax_flag'])
                ->first();
            $this->tax_pct = $configData->num1 ?? 0;
        }

        // Set default status
        if ($this->isNew()) {
            $this->status_code = Status::OPEN;
        }

        // Simpan header
        $this->save();
    }


    public static function generateTransactionId($sales_type, $tr_type, $tax_invoice = false)
    {
        if ($tr_type == 'PO') {
            return self::generatePurchaseOrderId();
        }

        $year = date('y'); // Two-digit year
        $monthNumber = date('n'); // Month in number
        $monthLetter = chr(64 + $monthNumber); // Month in letter (A, B, C, etc.)
        $sequenceNumber = self::getSequenceNumber($sales_type, $tax_invoice); // Get sequence number

        // Determine format based on sales_type and tax invoice
        if ($tax_invoice) {
            switch ($sales_type) {
                case 0: // MOTOR with tax invoice
                    return sprintf('%s%s%05d', $monthLetter, $year, $sequenceNumber); // Example: A25XXXXx
                case 1: // MOBIL with tax invoice
                    return sprintf('%s%s%s%05d', $monthLetter, $monthLetter, $year, $sequenceNumber); // Example: AA25XXXxx
                default:
                    throw new \InvalidArgumentException('Invalid vehicle type');
            }
        } else {
            switch ($sales_type) {
                case 0: // MOTOR without tax invoice
                    return sprintf('%s%s%05d', $monthLetter, $year, $sequenceNumber); // Example: A258XXXx
                case 1: // MOBIL without tax invoice
                    return sprintf('%s%s%s%05d', $monthLetter, $monthLetter, $year, $sequenceNumber); // Example: AA258XXXx
                default:
                    throw new \InvalidArgumentException('Invalid vehicle type');
            }
        }
    }

    private static function generatePurchaseOrderId()
    {
        $lastId = ConfigSnum::where('code', 'PURCHORDER_LASTID')->first();
        $newId = (int)$lastId->last_cnt + 1;
        $lastId->last_cnt = $newId;
        $lastId->save();

        return sprintf('PO%04d', $newId); // Example: PO0001
    }

    private static function getSequenceNumber($sales_type, $tax_invoice)
    {
        // Mendapatkan bulan dan tahun saat ini
        $currentYear = date('y'); // Dua digit terakhir tahun
        $currentMonth = date('n'); // Bulan dalam angka
        $currentMonthLetter = chr(64 + $currentMonth); // Bulan dalam huruf

        // Filter tambahan untuk tax_invoice
        $taxInvoiceFlag = $tax_invoice ? 1 : 0;

        // Ambil entri terakhir dari tabel orderhdr dengan tr_type = 'SO', sales_type, dan tax_invoice
        $lastOrder = OrderHdr::where('tr_type', 'SO')
            ->where('sales_type', $sales_type) // Filter berdasarkan sales_type
            ->where('tax_invoice', $taxInvoiceFlag) // Filter berdasarkan tax_invoice
            ->orderBy('id', 'desc')
            ->first();

        // Jika ada entri sebelumnya, periksa bulan dan tahun
        if ($lastOrder) {
            // Ambil bulan dan tahun dari tr_code
            preg_match('/([A-Z])(\d{2})(\d{4,5})$/', $lastOrder->tr_code, $matches); // Ambil bulan, tahun, dan nomor urut
            if (isset($matches[1]) && isset($matches[2]) && isset($matches[3])) {
                $lastMonthLetter = $matches[1];
                $lastYear = (int)$matches[2];
                $lastSequenceNumber = (int)$matches[3];

                // Cek apakah bulan dan tahun sama dengan yang sekarang
                if ($lastYear == $currentYear && $lastMonthLetter == $currentMonthLetter) {
                    return $lastSequenceNumber + 1; // Tambahkan 1 ke nomor urut
                }
            }
        }

        // Jika tidak ada entri sebelumnya atau bulan/tahun berbeda, mulai dari 1
        return 1;
    }




    public function saveOrderDetails($inputDetails, $trType, $inputs, $createBillingDelivery = false)
    {
        foreach ($inputDetails as $detail) {
            $orderDtl = $this->createOrUpdateOrderDetail($detail, $trType);

            if ($createBillingDelivery) {
                $this->createDeliveryDetail($orderDtl, $inputs);
                $this->createBillingDetail($orderDtl);
            }
        }
    }
    #endregion

    #region Logika Billing dan Delivery
    public function createOrUpdateBilling()
    {
        $billingHdr = BillingHdr::firstOrNew([
            'tr_code' => $this->tr_code,
            'tr_type' => $this->getBillingTrType(),
        ]);

        $billingHdr->fill([
            'tr_date' => $this->tr_date,
            'partner_id' => $this->partner_id,
            'partner_code' => $this->partner_code,
            'payment_term_id' => $this->payment_term_id,
        ]);

        if ($billingHdr->isNew()) {
            $billingHdr->status_code = Status::OPEN;
        }

        $billingHdr->save();
    }
    // Di dalam model OrderHdr
    public function isOrderCompleted()
    {
        // Logika untuk mengecek apakah order selesai
        return $this->status == 'completed'; // Misalnya, status 'completed' menandakan order selesai
    }


    public function createOrUpdateDelivery()
    {
        $deliveryHdr = DelivHdr::firstOrNew([
            'tr_code' => $this->tr_code,
            'tr_type' => $this->getDeliveryTrType(),
        ]);

        $deliveryHdr->fill([
            'tr_date' => $this->tr_date,
            'partner_id' => $this->partner_id,
            'partner_code' => $this->partner_code,
        ]);

        if ($deliveryHdr->isNew()) {
            $deliveryHdr->status_code = Status::OPEN;
        }

        $deliveryHdr->save();
    }

    public function createDeliveryDetail($orderDtl, $inputs)
    {
        $deliveryDtl = DelivDtl::firstOrNew([
            'trhdr_id' => $orderDtl->trhdr_id,
            'tr_seq' => $orderDtl->tr_seq,
            'tr_type' => $this->getDeliveryTrType(),
        ]);

        $deliveryDtl->fill([
            'trhdr_id' => $orderDtl->trhdr_id,
            'tr_type' => $this->getDeliveryTrType(),
            'tr_code' => $this->tr_code,
            'tr_seq' => $orderDtl->tr_seq,
            'matl_id' => $orderDtl->matl_id,
            'qty' => $orderDtl->qty,
        ]);

        $deliveryDtl->save();
    }

    public function createBillingDetail($orderDtl)
    {
        $billingDtl = BillingDtl::firstOrNew([
            'trhdr_id' => $orderDtl->trhdr_id,
            'tr_seq' => $orderDtl->tr_seq,
            'tr_type' => $this->getBillingTrType(),
        ]);

        $billingDtl->fill([
            'trhdr_id' => $orderDtl->trhdr_id,
            'tr_type' => $this->getBillingTrType(),
            'tr_code' => $this->tr_code,
            'tr_seq' => $orderDtl->tr_seq,
            'matl_id' => $orderDtl->matl_id,
            'qty' => $orderDtl->qty,
        ]);

        $billingDtl->save();
    }

    private function deleteDeliveryAndBilling()
    {
        if ($this->DelivHdr) {
            foreach ($this->DelivHdr->DelivDtl as $detail) {
                $detail->delete();
            }
            $this->DelivHdr->delete();
        }

        if ($this->BillingHdr) {
            foreach ($this->BillingHdr->BillingDtl as $detail) {
                $detail->delete();
            }
            $this->BillingHdr->delete();
        }
    }
    #endregion

    #region Utility
    private function getDeliveryTrType()
    {
        return $this->tr_type == "PO" ? "PD" : "SD";
    }

    private function getBillingTrType()
    {
        return $this->tr_type == "PO" ? "APB" : "ARB";
    }


    private function deleteOrderDetails()
    {
        foreach ($this->OrderDtl as $detail) {
            $detail->delete();
        }
    }
    #endregion

    #region Attributes
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
    #endregion
}
