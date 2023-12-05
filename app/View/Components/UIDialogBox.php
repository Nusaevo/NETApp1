<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UIDialogBox extends Component
{
    public $id;
    public $visible;

    public function __construct($id = null, $visible = 'false')
    {
        $this->id = $id;
        $this->visible = $visible;
    }

    public function render()
    {
        return view('components.ui-dialog-box');
    }
}
