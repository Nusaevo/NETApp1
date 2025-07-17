<?php

namespace App\View\Components;

class UiTextField extends UiBaseComponent
{
    // Input type for the text field, such as 'text', 'number', or 'password'
    public $type;

    // Placeholder text displayed in the text field when it is empty
    public $placeHolder;

    // Defines the span or width of the text field in the layout (e.g., 'Full' or 'Half')
    public $span;

    // Number of rows for the text field, used when the input type is 'textarea'
    public $rows;

    // Height of the text field, useful for textarea customization
    public $height;

    // Label for a button associated with this text field, if applicable
    public $buttonName;

    // Determines if the input should be transformed to uppercase
    public $capslockMode;

    public $buttonEnabled;

    // Currency format for number type (IDR, USD, EUR, etc.)
    public $currency;

    // Number of decimal places for number fields (null = use default behavior)
    public $decimalPlaces;

    /**
     * Constructor for the UiTextField component.
     *
     * @param string $model        Livewire model name for binding data to the field.
     * @param string $label        Label displayed alongside the text field.
     * @param string $type         Input type for the text field, e.g., 'text', 'number', 'date', 'datetime', 'textarea', 'code', 'document', 'barcode', 'image' (default: 'text').
     * @param string $action       Action type, such as 'View' or 'Edit'.
     * @param string $required     Determines if the field is required ('true' or 'false').
     * @param string $enabled      Determines if the field is enabled ('true', 'false', 'always').
     * @param string $visible      Determines if the field is visible ('true' or 'false').
     * @param string $placeHolder  Placeholder text shown in the field when empty (default: 'true').
     * @param string $span         Span or width of the field in the layout (e.g., 'Full', 'Half').
     * @param string $onChanged    Event name triggered when the field value changes.
     * @param int $rows            Number of rows displayed in the field (useful for text areas).
     * @param int $height          Custom height for the text field (useful for textarea customization).
     * @param string $clickEvent   Event triggered by an associated button or clickable element.
     * @param string $buttonName   Name or label displayed on the associated button, if any.
     * @param string $capslockMode Enables automatic uppercase transformation ('true' or 'false'). When enabled, all text is automatically converted to uppercase as the user types.
     * @param string $currency     Currency format for number type (IDR, USD, EUR, etc.).
     * @param int|null $decimalPlaces Number of decimal places for number fields (null = use default behavior).
     */
    public function __construct(
        $model,
        $label = '',
        $type = 'text',
        $action = '',
        $required = 'false',
        $enabled = 'true',
        $visible = 'true',
        $placeHolder = '',
        $span = 'Full',
        $onChanged = '',
        $rows = 5,
        $height = '',
        $clickEvent = '',
        $buttonName = "",
        $capslockMode = 'false',
        $buttonEnabled = 'true',
        $currency = '',
        $decimalPlaces = null
    ) {
        // Call parent constructor to initialize base component properties
        parent::__construct($label, $model, $required, $enabled, $visible, $action, $onChanged, $clickEvent, str_replace(['.', '[', ']'], '_', $model));

        // Set the input type for the field (e.g., 'text', 'number')
        $this->type = $type;

        // Placeholder text displayed when the field is empty
        $this->placeHolder = $placeHolder;

        // Define the span of the field in the layout (e.g., 'Full', 'Half')
        $this->span = $span;

        // Number of rows for the field (useful when it’s a textarea)
        $this->rows = $rows;

        // Custom height for the field
        $this->height = $height;

        // Name or label for an associated button
        $this->buttonName = $buttonName;

        // Enable automatic uppercase transformation
        $this->capslockMode = $capslockMode;

        $this->buttonEnabled = $buttonEnabled;

        // Currency format for number type
        $this->currency = $currency;

        // Set decimal places for number fields
        $this->decimalPlaces = $decimalPlaces;

        if ($type === 'code' && $action === 'Edit') {
            $this->enabled = 'false';
        }
    }

    /**
     * Render the view for the UiTextField component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        // Return the Blade view associated with this component
        return view('components.ui-text-field');
    }
}
