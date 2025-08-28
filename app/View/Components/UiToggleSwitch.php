<?php

namespace App\View\Components;

class UiToggleSwitch extends UiBaseComponent
{
    public $model;
    public $onChanged;
    public $showLabel;
    public $label;
    public $value;

    public function __construct(
        $model = '',
        $onChanged = '',
        $showLabel = false,
        $label = '',
        $value = false,
        $enabled = 'true',
        $action = ''
    ) {
        parent::__construct('', $model, 'false', $enabled, 'true', $action, $onChanged);

        $this->model = $model;
        $this->onChanged = $onChanged;
        $this->showLabel = $showLabel;
        $this->label = $label;
        $this->value = $value;
    }

    public function render()
    {
        return view('components.ui-toggle-switch');
    }
}
