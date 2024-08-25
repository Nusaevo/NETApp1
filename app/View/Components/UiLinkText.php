<?php
namespace App\View\Components;

class UiLinkText extends UiBaseComponent
{
    public $name;
    public $cssClass;
    public $type;

    public function __construct(
        $name,
        $clickEvent = "",
        $enabled = 'true',
        $visible = 'true',
        $action = '',
        $cssClass = '',
        $id = "",
        $type = ""
    ) {
        parent::__construct('', '', 'false', $enabled, $visible, $action, '', $clickEvent, $id);

        $this->name = $name;
        $this->cssClass = $cssClass;
        $this->type = $type;
    }

    public function render()
    {
        return view('components.ui-link-text');
    }
}
