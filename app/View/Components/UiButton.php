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

    // Delete functionality properties
    public $isDelete;
    public $enableConfirmationDialog;
    public $permissions;
    public $status;
    public $object;

    /**
     * Constructor for the UiButton component.
     *
     * @param string $buttonName  Name or label displayed on the button (optional, defaults based on type).
     * @param string $clickEvent  Livewire click event associated with the button (default: empty string).
     * @param string $loading     Flag to indicate loading state ('true' or 'false').
     * @param string $enabled     Determines if the button is enabled ('true', 'false', or 'always').
     * @param string $visible     Determines if the button is visible ('true' or 'false').
     * @param string $action      Button action type, e.g., 'View', 'Edit' (default: empty string).
     * @param string $cssClass    CSS class(es) applied to the button for additional styling.
     * @param string|null $iconPath Path to an icon displayed on the button, or null if no icon.
     * @param string $type        Button type - use 'delete' for delete functionality, 'save' for save functionality.
     * @param string $id          Unique ID for the button element.
     * @param string $dataBsTarget Target attribute for Bootstrap modals.
     * @param string $jsClick     JavaScript function or code executed on click.
     * @param string $enableConfirmationDialog Enable confirmation dialog for delete ('true' or 'false').
     * @param array $permissions  Permission array for delete authorization.
     * @param string $status      Transaction status for delete validation.
     * @param mixed $object       Object for deletion state check.
     */
    public function __construct(
        $buttonName = '',
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
        $jsClick = "",
        $enableConfirmationDialog = 'true',
        $permissions = [],
        $status = 'OPEN',
        $object = null
    ) {
        // Call parent constructor to initialize base component properties
        parent::__construct('', '', 'false', $enabled, $visible, $action, '', $clickEvent, $id);

        // Set the button label
        $this->buttonName = $buttonName;

        // Set loading state (default: 'true')
        $this->loading = $loading ?? "true";

        // Set default values based on button type
        if ($type === 'delete') {
            $this->buttonName = $buttonName ?: 'Hapus';
            $this->cssClass = $cssClass ?: 'btn-danger';
            $this->iconPath = $iconPath ?: 'delete.svg';
        } elseif ($type === 'save') {
            $this->buttonName = $buttonName ?: 'Simpan';
            $this->cssClass = $cssClass ?: 'btn-primary';
            $this->iconPath = $iconPath ?: 'save.svg';
        } else {
            $this->buttonName = $buttonName;
            $this->cssClass = $cssClass;
            $this->iconPath = $iconPath;
        }

        // Define the button's type attribute, e.g., 'button', 'submit'
        $this->type = $type;

        // Set the target for Bootstrap modal or popover (if applicable)
        $this->dataBsTarget = $dataBsTarget;

        // JavaScript function or code to execute on button click
        $this->jsClick = $jsClick;

        // Delete functionality
        $this->isDelete = ($type === 'delete');
        $this->enableConfirmationDialog = $enableConfirmationDialog;
        $this->permissions = $permissions;
        $this->status = $status;
        $this->object = $object;

        // Add delete dialog CSS class if needed
        if ($this->isDelete && $enableConfirmationDialog === 'true') {
            $this->cssClass = trim($this->cssClass . ' btn-delete-dialog');
        }
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
