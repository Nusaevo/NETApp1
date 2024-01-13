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

        $result = DB::table($table)->max($primaryKey);
        return $result;
    }
}
