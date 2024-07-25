<?php

namespace App\Models\SysConfig1;
use App\Models\Base\BaseModel;
use Illuminate\Support\Facades\DB;
class ConfigConst extends BaseModel
{
    protected $table = 'config_consts';
    protected $connection = 'sys-config1';

    const CURRENCY_DOLLAR_ID = '125';
    const CURRENCY_RUPIAH_ID = '124';
    public static function boot()
    {
        parent::boot();
    }

    protected $fillable = [
        'const_group',
        'app_id',
        'app_code',
        'group_id',
        'group_code',
        'user_id',
        'user_code',
        'seq',
        'str1',
        'str2',
        'num1',
        'num2',
        'note1',
    ];

    public function scopeGetActiveData()
    {
        return $this->orderBy('str1', 'asc')->get();
    }

    public function ConfigAppl()
    {
        return $this->belongsTo(ConfigAppl::class, 'app_id', 'id');
    }

    public function scopeGetWarehouse()
    {
        return $this->where('const_group', 'WAREHOUSE_LOC')
                    ->orderBy('seq', 'asc')
                    ->get();
    }

    public static function GetCurrencyData($appCode)
    {
        $data = DB::connection('sys-config1')
            ->table('config_consts')
            ->select('id', 'str1', 'str2')
            ->where('const_group', 'MCURRENCY_CODE')
            ->where('app_code', $appCode)
            ->whereNull('deleted_at')
            ->orderBy('seq')
            ->get();

        return $data;
    }

    public static function GetUOMData($appCode)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_UOM')
        ->where('app_code', $appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();
        return $data;
    }

    public static function GetMatlCategory1Data($appCode)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_CATEGL1')
        ->where('app_code', $appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();
        return $data;
    }

    public static function GetMatlCategory2Data($appCode)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_CATEGL2')
        ->where('app_code', $appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();
        return $data;
    }

    public static function GetMatlJewelPurityData($appCode)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_JEWEL_GOLDPURITY')
        ->where('app_code', $appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();

        return $data;
    }

    public static function GetMatlBaseMaterialData($appCode)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2','note1')
        ->where('const_group', 'MMATL_JEWEL_COMPONENTS')
        ->where('app_code', $appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();

        return $data;
    }


    public static function GetMatlSideMaterialShapeData($appCode)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_JEWEL_GEMSHAPES')
        ->where('app_code', $appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();

        return $data;
    }

    public static function GetMatlSideMaterialClarityData($appCode)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2','note1')
        ->where('const_group', 'MMATL_JEWEL_GIACLARITY')
        ->where('app_code', $appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();
        return $data;
    }

    public static function GetMatlSideMaterialGemColorData($appCode)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_JEWEL_GEMCOLORS')
        ->where('app_code', $appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();
        return $data;
    }


    public static function GetMatlSideMaterialGiaColorData($appCode)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_JEWEL_GIACOLORS')
        ->where('app_code', $appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();

        return $data;
    }


    public static function GetMatlSideMaterialGemstoneData($appCode)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_JEWEL_GEMSTONES')
        ->where('app_code', $appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();

        return $data;
    }


    public static function GetMatlSideMaterialCutData($appCode)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_JEWEL_GIACUT')
        ->where('app_code', $appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();

        return $data;
    }

    public static function GetMatlSideMaterialPurityData($appCode)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MMATL_JEWEL_GOLDPURITY')
        ->where('app_code', $appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();

        return $data;
    }

    public static function GetPaymentTerm($appCode)
    {
        $data = DB::connection('sys-config1')
        ->table('config_consts')
        ->select('id','str1','str2')
        ->where('const_group', 'MPAYMENT_TERMS')
        ->where('app_code', $appCode)
        ->where('deleted_at', NULL)
        ->orderBy('seq')
        ->get();
        return $data;
    }
}
