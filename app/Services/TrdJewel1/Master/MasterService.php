<?php

namespace App\Services\TrdJewel1\Master;

use Illuminate\Support\Facades\DB;
use App\Models\TrdJewel1\Master\Partner;
use App\Enums\Constant;
use App\Services\Base\BaseService;

class MasterService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function getConfigData($constGroup, $appCode)
    {
        return $this->mainConnection
            ->table('config_consts')
            ->select('id', 'str1', 'str2', 'note1')
            ->where('const_group', $constGroup)

            ->whereNull('deleted_at')
            ->orderBy('seq')
            ->get();
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
        $data = $this->mainConnection
            ->table('config_consts')
            ->select('str2')
            ->where('const_group', 'MMATL_CATEGL1')

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
        $data = $this->mainConnection
            ->table('config_consts')
            ->select('str2')
            ->where('const_group', 'MMATL_CATEGL2')

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
        $data = $this->mainConnection
            ->table('config_consts')
            ->select('str1', 'str2')
            ->where('const_group', 'MMATL_JEWEL_GOLDPURITY')

            ->where('str1', $str1)
            ->whereNull('deleted_at')
            ->first();

        return $data ? $data->str1 . " - " . $data->str2 : null;
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

    public function getMatlSideMaterialOriginData($appCode)
    {
        $data = $this->getConfigData('MMATL_ORIGINS', $appCode);
        return $data->map(function ($item) {
            return [
                'label' => $item->str1 . " - " . $item->str2,
                'value' => $item->id,
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

        $payments = $data->map(function ($item) {
            return [
                'label' => $item->str1 . " - " . $item->str2,
                'value' => $item->id,
            ];
        })->toArray();
        return $payments;
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
        return $this->mainConnection
            ->table('config_consts')
            ->select('id', 'str1')
            ->where('const_group', 'WAREHOUSE_LOC')

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

    public function getPrintSettings($appCode)
    {
        $data = $this->getConfigData('TRX_NJ_PRINT_OPTIONS', $appCode);

        $options = $data->map(function ($item) {
            return [
                'code' => $item->str1,
                'label' => $item->str2,
                'value' => $item->id,
                'checked' => false
            ];
        })->toArray();

        return $options;
    }

    public function getPrintRemarks($appCode)
    {
        $data = $this->getConfigData('TRX_NJ_REMARK', $appCode);

        $options = $data->map(function ($item) {
            return [
                'code' => $item->str1,
                'label' => $item->str2,
                'value' => $item->id,
                'checked' => false
            ];
        })->toArray();

        return $options;
    }

    public function getDefaultCurrencyStr1($appCode): string
    {
        $defaultCurrency = $this->mainConnection
            ->table('config_consts')
            ->select('str1')
            ->where('const_group', 'MCURRENCY_CODE')

            ->whereNull('deleted_at')
            ->orderByDesc('num1')
            ->first();

        return $defaultCurrency ? $defaultCurrency->str1 : '';
    }

    public function globalCurrency($price = 0, $use_name = true, $appCode = null): string
    {
        $currencyStr1 = $this->getDefaultCurrencyStr1($appCode);
        $formattedPrice = number_format($price, 2, ',', '.');
        if ($use_name) {
            return $currencyStr1 . ' ' . $formattedPrice;
        } else {
            return $formattedPrice;
        }
    }


}
