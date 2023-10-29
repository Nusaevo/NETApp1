<?php
namespace App\View\Components;

use Illuminate\View\Component;

class UITextFieldSearch extends Component
{
    public $label;
    public $model;
    public $enabled;
    public $required;
    public $visible;
    public $placeHolder;
    public $span;
    public $options;
    public $selectedValue;
    public $name;

    public function __construct($label, $model, $name, $enabled = true, $required = false, $visible = true,
    $placeHolder = '', $options = [], $selectedValue = '', $span = 'Full')
    {
        $this->label = $label;
        $this->model = $model;
        $this->enabled = $enabled;
        $this->required = $required;
        $this->visible = $visible;
        $this->placeHolder = $placeHolder;
        $this->span = $span;
        $this->options = $options;
        $this->selectedValue = $selectedValue;
        $this->name = $name;
    }

    public function render()
    {
        return view('components.ui-text-field-search');
    }
}
