<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UiTable extends Component
{
    public $id;
    public $title;
    public $enableDataTable;
    public $padding;
    public $margin;

    public function __construct($id, $title = '', $enableDataTable = "false", $padding = "5px", $margin = "5px")
    {
        $this->id = $id;
        $this->title = $title;
        $this->enableDataTable = $enableDataTable;
        $this->padding = $padding;
        $this->margin = $margin;
    }

    public function render()
    {
        return view('components.ui-table');
    }
}
