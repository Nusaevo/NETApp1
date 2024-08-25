<?php

namespace App\View\Components;

class UiTextField extends UiBaseComponent
{
    public $type;
    public $placeHolder;
    public $span;
    public $rows;
    public $buttonName;

    public function __construct(
        $model,
        $label = '',
        $type = 'text',
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
        parent::__construct($label, $model, $required, $enabled, $visible, $action, $onChanged, $clickEvent, str_replace(['.', '[', ']'], '_', $model));

        $this->type = $type;
        $this->placeHolder = $placeHolder;
        $this->span = $span;
        $this->rows = $rows;
        $this->buttonName = $buttonName;
    }

    public function render()
    {
        return view('components.ui-text-field');
    }
}

