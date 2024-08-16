<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UiDropdownSelect extends Component
{
    public $label;
    public $model;
    public $options;
    public $required;
    public $enabled;
    public $visible;
    public $action;
    public $selectedValue;
    public $onChanged;
    public $span;
    public $modelType;
    public $clickEvent;
    public $id;

    public function __construct($options, $label = '', $model = '', $selectedValue = null, $required = 'false',
    $enabled = 'true', $visible = 'true', $action = '', $onChanged = '', $span = 'Full', $modelType = '', $clickEvent = null)
    {
        $this->label = $label;
        $this->model = $model;
        $this->options = $options;
        $this->selectedValue = $selectedValue;
        $this->required = $required;
        $this->enabled = $enabled;
        $this->visible = $visible;
        $this->action = $action;
        $this->onChanged = $onChanged;
        $this->span = $span;
        $this->modelType = $modelType;
        $this->clickEvent = $clickEvent;
    }

    public function render()
    {
        return view('components.ui-dropdown-select');
    }
}
