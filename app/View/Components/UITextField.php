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
    public $required;

    public function __construct($label, $model, $type = 'text', $labelClass = '', $action = '', $required = false)
    {
        $this->label = $label;
        $this->model = $model;
        $this->type = $type;
        $this->labelClass = $labelClass;
        $this->disabled = ($action === 'View');
        $this->required = $required;
    }

    public function render()
    {
        return view('components.ui-text-field');
    }
}
