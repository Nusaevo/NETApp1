<?php

namespace App\Models\Settings;

use App\Core\Traits\SpatieLogsActivity;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Spatie\Permission\Traits\HasRoles;
use App\Traits\BaseTrait;
use Illuminate\Support\Facades\Auth;
class ConfigUser extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;
    use SpatieLogsActivity;
    // use HasRoles;
    use BaseTrait;
    use SoftDeletes;
    protected $connection = 'config';

    public static function boot()
    {
        parent::boot();
        self::bootUpdatesCreatedByAndUpdatedAt();
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'password',
        'name',
        'dept',
        'phone',
        'email',
        'status_code'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function configGroups()
    {
        return $this->belongsTo('App\Models\Settings\ConfigGroup', 'group_code', 'code');
    }

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

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    // protected $casts = [
    //     'email_verified_at' => 'datetime',
    // ];

    public function getRememberToken()
    {
        return $this->remember_token;
    }

    public function setRememberToken($value)
    {
        $this->remember_token = $value;
    }
    /**
     * Prepare proper error handling for url attribute
     *
     * @return string
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->info) {
            return asset($this->info->avatar_url);
        }

        return asset(theme()->getMediaUrlPath().'avatars/blank.png');
    }

    public function scopeGetActiveData()
    {
        return $this->orderBy('name', 'asc')->get();
    }
    /**
     * User relation to info model
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    // public function info()
    // {
    //     return $this->hasOne(UserInfo::class);
    // }
}
