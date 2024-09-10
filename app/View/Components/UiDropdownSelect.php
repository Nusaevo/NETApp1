<?php
namespace App\View\Components;

class UiDropdownSelect extends UiBaseComponent
{
    public $options;
    public $selectedValue;
    public $span;
    public $modelType;
    public string $type;
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
        $modelType = '',
        $clickEvent = null,
        $type = 'string'
    ) {
        parent::__construct($label, $model, $required, $enabled, $visible, $action, $onChanged, $clickEvent, str_replace(['.', '[', ']'], '_', $model));

        $this->options = $options;
        $this->selectedValue = $selectedValue;
        $this->span = $span;
        $this->modelType = $modelType;
        $this->type = $type;
    }

    public function render()
    {
        return view('components.ui-dropdown-select');
    }
}
