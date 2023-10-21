<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UIButton extends Component
{
    public $clickEvent;
    public $buttonName;
    public $loading;
    public $enabled;
    public $visible;
    public $action;

    public function __construct($clickEvent, $buttonName, $loading = false, $enabled = true, $visible = true, $action = '')
    {
        $this->clickEvent = $clickEvent;
        $this->buttonName = $buttonName;
        $this->loading = $loading;
        if($action === 'View')
        {
            $this->visible = false;
        }else{
            $this->visible = $visible;
        }
        $this->enabled = $enabled;
    }

    public function render()
    {
        return view('components.ui-button');
    }
}
