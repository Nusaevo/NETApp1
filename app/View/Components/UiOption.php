<?php

namespace App\View\Components;

class UiOption extends UiBaseComponent
{
    public $options;
    public $selectedValue;
    public $span;
    public $type; // 'checkbox', 'radio'
    public $layout;

    public function __construct(
        $options = [],
        $label = '',
        $model = '',
        $selectedValue = null,
        $required = 'false',
        $enabled = 'true',
        $visible = 'true',
        $action = '',
        $onChanged = '',
        $span = 'Full',
        $type = 'checkbox', // Default type is checkbox
        $layout = 'horizontal' // Default to horizontal
    ) {
        parent::__construct($label, $model, $required, $enabled, $visible, $action, $onChanged, null, str_replace(['.', '[', ']'], '_', $layout));

        $this->options = $options;
        $this->selectedValue = $selectedValue ?? $this->initializeSelectedValue($options, $type);
        $this->span = $span;
        $this->type = $type;
        $this->layout = $layout;
    }

    /**
     * Mengatur nilai default berdasarkan tipe.
     */
    protected function initializeSelectedValue($options, $type)
    {
        if ($type === 'checkbox') {
            return is_array($options) ? array_fill_keys(array_keys($options), false) : false;
        } elseif ($type === 'radio') {
            return null;
        }

        return null;
    }

    public function render()
    {
        return view('components.ui-option');
    }
}


/*
single checkbox
<x-ui-option
    label="Select Multiple Options"
    model="inputs.option"
    :options="['Yes' => 'Yes xxxx']"
    type="checkbox"
    layout="vertical"
/>

multiple checkbox
<x-ui-option
    label="Select Multiple Options"
    model="inputs.option"
    :options="['RK' => 'Option 1', 'RB' => 'Option 2']"
    type="checkbox"
    layout="vertical"
/>

radio button
<x-ui-option
    label="Select Multiple Options"
    model="inputs.option"
    :options="['RK' => 'Option 1', 'RB' => 'Option 2']"
    type="radio"
    layout="vertical"
/>
*/
