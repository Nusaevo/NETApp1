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
    public $cssClass;
    public $iconPath;
    public $type;
    public $id;

    public function __construct($clickEvent, $buttonName, $loading = 'true', $enabled = 'true',
    $visible = 'true', $action = '', $cssClass = '', $iconPath = null, $type = "", $id = "")
    {
        $this->clickEvent = $clickEvent;
        $this->buttonName = $buttonName;
        $this->loading = $loading ?? "true";
        $this->action = $action;
        $this->visible = $visible;
        $this->cssClass = $cssClass;
        $this->enabled = $enabled;
        $this->iconPath = $iconPath;
        $this->type = $type;
        $this->id = $id;
    }

    public function render()
    {
        return view('components.ui-button');
    }
}
