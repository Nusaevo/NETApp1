<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use Illuminate\Support\Str;

class Partner extends Model
{
    use HasFactory, SoftDeletes;
    use BaseTrait;

    protected $table = 'partners';

    public static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
        static::creating(function ($model) {
            $maxId = static::max('id') ?? 0;
            $model->code = 'PARTNER' . ($maxId + 1);
        });
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
        'status_code',
        'price_category_id'
    ];

    public function getAllColumns()
    {
        return $this->fillable;
    }

    public function getAllColumnValues($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            return $this->attributes[$attribute];
        }
        return null;
    }

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    public function scopeGetByGrp($query, $grp)
    {
        return $query->where('grp', $grp)->get();
    }

    public function configUsers()
    {
        return $this->belongsTo('App\Models\PriceCategory', 'user_id', 'id');
    }

    public function priceCategories()
    {
        return $this->belongsTo('App\Models\PriceCategory', 'price_category_id', 'id');
    }

}
