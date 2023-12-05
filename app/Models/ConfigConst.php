<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
class ConfigConst extends Model
{
    use HasFactory, SoftDeletes;
    use BaseTrait;

    protected $table = 'config_consts';
    protected $connection = 'config';

    public static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
    }

    protected $fillable = [
        'const_group',
        'app_id',
        'app_code',
        'group_id',
        'group_code',
        'user_id',
        'user_code',
        'seq',
        'str1',
        'str2',
        'num1',
        'num2',
        'note1',
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
        return $this->orderBy('str1', 'asc')->get();
    }

    public function configAppls()
    {
        return $this->belongsTo('App\Models\ConfigAppl', 'app_id', 'id');
    }

    public function scopeGetWarehouse()
    {
        return $this->where('const_group', 'WAREHOUSE_LOC')
                    ->orderBy('seq', 'asc')
                    ->get();
    }
}
