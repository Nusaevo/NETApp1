<?php

namespace App\Models\TrdRetail1\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\SequenceUtility;
use App\Models\Base\BaseModel;
use App\Models\TrdRetail1\Transaction\OrderHdr;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Enums\Constant;

class Partner extends BaseModel
{
    protected $table = 'partners';
    const CUSTOMER = 'C';
    const SUPPLIER = 'V';
    const SALESMAN = 'S';
    const BANK = 'B';
    use SoftDeletes;



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
        'phone',
        'email',
        'postal_code',
        'contact_person',
        'collect_sched',
        'payment_term',
        'curr_id',
        'pic_id',
        'pic_grp',
        'pic_code',
        'info',
        'amt_limit',
        'amt_bal',
        'partner_chars',
        'status_code'
    ];

    #region Relations
    public function OrderHdr()
    {
        return $this->belongsTo(OrderHdr::class, 'partner_id', 'id');
    }
    #endregion

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    public function scopeGetByGrp($query, $grp)
    {
        return $query->where('grp', $grp)->get();
    }

    public static function generateNewCode($name)
    {
        $initialCode = strtoupper(substr($name, 0, 1));
        $latestCode = self::withTrashed()->where('code', 'LIKE', $initialCode . '%')
                      ->orderByRaw("CAST(SUBSTRING(code, LENGTH(?) + 1) AS INTEGER) DESC", [$initialCode])
                      ->pluck('code')
                      ->first();

        if ($latestCode) {
            $numericPart = intval(substr($latestCode, 1)) + 1;
            return $initialCode . $numericPart;
        } else {
            return $initialCode . '1';
        }
    }
}
