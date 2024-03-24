<?php
// app/View/Components/UITd.php

namespace App\View\Components;

use Illuminate\View\Component;

class UITd extends Component
{
    public $width;

    public function __construct($width = '')
    {
        $this->width = $width;
    }

    public function render()
    {
        return view('component.ui-td');
    }
}
