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
            $userId = Auth::check() ? Auth::user()->code : 'SYSTEM';
            $model->created_by = $userId;
            $model->created_at = now();
            $model->updated_by = $userId;
            $model->updated_at = now();
            $model->setStatus(Status::ACTIVE);
        });

        static::updating(function ($model) {
            $userId = Auth::check() ? Auth::user()->code : 'SYSTEM';
            $model->updated_by = $userId;
            $model->updated_at = now();
            $model->version_number++;
        });

        static::saving(function ($model) {
            if (Schema::connection($model->getConnectionName())->hasColumn($model->getTable(), 'tr_id')) {
                if ($model->status_code == Status::ACTIVE) {
                    if ($model->isDirty('status_code')) {
                        $app_code = Session::get('app_code');
                        $configSnum = ConfigSnum::where('object_type', 'LIKE', '%' . $model->tr_type . '%')
                                    ->where('object_name', 'LIKE', get_class($model))
                                    ->where('app_code', '=', $app_code)->first();

                        if ($configSnum != null) {
                            $stepCnt = $configSnum->step_cnt;
                            $proposedTrId = $configSnum->last_cnt + $stepCnt;

                            // Check if the proposedTrId exceeds wrap_high. If so, reset to wrap_low or handle accordingly.
                            if ($proposedTrId > $configSnum->wrap_high) {
                                // This is a basic approach: reset to wrap_low. Adjust as necessary for your use case.
                                $proposedTrId = $configSnum->wrap_low;
                            }

                            // Ensure proposedTrId also respects wrap_low, in case last_cnt + stepCnt is below wrap_low.
                            $proposedTrId = max($proposedTrId, $configSnum->wrap_low);

                            // Assign the validated tr_id.
                            $model->tr_id = $proposedTrId;

                            // Update the ConfigSnum's last_cnt to the new tr_id.
                            $configSnum->update(['last_cnt' => $model->tr_id]);
                        } else {
                            // Fallback to using the model's id if no ConfigSnum is found.
                            // Consider your application's requirements for this case.
                            $model->tr_id = $model->id;
                        }
                    }
                }
            }
        });
    }
}
