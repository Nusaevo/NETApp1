<?php
namespace App\View\Components;

use Illuminate\View\Component;

class UITextFieldSearch extends Component
{
    public $clickEvent;
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
    public $action;

    public function __construct($clickEvent, $label ='', $model, $name, $enabled = 'true', $required = 'false', $visible = 'true',
    $placeHolder = '', $options = [], $selectedValue = '', $span = 'Full', $action = '')
    {
        $this->clickEvent = $clickEvent;
        $this->label = $label;
        $this->model = $model;
        $this->action = $action;
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
