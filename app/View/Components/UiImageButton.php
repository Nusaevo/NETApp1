<?php

namespace App\View\Components;

class UiImageButton extends UiBaseComponent
{
    // CSS class(es) for styling the button
    public $cssClass;

    // Target for Bootstrap modal data attributes (if using Bootstrap modals)
    public $dataBsTarget;

    // JavaScript function or code to execute when the button is clicked
    public $jsClick;

    public $hideStorageButton;

    /**
     * Constructor for the UiImageButton component.
     *
     * @param string $clickEvent  Livewire click event associated with the button.
     * @param string $enabled     Determines if the button is enabled ('true', 'false', or 'always').
     * @param string $visible     Determines if the button is visible ('true' or 'false').
     * @param string $cssClass    CSS class(es) applied to the button for styling.
     * @param string $dataBsTarget Target attribute for Bootstrap modals.
     * @param string $jsClick     JavaScript function or code executed on click.
     */
    public function __construct(
        $clickEvent = "",
        $enabled = 'true',
        $visible = 'true',
        $cssClass = '',
        $dataBsTarget = "",
        $jsClick = "",
        $hideStorageButton = ""
    ) {
        // Call parent constructor to initialize base component properties
        parent::__construct('', '', 'false', $enabled, $visible, '', '', $clickEvent, $hideStorageButton);
        // Apply additional CSS classes if specified
        $this->cssClass = $cssClass;

        // Set the target for Bootstrap modal or popover (if applicable)
        $this->dataBsTarget = $dataBsTarget;

        // JavaScript function or code to execute on button click
        $this->jsClick = $jsClick;

        $this->hideStorageButton = $hideStorageButton;
    }

    /**
     * Render the image button component view.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('components.ui-image-button');
    }
}
