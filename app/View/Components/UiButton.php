<?php

namespace App\View\Components;

class UiButton extends UiBaseComponent
{
    public $buttonName;
    public $loading;
    public $cssClass;
    public $iconPath;
    public $type;
    public $dataBsTarget;
    public $jsClick;

    public function __construct(
        $buttonName,
        $clickEvent = "",
        $loading = 'true',
        $enabled = 'true',
        $visible = 'true',
        $action = '',
        $cssClass = '',
        $iconPath = null,
        $type = "",
        $id = "",
        $dataBsTarget = "",
        $jsClick = ""
    ) {
        parent::__construct('', '', 'false', $enabled, $visible, $action, '', $clickEvent, $id);

        $this->buttonName = $buttonName;
        $this->loading = $loading ?? "true";
        $this->cssClass = $cssClass;
        $this->iconPath = $iconPath;
        $this->type = $type;
        $this->dataBsTarget = $dataBsTarget;
        $this->jsClick = $jsClick;
    }

    public function render()
    {
        return view('components.ui-button');
    }
}
