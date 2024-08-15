<?php

use App\Models\SysConfig1\ConfigMenu;
use App\Models\SysConfig1\ConfigUser;
use App\Models\SysConfig1\ConfigConst;
use App\Models\SysConfig1\ConfigRight;
if (!function_exists('populateArrayFromModel')) {
    /**
     * Populate an array with all column values from a model.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return array
     */
    function populateArrayFromModel($model)
    {
        $data = [];
        $attributes = $model->getAllColumns();

        foreach ($attributes as $attribute) {
            $value = $model->getAllColumnValues($attribute);
            $data[$attribute] = $value;
        }
        return $data;
    }
}

if (!function_exists('imagePath')) {
    /**
     * Get the path of an image file.
     *
     * @param string $imageName
     * @return string
     */
    function imagePath($imageName)
    {
        return asset('customs/images/' . $imageName);
    }
}

if (!function_exists('isNullOrEmptyString')) {
    /**
     * Get string
     *
     * @return void
     */
    function isNullOrEmptyString($str) {
        return !isset($str) || trim($str) === '';
    }
}

if (!function_exists('isNullOrEmptyNumber')) {
    /**
     * Check if a number is null, empty or zero
     *
     * @param mixed $num
     * @return bool
     */
    function isNullOrEmptyNumber($num) {
        return !isset($num) || $num === null || $num === '' || $num == 0;
    }
}

if (!function_exists('isNullOrEmptyDateTime')) {
    /**
     * Check if a date/time is null, empty, or invalid
     *
     * @param mixed $date
     * @return bool
     */
    function isNullOrEmptyDateTime($date) {
        if (!isset($date) || $date === null || $date === '') {
            return true;
        }

        // Use DateTime to validate the date format
        try {
            $dt = new DateTime($date);
            return false;
        } catch (Exception $e) {
            return true;
        }
    }
}
