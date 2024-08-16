<?php

namespace App\Services\SysConfig1;

use App\Models\SysConfig1\ConfigAppl;
use App\Models\SysConfig1\ConfigConst;
use App\Models\SysConfig1\ConfigUser;
use Illuminate\Support\Facades\Auth;

class ConfigService
{
    public function getActiveApplications($accessRequired = false)
    {
        $applicationsData = ConfigAppl::GetActiveData();

        if ($accessRequired) {
            $accessibleAppIds = $this->getAppIds();
            $applicationsData = $applicationsData->whereIn('id', $accessibleAppIds);
        }

        return $applicationsData->map(function ($data) {
            return [
                'label' => $data->code . ' - ' . $data->name,
                'value' => $data->id,
            ];
        })->toArray();
    }

    public function getAppIds()
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $appIds = ConfigUser::where('id', $userId)
                        ->with(['ConfigGroup' => function($query) {
                            $query->select('app_id')->orderBy('app_id', 'desc');
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

    public function getConstValueByStr1($const_group, $str1)
    {
        $configConst = ConfigConst::where('const_group', $const_group)
                                  ->where('str1', $str1)
                                  ->first();

        return $configConst->str2 ?? '';
    }

    public function getConstValueByID($const_group, $id)
    {
        $configConst = ConfigConst::where('const_group', $const_group)
                                  ->where('id', $id)
                                  ->first();

        return $configConst->str2 ?? '';
    }
}
