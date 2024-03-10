<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use App\Enums\Status;
use Illuminate\Support\Facades\Schema;
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
        });
    }

}
