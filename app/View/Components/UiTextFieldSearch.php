<?php
namespace App\View\Components;

class UiTextFieldSearch extends UiBaseComponent
{
    // Array of options for the text field, such as a list of items for a dropdown
    public $options;

    // The currently selected value in the options list (if any)
    public $selectedValue;

    // Defines the span or width of the component in the layout (e.g., 'Full', 'Half')
    public $span;

    // Specifies the type of binding model (e.g., 'lazy' or 'debounce')
    public $modelType;

    // Specifies the type of data expected in the field (e.g., 'string', 'int')
    public string $type;

    // Placeholder text displayed in the text field when it is empty
    public $placeHolder;
    public $buttonName;
    public $buttonEnabled;
    /**
     * Constructor for the UiTextFieldSearch component.
     *
     * @param array $options        Array of options for the text field.
     * @param string $label         Label displayed alongside the field.
     * @param string $model         Livewire model name for binding data.
     * @param mixed $selectedValue  The default or pre-selected value for the text field.
     * @param string $required      Determines if the field is required ('true' or 'false').
     * @param string $enabled       Determines if the field is enabled ('true', 'false', 'always').
     * @param string $visible       Determines if the field is visible ('true' or 'false').
     * @param string $action        Action type, such as 'View' or 'Edit'.
     * @param string $onChanged     Event name triggered when the field changes.
     * @param string $span          Span or width of the field in the layout (e.g., 'Full' or 'Half').
     * @param string $modelType     Type of Livewire binding model (e.g., 'lazy', 'debounce').
     * @param string|null $clickEvent Event triggered by the field's associated button or clickable element.
     * @param string $type          Type of input data expected (default: 'string').
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
        $buttonName = '',
        $buttonEnabled = 'true'
    ) {
        // Call parent constructor to initialize base component properties
        parent::__construct($label, $model, $required, $enabled, $visible, $action, $onChanged, $clickEvent);

        // Initialize the field options (e.g., dropdown list items)
        $this->options = $options;

        // Set the default selected value for the field (if any)
        $this->selectedValue = $selectedValue;

        // Define the span of the field in the layout
        $this->span = $span;

        // Specify the model binding type (e.g., 'lazy', 'debounce')
        $this->modelType = $modelType;

        // Define the type of data expected in the field (e.g., 'string', 'int')
        $this->type = $type;

        // Placeholder text displayed when the field is empty
        $this->placeHolder = $placeHolder;
        // Name or label for an associated button
        $this->buttonName = $buttonName;
        $this->buttonEnabled = $buttonEnabled;
    }

    /**
     * Render the view for the UiTextFieldSearch component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        // Return the Blade view associated with this component
        return view('components.ui-text-field-search');
    }
}
