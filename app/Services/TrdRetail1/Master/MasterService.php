<?php

namespace App\Services\TrdRetail1\Master;

use Illuminate\Support\Facades\DB;
use App\Enums\Constant;
use App\Services\Base\BaseService;

class MasterService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getConfigData($constGroup)
    {
        return $this->mainConnection
            ->table('config_consts')
            ->select('id', 'str1', 'str2', 'note1')
            ->where('const_group', $constGroup)
            ->whereNull('deleted_at')
            ->orderBy('seq')
            ->get();
    }
    protected function getSysConfigData($constGroup)
    {
        return $this->configConnection
            ->table('config_consts')
            ->select('id', 'str1', 'str2', 'note1')
            ->where('const_group', $constGroup)
            ->whereNull('deleted_at')
            ->orderBy('seq')
            ->get();
    }
    public function isValidMatlCategory($str2)
    {
        $result = $this->mainConnection
            ->table('config_consts')
            ->select('str1')
            ->where('const_group', 'MMATL_CATEGL1')
            ->where('str2', $str2)
            ->whereNull('deleted_at')
            ->first();
        return $result ? $result->str1 : null;
    }


    public function getPartnerTypes($appCode)
    {
        $data = $this->getConfigData('PARTNERS_TYPE');

        return $this->mapData($data);
    }

    public function getWarehouse()
    {
        $data = $this->getConfigData('WAREHOUSE_LOC', null);
        return $this->mapData($data);
    }

    public function getMatlCategoryData()
    {
        $data = $this->getConfigData('MMATL_CATEGL1');
        return $this->mapData($data);
    }

    public function getMatlUOMData()
    {
        $data = $this->getConfigData('MMATL_UOM');
        return $data->map(function ($item) {
            return [
                'label' => $item->str2,
                'value' => $item->str1,
            ];
        })->toArray();
    }

    public function getMatlCategoryString($str1)
    {
        $data = $this->mainConnection
            ->table('config_consts')
            ->select('str2')
            ->where('const_group', 'MMATL_CATEGL1')

            ->where('str1', $str1)
            ->whereNull('deleted_at')
            ->first();

        return $data ? $data->str2 : null;
    }


    // public function getSuppliers()
    // {
    //     $suppliersData = Partner::GetByGrp(Partner::SUPPLIER);
    //     return $suppliersData->map(function ($data) {
    //         return [
    //             'label' => $data->code . " - " . $data->name,
    //             'value' => $data->id,
    //         ];
    //     })->toArray();
    // }



    // public function getCustomers()
    // {
    //     $suppliersData = Partner::GetByGrp(Partner::CUSTOMER);
    //     return $suppliersData->map(function ($data) {
    //         return [
    //             'label' => $data->code . " - " . $data->name,
    //             'value' => $data->id,
    //         ];
    //     })->toArray();
    // }


}
