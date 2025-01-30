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
        'tr_id',
        'tr_type',
        'tr_date',
        'reff_code',
        'partner_id',
        'partner_code',
        'sales_id',
        'sales_code',
        'deliv_by',
        'payment_term_id',
        'payment_term',
        'curr_id',
        'curr_code',
        'curr_rate',
        'status_code',
        'print_settings',
        'print_remarks',
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

    #region Metode Utama
    public function saveOrderHeader($appCode, $trType, $inputs, $configCode)
    {
        $this->fill($inputs);

        // Generate Transaction ID jika belum ada
        $this->generateTransactionId($appCode, $configCode);

        // Set default status
        if ($this->isNew()) {
            $this->status_code = Status::OPEN;
        }

        // Simpan header
        $this->save();
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

    public function createOrUpdateDelivery()
    {
        $deliveryHdr = DelivHdr::firstOrNew([
            'tr_id' => $this->tr_id,
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

        $billingDtl->fill([
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

    private function generateTransactionId($appCode, $code)
    {
        if ($this->tr_id === null || $this->tr_id == 0) {
            // Dapatkan tahun dan bulan saat ini
            $year = date('y');
            $monthNumber = date('n');
            $monthLetter = chr(64 + $monthNumber); // Mengubah angka bulan ke huruf

            // Dapatkan nomor urut dari database atau session
            $sequenceNumber = $this->getSequenceNumber($code);

            // Tentukan format berdasarkan kode aplikasi atau jenis transaksi
            switch ($appCode) {
                case 'Lain-lain':
                    $this->tr_id = sprintf('%02d%02d%04d', $year, $monthNumber, $sequenceNumber);
                    break;
                case 'Motor':
                    $this->tr_id = sprintf('%s%02d8%04d', $monthLetter, $year, $sequenceNumber);
                    break;
                case 'Mobil':
                    $this->tr_id = sprintf('%s%02d8%04d', $monthLetter, $year, $sequenceNumber);
                    break;
                    // Tambahkan logika untuk jenis lainnya jika diperlukan
            }

            // Simpan nomor urut terbaru
            $this->saveSequenceNumber($code, $sequenceNumber);
        }
    }

    private function getSequenceNumber($code)
    {
        // Implementasikan logika untuk mendapatkan nomor urut
        // Misalnya, simpan di database dan reset setiap bulan
        // Contoh: ambil dari database atau session
        return 1; // Ganti dengan logika yang sesuai
    }

    private function saveSequenceNumber($code, $sequenceNumber)
    {
        // Implementasikan logika untuk menyimpan nomor urut terbaru
        // Misalnya, simpan di database atau session
    }


    private function deleteOrderDetails()
    {
        foreach ($this->OrderDtl as $detail) {
            $detail->delete();
        }
    }
    #endregion
}
