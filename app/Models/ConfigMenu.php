<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
class ConfigMenu extends Model
{
    use HasFactory, SoftDeletes;
    use BaseTrait;

    protected $table = 'config_menus';

    public static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
    }

    protected $fillable = [
        'code',
        'appl_id',
        'appl_code',
        'menu_header',
        'sub_menu',
        'menu_caption',
        'link',
        'status_code'
    ];

    public function scopeGetActiveData()
    {
        return $this->orderBy('code', 'asc')->get();
    }

    public function configAppls()
    {
        return $this->belongsTo('App\Models\ConfigAppl', 'appl_id', 'id');
    }
}
