<?php

namespace App\Traits;

trait LivewireTrait
{
    public $is_edit_mode = false;

    public function setEditMode(bool $status)
    {
        $this->is_edit_mode = $status;
    }
}
