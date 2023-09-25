<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UITextField extends Component
{
    public $label;
    public $model;
    public $type;
    public $labelClass;
    public $disabled;

    public function __construct($label, $model, $type = 'text', $labelClass = '', $disabled = false)
    {
        $this->label = $label;
        $this->model = $model;
        $this->type = $type;
        $this->labelClass = $labelClass;
        $this->disabled = $disabled;
    }

    public function render()
    {
        return view('components.ui-text-field');
    }
}
