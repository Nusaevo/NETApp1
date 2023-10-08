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

    public function __construct($label, $name, $options, $selectedValue = null, $required = false, $optionValueProperty = 'value', $optionLabelProperty = 'label')
    {
        $this->label = $label;
        $this->name = $name;
        $this->options = $options;
        $this->selectedValue = $selectedValue;
        $this->required = $required;
        $this->optionValueProperty = $optionValueProperty;
        $this->optionLabelProperty = $optionLabelProperty;
    }

    public function render()
    {
        return view('components.ui-dropdown-select');
    }
}
