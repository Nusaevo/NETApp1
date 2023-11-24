<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait BaseTrait
{
    public function updateObject($versionNumberFromPage)
    {
        if ($this->checkVersionNumber($versionNumberFromPage)) {
             $this->version_number++;
        } else {
            throw new \Exception("This object has already been updated by another user. Please refresh the page and try again.");
        }
    }

    public function checkVersionNumber($versionNumberFromPage)
    {
        $currentVersionNumber = $this->version_number;
        return $versionNumberFromPage == $currentVersionNumber;
    }

    public static function bootUpdatesCreatedByAndUpdatedAt()
    {
        if(Auth::user() == null)
        {
            static::creating(function ($model) {
                $model->created_by = "SYSTEM";
                $model->created_at = now();
            });

            static::updating(function ($model) {
                $model->updated_at = now();
                $model->updated_by = "SYSTEM";
            });
        }else{
            static::creating(function ($model) {
                $model->created_by = Auth::user()->code;
                $model->created_at = now();
            });

            static::updating(function ($model) {
                $model->updated_at = now();
                $model->updated_by = Auth::user()->code;
            });
        }
    }
}
