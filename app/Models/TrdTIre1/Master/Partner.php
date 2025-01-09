<?php

namespace App\Models\TrdTire1\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Helpers\SequenceUtility;
use App\Models\Base\BaseModel;
use App\Models\TrdTire1\Transaction\OrderHdr;
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

    public function PartnerDetail()
    {
        return $this->hasOne(PartnerDetail::class, 'partner_id');
    }

    public static function boot()
    {
        parent::boot();
        // static::creating(function ($model) {
        //     $maxId = SequenceUtility::getCurrentSequenceValue($model);
        //     $model->code = 'PARTNER' ."_". ($maxId + 1);
        // });
    }

    protected $fillable = [
        'country',
        'province',
        'city',
        'phone',
        'email',
        'nib',
        'address',
        'point_irc',
        'point_gt',
        'point_zn',
        'note',
        'grp',
        'code',
        'name',
        'name_prefix',
        'type_code',
        'postal_code',
        'contact_person',
        'collect_sched',
        'payment_term',
        'curr_id',
        'bank_acct',
        'tax_npwp',
        'tax_nppkp',
        'tax_address',
        'pic_grp',
        'pic_code',
        'info',
        'amt_limit',
        'partner_chars',
        'status_code',
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

    public static function generateNewCode($name, $category)
    {
        $nameInitial = strtoupper(substr($name, 0, 1));
        $categoryInitial = strtoupper(substr($category, 0, 1));
        $initialCode = $nameInitial . $categoryInitial;

        $latestCode = self::where('code', 'LIKE', $initialCode . '%')
                      ->orderByRaw("CAST(SUBSTRING(code, LENGTH(?) + 1) AS INTEGER) DESC", [$initialCode])
                      ->pluck('code')
                      ->first();
        if ($latestCode) {
            $numericPart = intval(substr($latestCode, 2)) + 1;
            return $initialCode . $numericPart;
        } else {
            return $initialCode . '1';
        }
    }

    // Fungsi untuk menghasilkan nama material, bisa dipanggil di dalam model ini
    protected function generateName($brand, $size, $pattern)
    {
        // Logika untuk menghasilkan nama berdasarkan nama, ukuran, dan pola
        return $brand . ' ' . $size . ' ' . $pattern;
    }
}
