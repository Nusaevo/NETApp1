<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Master\Partner;
use App\Models\TrdTire1\Master\Material;
use App\Enums\Status;
use App\Models\SysConfig1\ConfigSnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderHdr extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'tr_id',
        'tr_type',
        'tax',
        'partner_id',
        'payment_terms',
        'tr_date',
        'due_date',
        'cust_reff',
        'tax_payer',
        'type',
        'note',
        'send_to',
        'vehicle_type',
        'tax_invoice',
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
        return $this->hasMany(OrderDtl::class, 'tr_id', 'tr_id')->where('tr_type', $this->tr_type)->orderBy('tr_seq');
    }

    public function DelivHdr()
    {
        return $this->hasOne(DelivHdr::class, 'tr_id', 'tr_id')->where('tr_type', $this->getDeliveryTrType());
    }

    public function BillingHdr()
    {
        return $this->hasOne(BillingHdr::class, 'tr_id', 'tr_id')->where('tr_type', $this->getBillingTrType());
    }
    #endregion
    public function saveOrderHeader($appCode, $trType, $inputs, $configCode)
    #region Metode Utama public function saveOrderHeader($appCode, $trType, $inputs, $configCode)
    {
        $this->fillAndSanitize($inputs);
        $this->tr_type = $trType; // Ensure tr_type is set

        // Tentukan vehicle_type berdasarkan trType
        //$vehicleType = $this->vehicle_type;

        // Tentukan vehicle_type berdasarkan trType
        //$vehicleType = $this->vehicle_type;

        // Generate Transaction ID jika belum ada
        // if (empty($this->tr_Id)) {
        //     $this->tr_Id = $this->generateTransactionId($vehicleType);
        // }

        // Set default status
        if ($this->isNew()) {
            $this->status_code = Status::OPEN;
        }

        // Simpan header
        $this->save();
    }


    public static function generateTransactionId($vehicle_type, $tax_invoice = false)
    {
        $year = date('y'); // Two-digit year
        $monthNumber = date('n'); // Month in number
        $monthLetter = chr(64 + $monthNumber); // Month in letter (A, B, C, etc.)
        $sequenceNumber = self::getSequenceNumber($vehicle_type); // Get sequence number

        // Determine format based on vehicle_type and tax invoice
        if ($tax_invoice) {
            switch ($vehicle_type) {
                case 0: // MOTOR with tax invoice
                    return sprintf('%s%s%05d', $monthLetter, $year, $sequenceNumber); // Example: A25XXXXx
                case 1: // MOBIL with tax invoice
                    return sprintf('%s%s%s%05d', $monthLetter, $monthLetter, $year, $sequenceNumber); // Example: AA25XXXxx
                default:
                    throw new \InvalidArgumentException('Invalid vehicle type');
            }
        } else {
            switch ($vehicle_type) {
                case 0: // MOTOR without tax invoice
                    return sprintf('%s%02d8%04d', $monthLetter, $year, $sequenceNumber); // Example: A258XXXx
                case 1: // MOBIL without tax invoice
                    return sprintf('%s%s%02d8%04d', $monthLetter, $monthLetter, $year, $sequenceNumber); // Example: AA258XXXx
                default:
                    throw new \InvalidArgumentException('Invalid vehicle type');
            }
        }
    }


    private static function getSequenceNumber($vehicle_type)
    {
        // Mendapatkan bulan dan tahun saat ini
        $currentYear = date('y'); // Dua digit terakhir tahun
        $currentMonth = date('n'); // Bulan dalam angka

        // Ambil entri terakhir dari tabel orderhdr dengan tr_type = 'SO' dan vehicle_type
        $lastOrder = OrderHdr::where('tr_type', 'SO')
            ->where('vehicle_type', $vehicle_type) // Filter berdasarkan vehicle_type
            ->orderBy('id', 'desc')
            ->first();

        // Jika ada entri sebelumnya, periksa bulan dan tahun
        if ($lastOrder) {
            // Ambil bulan dan tahun dari tr_id
            preg_match('/([A-Z])(\d{2})/', $lastOrder->tr_id, $matches); // Ambil bulan dan tahun
            if (isset($matches[1]) && isset($matches[2])) {
                $lastMonthLetter = $matches[1];
                $lastYear = (int)$matches[2];

                // Cek apakah bulan dan tahun sama dengan yang sekarang
                if ($lastYear == $currentYear && $lastMonthLetter == chr(64 + $currentMonth)) {
                    // Ambil nomor urut dari tr_id
                    preg_match('/\d{3,4}$/', $lastOrder->tr_id, $matches); // Ambil 3-4 digit terakhir
                    $lastSequenceNumber = isset($matches[0]) ? (int)$matches[0] : 0;
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
            'tr_id' => $this->tr_id,
            'tr_type' => $this->getBillingTrType(),
        ]);

        $billingHdr->fillAndSanitize([
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
            'tr_id' => $this->tr_id,
            'tr_type' => $this->getDeliveryTrType(),
        ]);

        $deliveryHdr->fillAndSanitize([
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

        $deliveryDtl->fillAndSanitize([
            'trhdr_id' => $orderDtl->trhdr_id,
            'tr_type' => $this->getDeliveryTrType(),
            'tr_id' => $this->tr_id,
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

        $billingDtl->fillAndSanitize([
            'trhdr_id' => $orderDtl->trhdr_id,
            'tr_type' => $this->getBillingTrType(),
            'tr_id' => $this->tr_id,
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
}
