<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\TrdTire1\Master\Partner;
use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
class DelivHdr extends BaseModel
{
    use SoftDeletes;

    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'tr_id',
        'tr_type',
        'tr_id',
        'tr_date',
        'reff_code',
        'partner_id',
        'partner_code',
        'deliv_by',
        'status_code'
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
}
