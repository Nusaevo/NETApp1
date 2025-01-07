<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UiCard extends Component
{
    public $id;
    public $title;
    public $width;
    public $height;

    /**
     * Create a new component instance.
     *
     * @param string $id
     * @param string $title
     * @param string $width
     * @param string $height
     */
    public function __construct($id = '', $title = '', $width = '100%', $height = '')
    {
        $this->id = $id;
        $this->title = $title;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.ui-card');
    }
}
