<?php

namespace App\Livewire\TrdTire1\Report\ReportStockMaterial;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
use App\Services\TrdTire1\Master\MasterService;
use App\Enums\Constant;
use App\Models\TrdTire1\Master\SalesReward;

class Index extends BaseComponent
{
    public $codeSalesreward;
    public $brand; // tambah property untuk brand
    public $brandOptions; // tambah property untuk brand options
    public $category;
    public $startCode;
    public $endCode;
    public $beg_date; // tanggal awal dari sales reward
    public $end_date; // tanggal akhir dari sales reward
    protected $masterService;

    public $results = [];

    protected $listeners = [
        'onSrCodeChanged'
    ];

    protected function onPreRender()
    {
        // Ambil brand dari query raw SQL untuk dropdown
        $query = "
            SELECT DISTINCT m.brand
            FROM ivt_bals b
            JOIN materials m ON m.id = b.matl_id
            WHERE b.qty_oh > 0 OR b.qty_fgi > 0 OR b.qty_fgr > 0
        ";
        $this->brandOptions = collect(DB::connection(Session::get('app_code'))->select($query))
            ->map(function ($item) {
                return [
                    'value' => $item->brand,
                    'label' => $item->brand,
                ];
            })->toArray();
    }
    public function search()
    {
        $this->resetErrorBag();

        $brand = addslashes($this->brand);

        $query = "
            SELECT code, name,
                sum(qty_oh_g01) g01,
                sum(qty_oh_g02) g02,
                sum(qty_oh_g04) g04,
                sum(qty_fgi) fgi
            from (
                SELECT m.code, m.name,
                    case when b.wh_code = 'G01' then qty_oh else 0 end qty_oh_g01,
                    case when b.wh_code = 'G02' then qty_oh else 0 end qty_oh_g02,
                    case when b.wh_code = 'G04' then qty_oh else 0 end qty_oh_g04,
                    case when b.wh_code = '' then qty_fgi else 0 end qty_fgi
                FROM ivt_bals b
                join materials m on m.id = b.matl_id
                " . ($brand ? "and m.brand = '{$brand}'" : "") . "
                where b.qty_oh > 0 or b.qty_fgi > 0
            ) a
            group by code, name
        ";

        $this->results = DB::connection(Session::get('app_code'))->select($query);
    }

    public function resetFilters()
    {
        $this->brand = '';
        $this->brandOptions = [];
        $this->category = '';
        $this->startCode = '';
        $this->endCode = '';
        $this->results = [];
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    public function resetResult()
    {
        $this->results = [];
    }
}
