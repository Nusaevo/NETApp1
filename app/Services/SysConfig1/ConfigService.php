<?php

namespace App\Services\SysConfig1;

use App\Models\SysConfig1\{ConfigAppl, ConfigConst, ConfigUser};
use Illuminate\Support\Facades\Auth;
use App\Services\Base\BaseService;


class ConfigService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

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
                            $query->join('config_appls', 'config_groups.app_id', '=', 'config_appls.id')
                            ->select('config_groups.app_id')
                            ->orderBy('config_appls.seq');
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

    public function getApp()
    {
        $appIds = $this->getAppIds();

        return ConfigAppl::whereIn('id', $appIds)
            ->orderBy('seq')
            ->get();
    }

    public function getAppCodes()
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $appIds = ConfigUser::where('id', $userId)
                        ->with(['ConfigGroup' => function($query) {
                            $query->join('config_appls', 'config_groups.app_id', '=', 'config_appls.id')
                            ->select('config_appls.code')
                            ->orderBy('config_appls.seq');
                        }])
                        ->firstOrFail()
                        ->ConfigGroup
                        ->pluck('app_Code')
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

    public function getConstIdByStr1($const_group, $str1): int
    {
        $configConst = ConfigConst::where('const_group', $const_group)
            ->where('str1', $str1)
            ->first();

        return $configConst->id ?? 0;
    }

    /**
     * Helper: Dapatkan category material dari ConfigConst berdasarkan sales_type
     */
    public function getCategoryBySalesType($salesType)
    {
        $config = ConfigConst::where('const_group', 'MMATL_CATEGORY')
            ->where('str1', $salesType)
            ->get();
            // dd($config, $salesType);
        return $config ? $config->str2 : null;
    }
}
