<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class SequenceUtility
{
    public static function getCurrentSequenceValue(Model $model)
    {
        $primaryKey = $model->getKeyName();
        $table = $model->getTable();
        $sequenceName = $table . '_' . $primaryKey . '_seq';

        $result = DB::select("SELECT last_value FROM $sequenceName");
        return isset($result[0]->last_value) ? $result[0]->last_value : null;
    }
}
