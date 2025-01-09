<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Base\Attachment;
use App\Traits\BaseTrait;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use App\Enums\Status;
use App\Models\SysConfig1\ConfigSnum;


class BaseModel extends Model
{
    use HasFactory;
    use BaseTrait;

    protected $fillable = [];


    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $sessionAppCode = Session::get('app_code');
        $this->connection = $sessionAppCode;
    }

    protected static function boot()
    {
        parent::boot();

        static::retrieved(function ($model) {
            $attributes = $model->getAllColumns();

            foreach ($attributes as $attribute) {
                $value = $model->getAllColumnValues($attribute);
                if (is_numeric($value) && strpos($value, '.') !== false) {
                    $decimalPart = explode('.', $value)[1];
                    if ((int)$decimalPart === 0) {
                        $value = (int)$value;
                    }
                }

                if (is_string($value) && isJsonFormat($value)) {
                    $value = json_decode($value, true);
                }

                $model->{$attribute} = $value;
            }
        });

        self::bootUpdatesCreatedByAndUpdatedAt();
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

    public function getStatusCodeTextAttribute()
    {
        // Check if the `status_code` column exists in the current table
        if (Schema::connection($this->getConnectionName())->hasColumn($this->getTable(), 'status_code')) {
            $statusCode = $this->attributes['status_code'] ?? null;

            if ($statusCode) {
                // Return the full status string from the Status enum
                return Status::getStatusString($statusCode);

            }
        }
        return null;
    }


    public function isNew()
    {
        $isNew = !isset($this->id);
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

    public function Attachment()
    {
        return $this->hasMany(Attachment::class, 'attached_objectid')
            ->where('attached_objecttype', class_basename($this));
    }

    public function generateTrId($code)
    {
        if (Schema::connection($this->getConnectionName())->hasColumn($this->getTable(), 'tr_id')) {
            $app_code = Session::get('app_code');
            $configSnum = ConfigSnum::where('code', '=', $code)
                ->first();
            if ($configSnum != null) {
                $stepCnt = $configSnum->step_cnt;
                $proposedTrId = $configSnum->last_cnt + $stepCnt;
                if ($proposedTrId > $configSnum->wrap_high) {
                    $proposedTrId = $configSnum->wrap_low;
                }
                $proposedTrId = max($proposedTrId, $configSnum->wrap_low);
                $configSnum->update(['last_cnt' => $proposedTrId]);
                return $proposedTrId;
            }
            // }
        }
    }

    /**
     * Fill the model with sanitized attributes.
     *
     * - Dates: Sanitized via `sanitizeDate`
     * - Numeric strings: Properly formatted (`.` and `,` swapped)
     * - Strings: Trimmed whitespace
     * - Arrays: JSON-encoded
     *
     * @param array $attributes
     */
    public function fillAndSanitize(array $attributes)
    {
        $sanitizedAttributes = [];

        foreach ($attributes as $key => $value) {
            if (isDateAttribute($value)) {
                // Sanitize Date
                $sanitizedAttributes[$key] = sanitizeDate($value);
            } elseif (isFormattedNumeric($value) !== false) {
                // Format Numeric Strings (e.g., "1.000,50" => "1000.50")
                $sanitizedAttributes[$key] = str_replace('.', '', $value);
                $sanitizedAttributes[$key] = str_replace(',', '.', $sanitizedAttributes[$key]);
            } elseif (is_array($value)) {
                // Encode Arrays as JSON
                $sanitizedAttributes[$key] = json_encode($value);
            } elseif (is_string($value)) {
                // Trim Strings
                $sanitizedAttributes[$key] = trim($value);
            } else {
                // Default Assignment
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

            // Initialize the query to check for duplicates with case-insensitive comparison
            $query = $this->newQuery()->whereRaw('UPPER(code) = ?', [$upperCode]);

            // Check if the table has an 'app_id' column
            if (Schema::connection($this->getConnectionName())->hasColumn($this->getTable(), 'app_id')) {
                // Add condition to check that the app_id is the same
                $query->where('app_id', '=', $this->app_id);
            }

            // Exclude the current model instance from the check if it is not new
            if (!$this->isNew()) {
                $query->where('id', '!=', $this->id);
            }

            // Return true if a duplicate exists
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

            // Check if the table has an 'app_id' column
            if (Schema::connection($this->getConnectionName())->hasColumn($this->getTable(), 'app_id')) {
                // Add condition to check that the app_id is the same
                $query->where('app_id', '=', $this->app_id);
            }

            // Exclude the current model instance from the check if it is not new
            if (!$this->isNew()) {
                $query->where('id', '!=', $this->id);
            }

            return $query->exists();
        }

        return false; // Return false if 'name' column does not exist
    }


}
