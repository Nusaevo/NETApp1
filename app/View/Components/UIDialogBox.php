<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UIDialogBox extends Component
{
    public $id;
    public $visible;
    public $width;
    public $height;

    public function __construct($id = null, $visible = 'false', $width = 'auto', $height = 'auto')
    {
        $this->id = $id;
        $this->visible = $visible;
        $this->width = $width;
        $this->height = $height;
    }

    public function render()
    {
        return view('component.ui-dialog-box');
    }
}
