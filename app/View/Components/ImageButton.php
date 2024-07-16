<?php

namespace App\View\Components;

use Illuminate\View\Component;

class ImageButton extends Component
{
    public $hideStorageButton;
    public $action;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($hideStorageButton = "false", $action = '')
    {
        $this->hideStorageButton = $hideStorageButton;
        $this->action = $action;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.image-button');
    }
}
