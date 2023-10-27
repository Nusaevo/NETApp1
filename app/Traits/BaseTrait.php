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
        static::creating(function ($model) {
            $model->updated_by = Auth::user()->code;
            $model->updated_at = now();
        });

        static::updating(function ($model) {
            $model->updated_at = now();
            $model->updated_by = Auth::user()->code;
        });
    }
}
