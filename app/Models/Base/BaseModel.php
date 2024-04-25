<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Base\Attachment;
use App\Traits\BaseTrait;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use App\Enums\Status;
use App\Models\SysConfig1\ConfigSnum;


class BaseModel extends Model
{
    use HasFactory, SoftDeletes;
    use BaseTrait;

    protected $fillable = [];
    protected static function boot()
    {
        parent::boot();
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

    public function isNew()
    {
        $isNew = empty($this->id);
        return  $isNew;
    }

    public function setStatus($value)
    {
        if (Schema::connection($this->getConnectionName())->hasColumn($this->getTable(), 'status_code')) {
            $this->attributes['status_code'] = $value;
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
            $configSnum = ConfigSnum::where('app_code', '=', $app_code)
                ->where('code', '=', $code)
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

    public function fillAndSanitize(array $attributes)
    {
        $sanitizedAttributes = [];

        foreach ($attributes as $key => $value) {
            if (isDateAttribute($value)) {
                $sanitizedAttributes[$key] = sanitizeDate($value);
            } elseif ((isFormattedNumeric($value) !== false) ){
                $sanitizedAttributes[$key] = str_replace('.', '', $value);
                $sanitizedAttributes[$key] = str_replace(',', '.', $sanitizedAttributes[$key]);
            } else {
                $sanitizedAttributes[$key] = $value;
            }
        }
        $this->fill($sanitizedAttributes);
    }
}
