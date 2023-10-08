<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UIButton extends Component
{
    public $clickEvent;
    public $buttonName;

    public function __construct($clickEvent, $buttonName)
    {
        $this->clickEvent = $clickEvent;
        $this->buttonName = $buttonName;
    }

    public function render()
    {
        return view('components.ui-button');
    }
}
