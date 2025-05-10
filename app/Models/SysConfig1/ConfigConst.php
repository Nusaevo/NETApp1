<?php

namespace App\Models\SysConfig1;
use App\Models\Base\BaseModel;
use App\Models\SysConfig1\ConfigSnum;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Session;

class ConfigConst extends BaseModel
{
    protected $table = 'config_consts';

    use SoftDeletes;

    const CURRENCY_DOLLAR_ID = '125';
    const CURRENCY_RUPIAH_ID = '124';

    protected $fillable = [
        'const_group',
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
    public $labels = [
        // Geographical
        'GEO_CONTINENTS'    => 'Benua',
        'GEO_COUNTRIES'     => 'Negara',
        'GEO_COUNTRYL2'     => 'Provinsi / States',
        'GEO_COUNTRYL3'     => 'Kabupaten / Kotamadya / County',
        'GEO_COUNTRYL4'     => 'Kecamatan',
        'GEO_COUNTRYL5'     => 'Kelurahan',

        // People
        'PPL_GENDERS'       => 'Gender di Indonesia',

        // Masters
        'MASTER_STATUS'         => 'Status di semua Masters',
        'MACCOUNT_CLASSES'      => 'CoA - Klasifikasi utama',
        'MPARTNER_TYPES'        => 'Type di Master Partners',
        'MMATL_UOM'             => 'Unit of Measure di Materials',
        'MMATL_CATEGL1'         => 'Kategori 1',
        'MMATL_CATEGL2'         => 'Kategori 2',
        'MMATL_CATEGL3'         => 'Kategori 3',
        'MMATL_JEWEL_GIACOLORS'  => 'Material - Jewel - GIA Color Scale',
        'MMATL_JEWEL_GIACLARITY' => 'Material - Jewel - GIA Clarity Scale',
        'MMATL_JEWEL_GIACUT'     => 'Material - Jewel - GIA Cut Scale',
        'MMATL_JEWEL_GEMSTONES'  => 'Material - Jewel - Gemstone Types',
        'MMATL_JEWEL_GEMCOLORS'  => 'Material - Jewel - Gemstone Colours',
        'MMATL_JEWEL_GEMSHAPES'  => 'Material - Jewel - Gemstone Shapes',
        'MMATL_JEWEL_GOLDPURITY' => 'Material - Jewel - Gold Purity',
        'MMATL_JEWEL_COMPONENTS' => 'Material - Jewel - Components/Bahan',
        'MMATL_PATTERN'         => 'Kode Pattern pada TrdTire',
        'MMATL_JENIS'           => 'Kode Jenis pada TrdTire',
        'MMATL_MERK'            => 'Kode Merk',
        'MMATL_TYPE'            => 'Tipe Material',
        'MWAREHOUSE_LOCL1'      => 'Gudang - Lokasi',
        'MWAREHOUSE_LOCL2'      => 'Gudang - Gedung/Area',
        'MPAYMENT_TERMS'        => 'Payment Terms',
        'MCURRENCY_CODE'        => 'Currency Codes',

        // Transactions
        'TRX_STATUS'            => 'Status di semua Transactions',
        'TRX_NJ_REMARK'         => 'Catatan Pada Nota Jual',
        'TRX_SALES_TYPE'        => 'Sales Type TrdTire',
        'TRX_INV_TYPE'          => 'Tipe Transaksi Inventory',
        'TRX_SO_TAX'            => 'PPN',
        'TRX_PAYMENT_TYPE'      => 'Payment Type',
        'TRX_PAYMENT_SRCS'      => 'Jenis Pembayaran',

        // Systems
        'BASE_CURRENCY'         => 'Base Currency',
    ];

    protected static $serialNumberMappings = [
        'TrdJewel1' => [
            'MMATL_CATEGL1' => "Serial Number untuk Category pada TrdJewel1"
        ],
        'TrdTire1' => [
            'MMATL_MERK' => "Serial Number untuk Merk pada TrdTire1",
        ]
    ];

    public static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            $appCode = session('app_code');

            if ($appCode && isset(self::$serialNumberMappings[$appCode])) {
                $appMapping = self::$serialNumberMappings[$appCode];

                if (isset($appMapping[$model->const_group])) {
                    $description = $appMapping[$model->const_group];
                    if($appCode == 'TrdRetail1') {
                        ConfigSnum::create([
                            'code' => "MMATL_" . $model->str1 . "_LASTID",
                            'last_cnt' => 0,
                            'wrap_low' => 1000,
                            'wrap_high' => 99999999,
                            'step_cnt' => 1,
                            'descr' => $description . " " . $model->str1
                        ]);
                    }else{
                        ConfigSnum::create([
                            'code' => "MMATL_" . $model->str1 . "_LASTID",
                            'last_cnt' => 0,
                            'wrap_low' => 1,
                            'wrap_high' => 99999999,
                            'step_cnt' => 1,
                            'descr' => $description . " " . $model->str1
                        ]);
                    }
                }
            } else {
                \Log::warning("App code not found or invalid in session for ConfigConst creation.");
            }
        });
    }


    #region Relations

    // public function ConfigAppl()
    // {
    //     return $this->belongsTo(ConfigAppl::class, 'app_id', 'id');
    // }

    #endregion

    #region Attributes
    #endregion

    public function scopeGetActiveData()
    {
        return $this->orderBy('str1', 'asc')->get();
    }

}
