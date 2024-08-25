<?php

namespace App\Models\TrdJewel1\Transaction;

use App\Models\TrdJewel1\Base\TrdJewel1BaseModel;
use App\Models\TrdJewel1\Master\Partner;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
class BillingHdr extends TrdJewel1BaseModel
{
    use SoftDeletes;
    protected $connection;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = Constant::Trdjewel1_ConnectionString();
    }

    protected static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'tr_id',
        'tr_type',
        'tr_date',
        'reff_code',
        'partner_id',
        'partner_code',
        'payment_term_id',
        'payment_term',
        'payment_due_days',
        'curr_id',
        'curr_code',
        'curr_rate',
        'status_code'
    ];

    #region Relations
    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function BillingDtl()
    {
        return $this->hasMany(BillingDtl::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type);
    }
    #endregion
}
