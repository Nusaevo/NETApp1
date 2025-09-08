<?php

namespace App\Models\TrdTire1\Transaction;

use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Master\Partner;
use App\Models\TrdTire1\Transaction\ReturnDtl;
use App\Enums\Status;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use App\Enums\Constant;
class ReturnHdr extends BaseModel
{
    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($returnHdr) {
            foreach ($returnHdr->ReturnDtl as $returnDtl) {
                $returnDtl->delete();
            }
        });
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
        'payment_term_id',
        'payment_term',
        'curr_id',
        'curr_code',
        'curr_rate',
        'status_code'
    ];

    protected $casts = [
        'partner_id' => 'integer',
        'payment_term_id' => 'integer',
        'curr_id' => 'integer',
        'curr_rate' => 'float',
        'tr_date' => 'datetime',
    ];
    
    #region Relations

    public function Partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id', 'id');
    }

    public function ReturnDtl()
    {
        return $this->hasMany(ReturnDtl::class, 'trhdr_id', 'id');
    }

    #endregion

    #region Attributes

    public function getTotalQtyAttribute()
    {
        return (int) $this->ReturnDtl()->sum('qty');
    }

    public function getTotalAmtAttribute()
    {
        return (int) $this->ReturnDtl()->sum('amt');
    }

    public function getMatlCodesAttribute()
    {
        $matlCodes = $this->ReturnDtl()->pluck('matl_code')->toArray();
        return implode(', ', $matlCodes);
    }

    #endregion

    public function isOrderCompleted(): bool
    {
        if ($this->status_code == Status::COMPLETED) {
            return true;
        }
        return false;
    }
}
