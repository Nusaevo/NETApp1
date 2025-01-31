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
    const SUPPLIER = 'S';
    const SALESMAN = 'A';
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
        'country',
        'province',
        'city',
        'phone',
        'email',
        'nib',
        'address',
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
        'amt_limit',
    ];

    #region Relations

    public function PartnerDetail()
    {
        return $this->hasOne(PartnerDetail::class, 'partner_id');
    }


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
        $nameInitial = strtoupper(substr($name, 0, 1));
        $initialCode = $nameInitial;

        $latestCode = self::where('code', 'LIKE', $initialCode . '%')
                          ->orderByRaw("LENGTH(code) DESC")
                          ->pluck('code')
                          ->first();

        if ($latestCode) {
            // Ekstrak bagian numerik dari kode
            preg_match('/\d+$/', $latestCode, $matches);
            $numericPart = isset($matches[0]) ? intval($matches[0]) + 1 : 1;

            return $initialCode . str_pad($numericPart, 4, '0', STR_PAD_LEFT);
        } else {
            return $initialCode . '0001';
        }
    }


    // Fungsi untuk menghasilkan nama material, bisa dipanggil di dalam model ini
    protected function generateName($brand, $size, $pattern)
    {
        // Logika untuk menghasilkan nama berdasarkan nama, ukuran, dan pola
        return $brand . ' ' . $size . ' ' . $pattern;
    }
}
