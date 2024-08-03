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
            if ($model->timestamps !== false) {
                $userId = Auth::check() ? Auth::user()->code : 'SYSTEM';
                $model->created_by = $userId;
                $model->created_at = now();
                $model->updated_by = $userId;
                $model->updated_at = now();
                $model->setStatus(Status::ACTIVE);
            }
        });

        static::updating(function ($model) {
            if ($model->timestamps !== false) {
                $userId = Auth::check() ? Auth::user()->code : 'SYSTEM';
                $model->updated_by = $userId;
                $model->updated_at = now();
                $model->version_number++;
            }
        });
    }
}
