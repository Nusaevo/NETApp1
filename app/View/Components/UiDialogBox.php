<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UiDialogBox extends Component
{
    public $id;
    public $title;
    public $width;
    public $height;
    public $onOpened;
    public $onClosed;

    /**
     * Create a new component instance.
     *
     * @param string $id
     * @param string $title
     * @param string $width
     * @param string $height
     * @param string|null $onOpen
     * @param string|null $onClose
     */
    public function __construct($id, $title='', $width = '600px', $height = '400px', $onOpened = null, $onClosed = null)
    {
        $this->id = $id;
        $this->title = $title;
        $this->width = $width;
        $this->height = $height;
        $this->onOpened = $onOpened;
        $this->onClosed = $onClosed;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('components.ui-dialog-box');
    }
}
