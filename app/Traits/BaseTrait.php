<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait BaseTrait
{
    public function updateObject($versionNumberFromPage)
    {
        if ($this->checkVersionNumber($versionNumberFromPage)) {
            $this->updated_at = now();
            $this->updated_by = Auth::id();
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
}
