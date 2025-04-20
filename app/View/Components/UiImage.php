<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UiImage extends Component
{
    public $src;
    public $alt;
    public $width;
    public $height;

    /**
     * Create a new component instance.
     *
     * @param string $src
     * @param string $alt
     * @param string $width
     * @param string $height
     * @param string $onClicked
     */
    public function __construct($src, $alt = 'Image', $width = '50px', $height = '50px')
    {
        $this->src = $src;
        $this->alt = $alt;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('components.ui-image');
    }
}
