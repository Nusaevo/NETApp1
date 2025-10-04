<?php

namespace App\Models\TrdTire2\Transaction;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;


class PartnertrHdr extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'tr_date',
        'tr_type',
        'tr_code',
        'reff_code',
        'curr_id',
        'curr_rate',
        'curr_code',
        'amt',
        'amt_base',
    ];

    protected $casts = [
        'curr_id' => 'integer',
        'curr_rate' => 'float',
        'amt' => 'float',
        'amt_base' => 'float',
    ];

    #region Relations
    public function PartnertrDtl()
    {
        return $this->hasMany(PartnertrDtl::class, 'trhdr_id', 'id');
    }
    #endregion
}
