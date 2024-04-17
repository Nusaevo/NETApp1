<?php

namespace App\Http\Livewire\TrdJewel1\Report\ReportSample;


use App\Models\Tigaputra\Master\CategoryItem;
use App\Http\Livewire\Component\BaseComponent;
use App\Models\Tigaputra\Master\Item;
use DB;

class Index extends BaseComponent
{
    public $inputs = [];
    public $items = [];
    public $item_price = [];
    public $category = [];
    public $dateStart;
    public $dateEnd;

    public $categories;
    public $results = [];

    public function refreshItemCategory()
    {
    }


    public function render()
    {
        $this->inputs['name'] = '';

        $this->dateStart = now()->startOfMonth()->format('Y-m-d');
        $this->dateEnd = now()->format('Y-m-d');

        $this->refreshItemCategory();
        return view($this->renderRoute);
    }

    public function resetResult()
    {
        $this->results = [];
        $this->emit('refreshDataTable');
    }

    public function search()
    {
        $keyword = strtolower($this->inputs['name']);

        $query = "SELECT * FROM Materials";

        if ($keyword) {
            $query .= " WHERE LOWER(descr) LIKE '%{$keyword}%'";
        }

        $data = DB::select($query);
        $this->results = $data;
        $this->emit('refreshDataTable');
    }
}
