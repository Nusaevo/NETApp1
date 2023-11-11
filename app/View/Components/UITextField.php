<?php
namespace App\View\Components;

use Illuminate\View\Component;

class UITextField extends Component
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

    public function __construct(
        $label = '',
        $model,
        $type = 'text',
        $labelClass = '',
        $action = '',
        $required = 'false',
        $enabled = 'true',
        $visible = 'true',
        $placeHolder = 'true',
        $span = 'Full',
        $onChanged = ''
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
    }

    public function render()
    {
        return view('components.ui-text-field');
    }
}
