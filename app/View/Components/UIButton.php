<?php

namespace App\View\Components;

use Illuminate\View\Component;
class UIButton extends Component
{
    public $clickEvent;
    public $buttonName;
    public $loading;

    public function __construct($clickEvent, $buttonName, $loading = false)
    {
        $this->clickEvent = $clickEvent;
        $this->buttonName = $buttonName;
        $this->loading = $loading;
    }

    public function render()
    {
        return view('components.ui-button');
    }
}

