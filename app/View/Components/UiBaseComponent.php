<?php
namespace App\View\Components;

use Illuminate\View\Component;

class UiBaseComponent extends Component
{
    public $label;
    public $model;
    public $required;
    public $enabled;
    public $visible;
    public $action;
    public $onChanged;
    public $clickEvent;
    public $id;

    public function __construct($label = '', $model = '', $required = 'false', $enabled = 'true', $visible = 'true',
                                $action = '', $onChanged = '', $clickEvent = null, $id = '')
    {
        $this->label = $label;
        $this->model = $model;
        $this->required = $required;
        $this->enabled = $enabled;
        $this->visible = $visible;
        $this->action = $action;
        $this->onChanged = $onChanged;
        $this->clickEvent = $clickEvent;
        $this->id = $id;
    }

    public function render()
    {
    }
}
