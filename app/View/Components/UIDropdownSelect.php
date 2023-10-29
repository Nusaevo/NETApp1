<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UIDropdownSelect extends Component
{
    public $label;
    public $name;
    public $options;
    public $required;
    public $enabled;
    public $visible;
    public $action;
    public $selectedValue;
    public $onChanged;

    public function __construct($label, $name, $options, $selectedValue = null, $required = false, $enabled = true, $visible = true, $action = '', $onChanged = '')
    {
        $this->label = $label;
        $this->name = $name;
        $this->options = $options;
        $this->selectedValue = $selectedValue;
        $this->required = $required;
        if($action === 'View')
        {
            $this->enabled = false;
        }else{
            $this->enabled = $enabled;
        }
        $this->visible = $visible;
        $this->action = $action;
        $this->onChanged = $onChanged;
    }

    public function render()
    {
        return view('components.ui-dropdown-select');
    }
}
