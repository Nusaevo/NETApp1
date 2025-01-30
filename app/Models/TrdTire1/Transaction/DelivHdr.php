<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\TrdTire1\Master\Partner;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
use App\Enums\Status;

class DelivHdr extends BaseModel
{
    use SoftDeletes;

    protected $table = 'deliv_hdrs'; // Update the table name

    protected $primaryKey = 'id';
    public $timestamps = true;
    protected $fillable = [
        'tr_id',
        'tr_date',
        'partner_id',
    ];

    #region Relations
    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function DelivDtl()
    {
        return $this->hasMany(DelivDtl::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type)->orderBy('tr_seq');
    }
    #endregion

    #region Metode Utama
    protected static function boot()
    {
        parent::boot();

        // Hook untuk menghapus relasi saat header dihapus
        static::deleting(function ($orderHdr) {
            $orderHdr->deleteDeliveryAndBilling();
            $orderHdr->deleteOrderDetails();
        });
    }
    public function savePurchaseHeader($appCode, $trType, $inputs, $configCode)
    {
        $this->fill($inputs);
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
    #endregion
}
