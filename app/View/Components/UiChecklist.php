<?php

namespace App\View\Components;

class UiChecklist extends UiBaseComponent
{
    public $options;
    public $selectedValue;
    public $span;
    public $modelType;

    public function __construct(
        $options,
        $label = '',
        $model = '',
        $selectedValue = null,
        $required = 'false',
        $enabled = 'true',
        $visible = 'true',
        $action = '',
        $onChanged = '',
        $span = 'Full',
        $modelType = ''
    ) {
        parent::__construct($label, $model, $required, $enabled, $visible, $action, $onChanged, null, str_replace(['.', '[', ']'], '_', $model));

        $this->options = $options;
        $this->selectedValue = $selectedValue;
        $this->span = $span;
        $this->modelType = $modelType;
    }

    public function render()
    {
        return view('components.ui-checklist');
    }
}
