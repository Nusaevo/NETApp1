<?php

namespace App\View\Components;

use Illuminate\View\Component;

class TesComponent extends Component
{
    public $items;
    public $selectedItems;
    public $placeholder;
    public $name;
    public $label;
    public $multiple;
    public $enabled;
    public $onChanged;
    public $action;
    public $required;

    public function __construct($items = [], $selectedItems = [], $placeholder = '', $name = 'tes_component', $label = null, $multiple = true, $enabled = true, $onChanged = null, $action = null, $required = false)
    {
        $this->items = $items;
        $this->selectedItems = $selectedItems;
        $this->placeholder = $placeholder;
        $this->name = $name;
        $this->label = $label;
        $this->multiple = $multiple;
        $this->enabled = $enabled ?? true;
        $this->onChanged = $onChanged;
        $this->action = $action;
        $this->required = $required;
    }

    public function render()
    {
        return view('components.ui-tes-component');
    }
}
