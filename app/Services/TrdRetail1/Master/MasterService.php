<?php

namespace App\Services\TrdRetail1\Master;

use App\Services\Base\BaseService;
use App\Models\TrdRetail1\Master\{Material, Partner};

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

    public function isValidMatlCategory($str1)
    {
        $result = $this->mainConnection
            ->table('config_consts')
            ->select('str1')
            ->where('const_group', 'MMATL_CATEGL1')
            ->where('str1', $str1)
            ->whereNull('deleted_at')
            ->first();
        return $result ? $result->str1 : null;
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

    public function getWarehouseData()
    {
        $data = $this->getConfigData('MWAREHOUSE_LOCL1');
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

    public function getMatlCategoryDetail(string $str1): ?object
    {
        return $this->mainConnection
            ->table('config_consts')
            ->select('str2', 'num1')
            ->where('const_group', 'MMATL_CATEGL1')
            ->where('str1', $str1)
            ->whereNull('deleted_at')
            ->first();
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
    public function getMaterials()
    {
        $materialsData = Material::all();
        return $materialsData->map(function ($data) {
            return [
                'label' => $data->code . " - " . $data->name,
                'value' => $data->id,
            ];
        })->toArray();
    }
    public function getMatlBrandData()
    {
        // Ambil semua brand unik dari tabel materials
        $brands = Material::distinct('brand')->whereNotNull('brand')->get(['brand']);

        // Mapping menjadi label-value
        return $brands->map(function ($item) {
            return [
                'label' => $item->brand,
                'value' => $item->brand,
            ];
        })->toArray();
    }

    public function getMatlTypeData()
    {
        // Ambil semua type (class_code) unik dari tabel materials
        $types = Material::distinct('class_code')->whereNotNull('class_code')->get(['class_code']);

        // Mapping menjadi label-value
        return $types->map(function ($item) {
            return [
                'label' => $item->class_code,
                'value' => $item->class_code,
            ];
        })->toArray();
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
}
