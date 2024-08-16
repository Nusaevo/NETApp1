<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UiTable extends Component
{
    public $id;
    public $title;
    public $enableDataTable;

    public function __construct($id, $title = '', $enableDataTable = "false")
    {
        $this->id = $id;
        $this->title = $title;
        $this->enableDataTable = $enableDataTable;
    }

    public function render()
    {
        return view('components.ui-table');
    }
}
