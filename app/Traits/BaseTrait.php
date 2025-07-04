<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Enums\Status;
use Illuminate\Support\Facades\Schema;
use App\Models\SysConfig1\ConfigSnum;
use Illuminate\Support\Facades\Session;

trait BaseTrait
{
    public static function bootUpdatesCreatedByAndUpdatedAt()
    {
        static::creating(function ($model) {
            // sanitizeModelAttributes($model->attributes);
            if ($model->timestamps !== false) {
                $userId = Auth::check() ? Auth::user()->code : 'SYSTEM';
                $model->created_by = $userId;
                $model->created_at = now();
                $model->updated_by = $userId;
                $model->updated_at = now();
                $model->setStatus(Status::ACTIVE);
                $model->version_number = 1;
            }
        });

        static::updating(function ($model) {
            // sanitizeModelAttributes($model->attributes);
            if ($model->timestamps !== false) {
                $userId = Auth::check() ? Auth::user()->code : 'SYSTEM';
                $model->updated_by = $userId;
                $model->updated_at = now();
                $model->version_number = $model->version_number + 1;
            }
        });
        static::retrieved(function ($model) {
            $attributes = $model->getAllColumns();
            foreach ($attributes as $attribute) {
                $value = $model->getAllColumnValues($attribute);
                if (is_numeric($value) && strpos($value, '.') !== false) {

                    $decimalPart = explode('.', $value)[1];
                    if ((int) $decimalPart === 0) {
                        $value = (int) $value;
                    }
                }

                if (is_string($value) && isJsonFormat($value)) {
                    $value = json_decode($value, true);
                }

                $model->{$attribute} = $value;
            }
        });
    }
}
