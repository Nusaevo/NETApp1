<?php

namespace App\Models\SysConfig1;

// use App\Core\Traits\SpatieLogsActivity;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseTrait;
use Illuminate\Support\Facades\Schema;
class ConfigUser extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;
    // use SpatieLogsActivity;
    // use HasRoles;
    use SoftDeletes;
    use BaseTrait;


    public static function boot()
    {
        parent::boot();
        static::retrieved(function ($model) {
            $attributes = $model->getAllColumns();

            foreach ($attributes as $attribute) {
                $value = $model->getAllColumnValues($attribute);
                if (is_string($value) && preg_match('/^\$[\d,]+\.\d{2}$/', $value)) {
                    $value = (float) currencyToNumeric($value);
                }
                $model->{$attribute} = $value;
            }
        });
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

    public function fillAndSanitize(array $attributes)
    {
        $sanitizedAttributes = [];

        foreach ($attributes as $key => $value) {
            if (isDateAttribute($value)) {
                $sanitizedAttributes[$key] = sanitizeDate($value);
            } elseif (isFormattedNumeric($value) !== false) {
                $sanitizedAttributes[$key] = str_replace('.', '', $value);
                $sanitizedAttributes[$key] = str_replace(',', '.', $sanitizedAttributes[$key]);
            } else {
                $sanitizedAttributes[$key] = $value;
            }
        }
        $this->fill($sanitizedAttributes);
    }

    public function isDuplicateCode()
    {
        // Check if the model has a column 'code'
        if (Schema::connection($this->getConnectionName())->hasColumn($this->getTable(), 'code')) {
            // Convert code to uppercase for case-insensitive comparison
            $upperCode = strtoupper($this->code);

            // Perform a query to check for duplicates with case-insensitive comparison
            $query = $this->newQuery()
                ->whereRaw('UPPER(code) = ?', [$upperCode]);

            // Exclude the current model instance from the check if it is not new
            if (!$this->isNew()) {
                $query->where('id', '!=', $this->id);
            }

            return $query->exists();
        }

        return false; // Return false if 'code' column does not exist
    }

    public function isDuplicateName()
    {
        // Check if the model has a column 'name'
        if (Schema::connection($this->getConnectionName())->hasColumn($this->getTable(), 'name')) {
            // Convert name to uppercase for case-insensitive comparison
            $upperName = strtoupper($this->name);

            // Perform a query to check for duplicates with case-insensitive comparison
            $query = $this->newQuery()
                ->whereRaw('UPPER(name) = ?', [$upperName]);

            // Exclude the current model instance from the check if it is not new
            if (!$this->isNew()) {
                $query->where('id', '!=', $this->id);
            }

            return $query->exists();
        }

        return false; // Return false if 'name' column does not exist
    }
    #region Relations
    public function ConfigGroup()
    {
        return $this->belongsToMany(ConfigGroup::class, 'config_grpusers', 'user_id', 'group_id');
    }
    #endregion

    #region Attributes
    #endregion


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

    public function scopeGetActiveData()
    {
        return $this->orderBy('name', 'asc')->get();
    }
    public function isNew()
    {
        $isNew = empty($this->id);
        return  $isNew;
    }

    public function setStatus($value)
    {
        if (Schema::connection($this->getConnectionName())->hasColumn($this->getTable(), 'status_code')) {
            if (!isset($this->attributes['status_code'])) {
                $this->attributes['status_code'] = $value;
            }
        }
    }

}
