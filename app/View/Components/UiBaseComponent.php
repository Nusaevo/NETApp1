<?php
namespace App\View\Components;

use Illuminate\View\Component;

class UiBaseComponent extends Component
{
    // The label text displayed alongside the component (if applicable)
    public $label;

    // Model name for data binding in Livewire components
    public $model;

    // Specifies if the component is required ('true' or 'false')
    public $required;

    // Specifies if the component is enabled ('true', 'false', or 'always')
    public $enabled;

    // Controls the visibility of the component ('true' or 'false')
    public $visible;

    // Represents the action associated with the component, e.g., 'View' or 'Edit'
    public $action;

    // Name of the event triggered when the component’s value changes
    public $onChanged;

    // Click event name triggered by an associated button or element
    public $clickEvent;

    // Unique identifier for the component (HTML `id` attribute)
    public $id;

    /**
     * Constructor for the UiBaseComponent class.
     *
     * @param string $label        Label displayed alongside the component.
     * @param string $model        Livewire model name for data binding.
     * @param string $required     Determines if the component is required ('true' or 'false').
     * @param string $enabled      Determines if the component is enabled ('true', 'false', or 'always').
     * @param string $visible      Determines if the component is visible ('true' or 'false').
     * @param string $action       Action type associated with the component, e.g., 'View' or 'Edit'.
     * @param string $onChanged    Event triggered when the component’s value changes.
     * @param string|null $clickEvent Name of the click event triggered by the component.
     * @param string $id           Unique identifier for the component.
     */
    public function __construct(
        $label = '',
        $model = '',
        $required = 'false',
        $enabled = 'true',
        $visible = 'true',
        $action = '',
        $onChanged = '',
        $clickEvent = null,
        $id = ''
    ) {
        // Initialize the properties with values provided in the constructor
        $this->label = $label;
        $this->model = $model;
        $this->required = $required;
        $this->enabled = $enabled;
        $this->visible = $visible;
        $this->action = $action;
        $this->onChanged = $onChanged;
        $this->clickEvent = $clickEvent;
        $this->id = $id;
    }

    /**
     * Render method to be overridden by child components if needed.
     *
     * @throws \BadMethodCallException
     */
    public function render()
    {
        // This base component does not render a view, so render is not implemented
        throw new \BadMethodCallException('Render method is not implemented because this component does not render a view.');
    }
}
