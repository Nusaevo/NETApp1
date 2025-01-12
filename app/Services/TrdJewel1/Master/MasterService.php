<?php

namespace App\Services\TrdJewel1\Master;

use App\Models\TrdJewel1\Master\Partner;
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

    public function getCurrencyData()
    {
        $data = $this->getConfigData('MCURRENCY_CODE');

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

    public function getPartnerTypes()
    {
        $data = $this->getConfigData('PARTNERS_TYPE');

        return $this->mapData($data);
    }

    public function getWarehouse()
    {
        $data = $this->getConfigData('WAREHOUSE_LOC', null);
        return $this->mapData($data);
    }

    public function getUOMData()
    {
        $data = $this->getConfigData('MMATL_UOM');
        return $this->mapData($data);
    }

    public function getMatlCategory1Data()
    {
        $data = $this->getConfigData('MMATL_CATEGL1');
        return $this->mapData($data);
    }

    public function getMatlCategory1String( $str1)
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

    public function getMatlCategory2Data()
    {
        $data = $this->getConfigData('MMATL_CATEGL2');
        return $this->mapData($data);
    }

    public function getMatlCategory2String( $str1)
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

    public function getMatlJewelPurityData()
    {
        $data = $this->getConfigData('MMATL_JEWEL_GOLDPURITY');
        return $this->mapData($data);
    }

    public function getMatlJewelPurityString( $str1)
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


    public function getMatlBaseMaterialData()
    {
        $data = $this->getConfigData('MMATL_JEWEL_COMPONENTS');
        return $data->map(function ($item) {
            return [
                'label' => $item->str1 . " - " . $item->str2,
                'value' => $item->id . (isset($item->note1) ? '-' . $item->note1 : ''),
            ];
        })->toArray();
    }

    public function getMatlSideMaterialOriginData()
    {
        $data = $this->getConfigData('MMATL_ORIGINS');
        return $data->map(function ($item) {
            return [
                'label' => $item->str1 . " - " . $item->str2,
                'value' => $item->id,
            ];
        })->toArray();
    }

    public function getMatlSideMaterialShapeData()
    {
        $data = $this->getConfigData('MMATL_JEWEL_GEMSHAPES');
        return $this->mapData($data);
    }

    public function getMatlSideMaterialClarityData()
    {
        $data = $this->getConfigData('MMATL_JEWEL_GIACLARITY');
        return $this->mapData($data);
    }

    public function getMatlSideMaterialGemColorData()
    {
        $data = $this->getConfigData('MMATL_JEWEL_GEMCOLORS');
        return $this->mapData($data);
    }

    public function getMatlSideMaterialGiaColorData()
    {
        $data = $this->getConfigData('MMATL_JEWEL_GIACOLORS');
        return $this->mapData($data);
    }

    public function getMatlSideMaterialGemstoneData()
    {
        $data = $this->getConfigData('MMATL_JEWEL_GEMSTONES');
        return $this->mapData($data);
    }

    public function getMatlSideMaterialCutData()
    {
        $data = $this->getConfigData('MMATL_JEWEL_GIACUT');
        return $this->mapData($data);
    }

    public function getMatlSideMaterialPurityData()
    {
        $data = $this->getConfigData('MMATL_JEWEL_GOLDPURITY');
        return $this->mapData($data);
    }


    public function getPaymentTerm()
    {
        $data = $this->getConfigData('MPAYMENT_TERMS');

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

    public function getWarehouses()
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

    public function getPrintSettings()
    {
        $data = $this->getConfigData('TRX_NJ_PRINT_OPTIONS');

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

    public function getPrintRemarks()
    {
        $data = $this->getConfigData('TRX_NJ_REMARK');

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

    public function getDefaultCurrencyStr1(): string
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

    public function globalCurrency($price = 0, $use_name = true): string
    {
        $currencyStr1 = $this->getDefaultCurrencyStr1();
        $formattedPrice = number_format($price, 2, ',', '.');
        if ($use_name) {
            return $currencyStr1 . ' ' . $formattedPrice;
        } else {
            return $formattedPrice;
        }
    }


}
