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
    public $width;
    public $height;

    public function __construct(
        $id,
        $title = '',
        $enableDataTable = "false",
        $padding = "5px",
        $margin = "5px",
        $width = "100%",
        $height = "auto"
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->enableDataTable = $enableDataTable;
        $this->padding = $padding;
        $this->margin = $margin;
        $this->width = $width;
        $this->height = $height;
    }

    public function render()
    {
        return view('components.ui-table');
    }
}
