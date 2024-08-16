<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UiTextField extends Component
{
    public $label;
    public $model;
    public $type;
    public $enabled;
    public $required;
    public $visible;
    public $placeHolder;
    public $span;
    public $onChanged;
    public $action;
    public $rows;
    public $id;
    public $clickEvent;
    public $buttonName;

    public function __construct(
        $model,
        $label = '',
        $type = 'text',
        $labelClass = '',
        $action = '',
        $required = 'false',
        $enabled = 'true',
        $visible = 'true',
        $placeHolder = 'true',
        $span = 'Full',
        $onChanged = '',
        $rows = 5,
        $clickEvent = '',
        $buttonName = ""
    ) {
        $this->label = $label;
        $this->model = $model;
        $this->type = $type;
        $this->action = $action;
        $this->enabled = $enabled;
        $this->required = $required;
        $this->visible = $visible;
        $this->placeHolder = $placeHolder;
        $this->span = $span;
        $this->onChanged = $onChanged;
        $this->rows = $rows;
        $this->clickEvent = $clickEvent;
        $this->buttonName = $buttonName;
    }

    public function render()
    {
        return view('components.ui-text-field');
    }
}
