<?php

namespace App\View\Components;

class UiChecklist extends UiBaseComponent
{
    public $options;
    public $selectedValue;
    public $span;
    public $type;
    public $layout;

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
        $layout = 'horizontal' // Default to horizontal
    ) {
        parent::__construct($label, $model, $required, $enabled, $visible, $action, $onChanged, null, str_replace(['.', '[', ']'], '_', $layout));

        $this->options = $options;
        $this->selectedValue = $selectedValue ?? $this->initializeSelectedValue($options);
        $this->span = $span;
        $this->type = $layout;
        $this->layout = $layout;
    }

    /**
     * Mengatur nilai default untuk setiap opsi.
     */
    protected function initializeSelectedValue($options)
    {
        $defaultValues = [];
        foreach ($options as $key => $value) {
            $defaultValues[$key] = false;
        }
        return $defaultValues;
    }

    public function render()
    {
        return view('components.ui-checklist');
    }
}
