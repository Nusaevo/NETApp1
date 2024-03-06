<?php

namespace App\Models\Masters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use App\Helpers\SequenceUtility;
use App\Models\BaseModel;

class Partner extends BaseModel
{
    protected $table = 'partners';

    public static function boot()
    {
        parent::boot();
        // static::creating(function ($model) {
        //     $maxId = SequenceUtility::getCurrentSequenceValue($model);
        //     $model->code = 'PARTNER' ."_". ($maxId + 1);
        // });
    }

    protected $fillable = [
        'grp',
        'code',
        'name',
        'name_prefix',
        'type_code',
        'address',
        'city',
        'country',
        'postal_code',
        'contact_person',
        'collect_sched',
        'payment_term',
        'curr_id',
        'bank_acct',
        'tax_npwp',
        'tax_nppkp',
        'tax_address',
        'pic_id',
        'pic_grp',
        'pic_code',
        'info',
        'amt_limit',
        'amt_bal',
        'status_code'
    ];

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    public function scopeGetByGrp($query, $grp)
    {
        return $query->where('grp', $grp)->get();
    }

}
