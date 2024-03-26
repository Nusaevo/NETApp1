<?php
// app/View/Components/CustomCard.php

namespace App\View\Components;

use Illuminate\View\Component;

class UIFooter extends Component
{
    public $id;

    public function __construct($id='')
    {
        $this->id = $id;
    }

    public function render()
    {
        return view('component.ui-footer');
    }
}
