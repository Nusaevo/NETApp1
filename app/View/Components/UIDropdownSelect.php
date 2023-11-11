<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UIDropdownSelect extends Component
{
    public $clickEvent;
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

    public function __construct($clickEvent = null, $label = '', $model, $options, $selectedValue = null, $required = 'false', $enabled = 'true', $visible = 'true', $action = '', $onChanged = '', $span = 'Full')
    {
        $this->clickEvent = $clickEvent;
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
    }

    public function render()
    {
        return view('components.ui-dropdown-select');
    }
}
