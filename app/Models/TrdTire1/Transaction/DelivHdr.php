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
        'tax',
        'payment_terms',
        'due_date',
        'note',
        'status',
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
    public function savePurchaseHeader($appCode, $trType, $inputs, $configCode)
    {
        $this->fillAndSanitize($inputs);
        $this->tr_type = $trType; // Ensure tr_type is set

        // Set default status
        if ($this->isNew()) {
            $this->status_code = Status::OPEN;
        }

        // Simpan header
        $this->save();
    }
    #endregion
}
