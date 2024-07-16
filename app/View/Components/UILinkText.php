<?php
// app/View/Components/UiLinkText.php
namespace App\View\Components;

use Illuminate\View\Component;

class UiLinkText extends Component
{
    public $clickEvent;
    public $name;
    public $enabled;
    public $visible;
    public $action;
    public $cssClass;
    public $id;
    public $type;

    public function __construct($clickEvent = "", $name, $enabled = 'true', $visible = 'true', $action = '', $cssClass = '', $id = "", $type = "")
    {
        $this->clickEvent = $clickEvent;
        $this->name = $name;
        $this->enabled = $enabled;
        $this->visible = $visible;
        $this->action = $action;
        $this->cssClass = $cssClass;
        $this->id = $id;
        $this->type = $type;
    }

    public function render()
    {
        return view('components.ui-link-text');
    }
}

