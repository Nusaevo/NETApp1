<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Enums\Status;
use Illuminate\Support\Facades\Schema;
use App\Models\SysConfig1\ConfigSnum;
use Illuminate\Support\Facades\Session;

trait BaseTrait
{
    public static function bootBaseTrait()
    {
        // Single event to handle both CREATE and UPDATE operations
        static::saving(function ($model) {
            sanitizeModelAttributesAuto($model, $model->attributes);

            if ($model->timestamps !== false) {
                $userId = Auth::check() ? Auth::user()->code : 'SYSTEM';

                if (!$model->exists) {
                    // This is CREATE operation
                    $model->created_by = $userId;
                    $model->created_at = now();
                    $model->updated_by = $userId;
                    $model->updated_at = now();

                    // Initialize version number for new records
                    if (Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), 'version_number')) {
                        $model->version_number = 1;
                    }
                } else {
                    // This is UPDATE operation
                    $model->updated_by = $userId;
                    $model->updated_at = now();
                    $model->setStatus(Status::ACTIVE);

                    // Increment version number for updates
                    if (Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), 'version_number')) {
                        $oldVersion = $model->version_number ?? 0;
                        $model->version_number = $oldVersion + 1;
                    }
                }
            }
        });

        // Handle model retrieval for JSON decoding
        static::retrieved(function ($model) {
            $attributes = $model->getAllColumns();
            foreach ($attributes as $attribute) {
                $value = $model->getAllColumnValues($attribute);
                if (is_string($value) && isJsonFormat($value)) {
                    $value = json_decode($value, true);
                }
                $model->{$attribute} = $value;
            }
        });
    }
}
