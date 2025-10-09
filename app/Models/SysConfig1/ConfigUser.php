<?php

namespace App\Models\SysConfig1;

use Illuminate\Database\Eloquent\{Factories\HasFactory, SoftDeletes};
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use App\Traits\BaseTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Constant;

class ConfigUser extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;
    // use SpatieLogsActivity;
    // use HasRoles;
    use SoftDeletes;
    use BaseTrait;

     public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->connection = Constant::configConn();
    }

    protected static function boot()
    {
        parent::boot();
        self::bootBaseTrait();
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['code', 'password', 'name', 'dept', 'phone', 'email', 'status_code', 'otp_code', 'otp_expiry'];    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    public function getAllColumns()
    {
        return $this->fillable;
    }

    public function getAllColumnValues($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            return $this->attributes[$attribute];
        }
        return null;    }


    public function isDuplicateCode()
    {
        // Check if the model has a column 'code'
        if (Schema::connection($this->getConnectionName())->hasColumn($this->getTable(), 'code')) {
            // Convert code to uppercase for case-insensitive comparison
            $upperCode = strtoupper($this->code);

            // Perform a query to check for duplicates with case-insensitive comparison
            $query = $this->newQuery()->whereRaw('UPPER(code) = ?', [$upperCode]);

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
            $query = $this->newQuery()->whereRaw('UPPER(name) = ?', [$upperName]);

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
    protected $casts = [
        'otp_expiry' => 'datetime',
    ];

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
        return $isNew;
    }

    public function setStatus($value)
    {
        if (Schema::connection($this->getConnectionName())->hasColumn($this->getTable(), 'status_code')) {
            if (!isset($this->attributes['status_code'])) {
                $this->attributes['status_code'] = $value;
            }
        }
    }

    /**
     * Get list of group codes for the current session app code.
     *
     * @return array
     */
    public function getGroupCodesBySessionAppCode()
    {
        // Periksa apakah kode aplikasi ada di session
        $appCode = session('app_code');

        if (!$appCode) {
            return []; // Jika app_code tidak ada di session, kembalikan array kosong
        }

        // Ambil grup yang terkait dengan pengguna ini
        $groupCodes = $this->ConfigGroup()
            ->where('app_code', $appCode)
            ->pluck('code') // Ambil hanya kolom 'code'
            ->toArray();

        return $groupCodes;
    }

    /**
     * Generate OTP for this user
     */
    public function generateOtp()
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->update([
            'otp_code' => $otp,
            'otp_expiry' => now()->addMinutes(5) // OTP expires in 5 minutes
        ]);

        return $otp;
    }

    /**
     * Verify OTP
     */
    public function verifyOtp($inputOtp)
    {
        if (!$this->otp_code || !$this->otp_expiry) {
            return false;
        }

        if ($this->otp_expiry < now()) {
            // Clear expired OTP
            $this->update([
                'otp_code' => null,
                'otp_expiry' => null
            ]);
            return false;
        }

        // Try different comparison approaches
        $storedOtp = trim((string)$this->otp_code);
        $inputOtpTrimmed = trim((string)$inputOtp);

        if ($storedOtp === $inputOtpTrimmed) {
            // Clear used OTP
            $this->update([
                'otp_code' => null,
                'otp_expiry' => null
            ]);
            return true;
        }

        return false;
    }

    /**
     * Check if user has OTP access to app
     */
    public function hasOtpAccessToApp($appCode)
    {
        // Get user's groups for the app
        $groups = $this->ConfigGroup()
            ->where('app_code', $appCode)
            ->get();

        // Get excluded OTP groups from TrdTire1 ConfigConst
        $excludedGroups = $this->getExcludedOtpGroups();

        // Convert excluded groups to uppercase for comparison
        $excludedGroupsUpper = array_map('strtoupper', $excludedGroups);

        foreach ($groups as $group) {
            // Check if group is in excluded list (bypass OTP) - case insensitive
            if (in_array(strtoupper($group->code), $excludedGroupsUpper)) {
                return 'bypass'; // Special return value for excluded groups
            }
        }

        $hasAccess = $groups->isNotEmpty();

        return $hasAccess; // Return true if user has any group access
    }

    /**
     * Get excluded OTP groups from TrdTire1 ConfigConst
     */
    private function getExcludedOtpGroups()
    {
        // Look for excluded groups configuration in ConfigConst using TrdTire1 connection
        $excludedConfig = \DB::connection('TrdTire1')
            ->table('config_consts')
            ->select('note1')
            ->where('const_group', 'EXCLUDED_OTP_GROUPS')
            ->first();

        $excludedGroups = [];
        if ($excludedConfig && $excludedConfig->note1) {
            // Assume group codes are comma-separated in note1
            $groupCodes = explode(',', $excludedConfig->note1);
            $excludedGroups = array_map('trim', $groupCodes);
        } else {
            // Fallback groups if no configuration found
            $excludedGroups = ['netDevelopers'];
        }

        // Remove duplicates and filter non-empty values, then normalize to uppercase
        $excludedGroups = array_unique(array_filter($excludedGroups));

        return $excludedGroups;
    }
}
