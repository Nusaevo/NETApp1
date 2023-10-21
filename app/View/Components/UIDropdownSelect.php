<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UIDropdownSelect extends Component
{
    public $label;
    public $name;
    public $options;
    public $selectedValue;
    public $required;
    public $optionValueProperty;
    public $optionLabelProperty;
    public $enabled;
    public $visible;
    public $action;

    public function __construct($label, $name, $options, $selectedValue = null, $required = false, $optionValueProperty = 'value', $optionLabelProperty = 'label', $enabled = true, $visible = true, $action = '')
    {
        $this->label = $label;
        $this->name = $name;
        $this->options = $options;
        $this->selectedValue = $selectedValue;
        $this->required = $required;
        $this->optionValueProperty = $optionValueProperty;
        $this->optionLabelProperty = $optionLabelProperty;
        if($action === 'View')
        {
            $this->enabled = false;
        }else{
            $this->enabled = $enabled;
        }
        $this->visible = $visible;
    }

    public function render()
    {
        return view('components.ui-dropdown-select');
    }
}
