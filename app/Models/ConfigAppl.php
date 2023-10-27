<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
class ConfigAppl extends Model
{
    use HasFactory, SoftDeletes;
    use BaseTrait;
    protected $table = 'config_appls';

    public static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
    }

    protected $fillable = [
        'code',
        'name',
        'version',
        'descr',
        'status_code'
    ];

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    public function configMenus()
    {
        return $this->hasMany('App\Models\ConfigMenu', 'appl_id', 'id');
    }
}
