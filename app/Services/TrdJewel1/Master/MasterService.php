<?php

namespace App\Services\TrdJewel1\Master;

use Illuminate\Support\Facades\DB;
use App\Models\TrdJewel1\Master\Partner;

class MasterService
{
    protected $connection;

    public function __construct()
    {
        $this->connection = DB::connection('sys-config1');
    }

    protected function getConfigData($constGroup, $appCode)
    {
        return $this->connection
            ->table('config_consts')
            ->select('id', 'str1', 'str2', 'note1')
            ->where('const_group', $constGroup)
            ->where('app_code', $appCode)
            ->whereNull('deleted_at')
            ->orderBy('seq')
            ->get();
    }

    protected function mapData($data)
    {
        return $data->map(function ($item) {
            return [
                'label' => $item->str1 . " - " . $item->str2,
                'value' => $item->str1,
            ];
        })->toArray();
    }

    public function getCurrencyData($appCode)
    {
        $data = $this->getConfigData('MCURRENCY_CODE', $appCode);

        $currencies = $data->map(function ($item) {
            return [
                'label' => $item->str1 . " - " . $item->str2,
                'value' => $item->id,
            ];
        })->toArray();

        $defaultCurrency = $currencies[0] ?? null;
        return [
            'currencies' => $currencies,
            'defaultCurrency' => $defaultCurrency
        ];
    }

    public function getPartnerTypes($appCode)
    {
        $data = $this->getConfigData('PARTNERS_TYPE', $appCode);

        return $this->mapData($data);
    }

    public function getWarehouse()
    {
        $data = $this->getConfigData('WAREHOUSE_LOC', null);
        return $this->mapData($data);
    }

    public function getUOMData($appCode)
    {
        $data = $this->getConfigData('MMATL_UOM', $appCode);
        return $this->mapData($data);
    }

    public function getMatlCategory1Data($appCode)
    {
        $data = $this->getConfigData('MMATL_CATEGL1', $appCode);
        return $this->mapData($data);
    }

    public function getMatlCategory1String($appCode, $str1)
    {
        $data = $this->connection
            ->table('config_consts')
            ->select('str2')
            ->where('const_group', 'MMATL_CATEGL1')
            ->where('app_code', $appCode)
            ->where('str1', $str1)
            ->whereNull('deleted_at')
            ->first();

        return $data ? $data->str2 : null;
    }

    public function getMatlCategory2Data($appCode)
    {
        $data = $this->getConfigData('MMATL_CATEGL2', $appCode);
        return $this->mapData($data);
    }

    public function getMatlCategory2String($appCode, $str1)
    {
        $data = $this->connection
            ->table('config_consts')
            ->select('str2')
            ->where('const_group', 'MMATL_CATEGL2')
            ->where('app_code', $appCode)
            ->where('str1', $str1)
            ->whereNull('deleted_at')
            ->first();

        return $data ? $data->str2 : null;
    }

    public function getMatlJewelPurityData($appCode)
    {
        $data = $this->getConfigData('MMATL_JEWEL_GOLDPURITY', $appCode);
        return $this->mapData($data);
    }

    public function getMatlJewelPurityString($appCode, $str1)
    {
        $data = $this->connection
            ->table('config_consts')
            ->select('str2')
            ->where('const_group', 'MMATL_JEWEL_GOLDPURITY')
            ->where('app_code', $appCode)
            ->where('str1', $str1)
            ->whereNull('deleted_at')
            ->first();

        return $data ? $data->str2 : null;
    }

    public function getMatlBaseMaterialData($appCode)
    {
        $data = $this->getConfigData('MMATL_JEWEL_COMPONENTS', $appCode);
        return $data->map(function ($item) {
            return [
                'label' => $item->str1 . " - " . $item->str2,
                'value' => $item->id . (isset($item->note1) ? '-' . $item->note1 : ''),
            ];
        })->toArray();
    }

    public function getMatlSideMaterialShapeData($appCode)
    {
        $data = $this->getConfigData('MMATL_JEWEL_GEMSHAPES', $appCode);
        return $this->mapData($data);
    }

    public function getMatlSideMaterialClarityData($appCode)
    {
        $data = $this->getConfigData('MMATL_JEWEL_GIACLARITY', $appCode);
        return $this->mapData($data);
    }

    public function getMatlSideMaterialGemColorData($appCode)
    {
        $data = $this->getConfigData('MMATL_JEWEL_GEMCOLORS', $appCode);
        return $this->mapData($data);
    }

    public function getMatlSideMaterialGiaColorData($appCode)
    {
        $data = $this->getConfigData('MMATL_JEWEL_GIACOLORS', $appCode);
        return $this->mapData($data);
    }

    public function getMatlSideMaterialGemstoneData($appCode)
    {
        $data = $this->getConfigData('MMATL_JEWEL_GEMSTONES', $appCode);
        return $this->mapData($data);
    }

    public function getMatlSideMaterialCutData($appCode)
    {
        $data = $this->getConfigData('MMATL_JEWEL_GIACUT', $appCode);
        return $this->mapData($data);
    }

    public function getMatlSideMaterialPurityData($appCode)
    {
        $data = $this->getConfigData('MMATL_JEWEL_GOLDPURITY', $appCode);
        return $this->mapData($data);
    }

    public function getPaymentTerm($appCode)
    {
        $data = $this->getConfigData('MPAYMENT_TERMS', $appCode);
        return $this->mapData($data);
    }

    public function getSuppliers()
    {
        $suppliersData = Partner::GetByGrp(Partner::SUPPLIER);
        return $suppliersData->map(function ($data) {
            return [
                'label' => $data->code . " - " . $data->name,
                'value' => $data->id,
            ];
        })->toArray();
    }

    public function getCustomers()
    {
        $suppliersData = Partner::GetByGrp(Partner::CUSTOMER);
        return $suppliersData->map(function ($data) {
            return [
                'label' => $data->code . " - " . $data->name,
                'value' => $data->id,
            ];
        })->toArray();
    }

    public function getWarehouses($appCode)
    {
        return $this->connection
            ->table('config_consts')
            ->select('id', 'str1')
            ->where('const_group', 'WAREHOUSE_LOC')
            ->where('app_code', $appCode)
            ->whereNull('deleted_at')
            ->orderBy('seq')
            ->get()
            ->map(function ($data) {
                return [
                    'label' => $data->str1,
                    'value' => $data->id,
                ];
            })->toArray();
    }
}
