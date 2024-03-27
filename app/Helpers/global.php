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
        $data = [] ;
        $attributes = $model->getAllColumns();

        foreach ($attributes as $attribute) {
            $data[$attribute] = $model->getAllColumnValues($attribute);
        }
        return $data;
    }
}

if (!function_exists('populateModelFromForm')) {
    /**
     * Populate a model's attributes from form input data.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @param array $formData
     * @return void
     */
    function populateModelFromForm($model, $formData)
    {
        $data = [] ;
        $attributes = $model->getAllColumns();
        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $formData)) {
                $data[$attribute] = $formData[$attribute];
            }
        }
        return $data;
    }
}

if (!function_exists('mapDropdownData')) {
    /**
     * Map data to the desired structure.
     *
     * @param mixed $data
     * @param string $labelKey
     * @param string $valueKey
     * @return array
     */
    function mapDropdownData($data, $labelKeys, $valueKey)
    {
        if ($data instanceof \Illuminate\Support\Collection) {
            return $data->map(function ($item) use ($labelKeys, $valueKey) {
                $label = collect($labelKeys)->map(function ($labelKey) use ($item) {
                    return $item->$labelKey;
                })->implode(' - ');

                return [
                    'label' => $label,
                    'value' => $item->$valueKey,
                ];
            })->toArray();
        }

        // Handle other data types or return an empty array by default
        return [];
    }

}

if (!function_exists('getAppIds')) {
    function getAppIds()
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $appIds = ConfigUser::where('id', $userId)
                        ->with(['ConfigGroup' => function($query) {
                            $query->select('app_id');
                        }])
                        ->firstOrFail()
                        ->ConfigGroup
                        ->pluck('app_id')
                        ->unique()
                        ->toArray();

            return $appIds;
        }

        return [];
    }
}
if (!function_exists('getConstValueByStr1')) {
    function getConstValueByStr1($const_group, $str1)
    {
        $configConst = ConfigConst::where('const_group', $const_group)
                                  ->where('str1', $str1)
                                  ->first();

        return $configConst->str2 ?? '';
    }
}

if (!function_exists('getConstValueByID')) {
    function getConstValueByID($const_group, $id)
    {
        $configConst = ConfigConst::where('const_group', $const_group)
                                  ->where('id', $id)
                                  ->first();

        return $configConst->str2 ?? '';
    }
}
