<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Enums\Status;
use Illuminate\Support\Facades\Schema;
use App\Models\SysConfig1\ConfigSnum;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
                    // Use Carbon for standard timestamps (microseconds will be added later)
                    $model->created_at = Carbon::now();
                    $model->updated_by = $userId;
                    $model->updated_at = Carbon::now();
                    $model->setStatus(Status::ACTIVE);

                    // Initialize version number for new records
                    if (Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), 'version_number')) {
                        $model->version_number = 1;
                    }
                } else {
                    // This is UPDATE operation
                    $model->updated_by = $userId;
                    $model->updated_at = Carbon::now();
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

        // After the model is saved, update the timestamps with microseconds directly in DB
        // This ensures that Eloquent won't strip the microseconds
        static::saved(function ($model) {
            if ($model->timestamps !== false && isset($model->id)) {
                try {
                    // Direct DB query to ensure microsecond precision is maintained
                    $driver = DB::connection()->getDriverName();
                    $table = $model->getTable();
                    $id = $model->id;
                    $primaryKey = $model->getKeyName();

                    if ($driver === 'pgsql') {
                        // PostgreSQL timestamp microsecond update
                        DB::statement(
                            "UPDATE \"{$table}\" SET \"updated_at\" = NOW() WHERE \"{$primaryKey}\" = ?",
                            [$id]
                        );

                        if ($model->wasRecentlyCreated) {
                            // For newly created models, also update created_at
                            DB::statement(
                                "UPDATE \"{$table}\" SET \"created_at\" = NOW() WHERE \"{$primaryKey}\" = ?",
                                [$id]
                            );
                        }
                    } else if ($driver === 'mysql') {
                        // MySQL timestamp microsecond update
                        DB::statement(
                            "UPDATE `{$table}` SET `updated_at` = NOW(6) WHERE `{$primaryKey}` = ?",
                            [$id]
                        );

                        if ($model->wasRecentlyCreated) {
                            DB::statement(
                                "UPDATE `{$table}` SET `created_at` = NOW(6) WHERE `{$primaryKey}` = ?",
                                [$id]
                            );
                        }
                    }
                } catch (\Exception $e) {
                    // Log the error but don't interrupt the save process
                    \Log::error("Error updating timestamps with microseconds: " . $e->getMessage());
                }
            }
        });
    }
}
