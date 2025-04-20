<?php
namespace App\View\Components;

class UiDropdownSelect extends UiBaseComponent
{
    // Array of options for the dropdown, each item represents an option in the dropdown menu
    public $options;

    // The value currently selected in the dropdown
    public $selectedValue;

    // Defines the span or width of the dropdown component in the layout (e.g., 'Full' or 'Half')
    public $span;

    // Specifies the type of binding model, such as 'lazy' or 'debounce', to control data binding behavior
    public $modelType;

    // The expected data type of the dropdown value (e.g., 'string', 'int')
    public string $type;

    // Placeholder text displayed in the text field when it is empty
    public $placeHolder;
    public $buttonName;
    public $buttonEnabled;
    /**
     * Constructor for the UiDropdownSelect component.
     *
     * @param array $options        Array of options for the dropdown menu.
     * @param string $label         Label displayed alongside the dropdown.
     * @param string $model         Livewire model name for binding data to the dropdown.
     * @param mixed $selectedValue  The default or pre-selected value for the dropdown.
     * @param string $required      Determines if the field is required ('true' or 'false').
     * @param string $enabled       Determines if the dropdown is enabled ('true', 'false', 'always').
     * @param string $visible       Determines if the dropdown is visible ('true' or 'false').
     * @param string $action        Action type, such as 'View' or 'Edit'.
     * @param string $onChanged     Event name triggered when the dropdown value changes.
     * @param string $span          Span or width of the dropdown in the layout (e.g., 'Full' or 'Half').
     * @param string $modelType     Type of Livewire binding model (e.g., 'lazy', 'debounce').
     * @param string|null $clickEvent Event triggered by an associated button or clickable element.
     * @param string $type          The expected data type of the dropdown value (default: 'string').
     */
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
        $type = 'string',
        $placeHolder = '',
        $buttonName = "",
        $buttonEnabled = 'true'
    ) {
        // Call parent constructor to initialize base component properties
        parent::__construct($label, $model, $required, $enabled, $visible, $action, $onChanged, $clickEvent, str_replace(['.', '[', ']'], '_', $model));

        // Set the options for the dropdown
        $this->options = $options;

        // Set the initially selected value for the dropdown
        $this->selectedValue = $selectedValue;

        // Define the span or layout width of the dropdown (e.g., 'Full' or 'Half')
        $this->span = $span;

        // Specify the model type, controlling data binding behavior (e.g., 'lazy', 'debounce')
        $this->modelType = $modelType;

        // Define the expected data type of the dropdown value (e.g., 'string', 'int')
        $this->type = $type;

        // Placeholder text displayed when the field is empty
        $this->placeHolder = $placeHolder;
        // Name or label for an associated button
        $this->buttonName = $buttonName;
        $this->buttonEnabled = $buttonEnabled;
    }

    /**
     * Render the view for the UiDropdownSelect component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        // Return the Blade view associated with this component
        return view('components.ui-dropdown-select');
    }
}
