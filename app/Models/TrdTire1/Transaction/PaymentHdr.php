<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Master\Partner;
use App\Models\TrdTire1\Transaction\PaymentDtl;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
use App\Enums\TrdTire1\Status;
use App\Traits\BaseTrait;

class PaymentHdr extends BaseModel
{
    use SoftDeletes;

    protected $table = 'payment_hdrs';

    protected static function boot()
    {
        parent::boot();
    }
    protected $fillable = [
        'tr_type',
        'tr_code',
        'tr_date',
        'reff_code',
        'partner_id',
        'partner_code',
        'bank_id',
        'bank_code',
        'bank_reff',
        'bank_due',
        'bank_rcv',
        'bank_rcv_base',
        'bank_note',
        'curr_id',
        'curr_rate',
        'status_code',
        'version_number',
        'updated_at'
    ];

    #region Relations
    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function PaymentDtl()
    {
        return $this->hasMany(PaymentDtl::class, 'trhdr_id', 'id');
    }
    public function paymentSrc()
    {
        return $this->hasMany(PaymentSrc::class, 'trhdr_id', 'id');
    }

    // Define the details relationship
    // public function details()
    // {
    //     return $this->hasMany(PaymentSrc::class, 'trhdr_id', 'id');
    // }
    // public function details2()
    // {
    //     return $this->hasMany(PaymentDtl::class, 'trhdr_id', 'id');
    // }

    public static function getByCreatedByAndTrType($createdBy, $trType)
    {
        return self::where('created_by', $createdBy)->where('tr_type', $trType)->get();
    }
    public function saveOrderHeader($appCode, $trType, $inputs, $lastIdKey)
    {
        // Implement the logic to save the order header
        // Example:
        $this->fill($inputs);

        // Generate tr_id with incremented value only if it's a new record
        if (!$this->exists) {
            $lastRecord = self::where('tr_type', $trType)->orderBy('tr_code', 'desc')->first();
            $lastId = $lastRecord ? intval($lastRecord->tr_code) : 0; // Ensure tr_code is treated as an integer
            $this->tr_code = str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);
        }
        // Set default status jika baru
        if ($this->isNew()) {
            $this->status_code = Status::OPEN;
        }

        // $this->tr_type = $inputs['tr_type'];
        $this->save();
    }

    public function scopeGetByOrderHdr($query, $id, $trType)
    {
        return $query->where('id', $id) // Changed 'tr_id' to 'id'
            ->where('tr_type', $trType);
    }
    #endregion

    /**
     * Check if the order is completed.
     * You can adjust the logic as needed.
     */
    public function isOrderCompleted()
    {
        return $this->status_code == Status::COMPLETED;
    }

    public static function generateTransactionId($tr_type, $tax_doc_flag)
    {
        // Cari transaksi terakhir berdasarkan tr_type dan tr_code
        $lastTransaction = self::where('tr_type', $tr_type)
            ->whereRaw('tr_code ~ \'^[0-9]+$\'') // Pastikan tr_code hanya berisi angka
            ->orderByRaw('CAST(tr_code AS INTEGER) DESC')
            ->first();

        // Jika tidak ada transaksi sebelumnya, mulai dari 0001
        if (!$lastTransaction) {
            return "0001";
        }

        // Ambil sequence number dari kode terakhir
        $lastSequence = (int) $lastTransaction->tr_code;

        // Increment sequence
        $newSequence = $lastSequence + 1;

        // Format sequence dengan leading zeros
        $formattedSequence = str_pad($newSequence, 4, '0', STR_PAD_LEFT);

        // Verifikasi bahwa sequence baru belum digunakan
        while (self::where('tr_type', $tr_type)
               ->where('tr_code', $formattedSequence)
               ->exists()) {
            $newSequence++;
            $formattedSequence = str_pad($newSequence, 4, '0', STR_PAD_LEFT);
        }

        return $formattedSequence;
    }
}
