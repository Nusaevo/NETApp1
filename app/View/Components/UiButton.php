<?php

namespace App\View\Components;

class UiButton extends UiBaseComponent
{
    // The name or label displayed on the button
    public $buttonName;

    // Flag indicating whether to show a loading state on the button (default: 'true')
    public $loading;

    // CSS class(es) applied to the button for styling
    public $cssClass;

    // Path to an icon displayed on the button (if any)
    public $iconPath;

    // Button type, e.g., 'submit', 'button', 'reset', or custom type
    public $type;

    // Target for Bootstrap modal data attributes (if using Bootstrap modals)
    public $dataBsTarget;

    // JavaScript function or code to execute when the button is clicked
    public $jsClick;

    /**
     * Constructor for the UiButton component.
     *
     * @param string $buttonName  Name or label displayed on the button.
     * @param string $clickEvent  Livewire click event associated with the button (default: empty string).
     * @param string $loading     Flag to indicate loading state ('true' or 'false').
     * @param string $enabled     Determines if the button is enabled ('true', 'false', or 'always').
     * @param string $visible     Determines if the button is visible ('true' or 'false').
     * @param string $action      Button action type, e.g., 'View', 'Edit' (default: empty string).
     * @param string $cssClass    CSS class(es) applied to the button for additional styling.
     * @param string|null $iconPath Path to an icon displayed on the button, or null if no icon.
     * @param string $type        Button type attribute (e.g., 'submit', 'button', etc.).
     * @param string $id          Unique ID for the button element.
     * @param string $dataBsTarget Target attribute for Bootstrap modals.
     * @param string $jsClick     JavaScript function or code executed on click.
     */
    public function __construct(
        $buttonName,
        $clickEvent = "",
        $loading = 'true',
        $enabled = 'true',
        $visible = 'true',
        $action = '',
        $cssClass = '',
        $iconPath = null,
        $type = "",
        $id = "",
        $dataBsTarget = "",
        $jsClick = ""
    ) {
        // Call parent constructor to initialize base component properties
        parent::__construct('', '', 'false', $enabled, $visible, $action, '', $clickEvent, $id);

        // Set the button label
        $this->buttonName = $buttonName;

        // Set loading state (default: 'true')
        $this->loading = $loading ?? "true";

        // Apply additional CSS classes if specified
        $this->cssClass = $cssClass;

        // Set the path for an optional icon displayed on the button
        $this->iconPath = $iconPath;

        // Define the button's type attribute, e.g., 'button', 'submit'
        $this->type = $type;

        // Set the target for Bootstrap modal or popover (if applicable)
        $this->dataBsTarget = $dataBsTarget;

        // JavaScript function or code to execute on button click
        $this->jsClick = $jsClick;
    }

    /**
     * Render the button component view.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        // Return the corresponding Blade view for the button component
        return view('components.ui-button');
    }
}
