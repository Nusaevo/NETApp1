<?php

namespace App\Models\TrdJewel1\Transaction;

use App\Models\TrdJewel1\Master\Partner;
use App\Models\TrdJewel1\Base\TrdJewel1BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;
class DelivHdr extends TrdJewel1BaseModel
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
        return $this->hasMany(DelivDtl::class, 'trhdr_id', 'id')->where('tr_type', $this->tr_type)->orderBy('seq');
    }
    #endregion
}
