<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UiDialogBox extends Component
{
    public $id;
    public $title;
    public $visible;
    public $width;
    public $height;

    /**
     * Constructor for the UiDialogBox component.
     *
     * @param string|null $id       Dialog box ID.
     * @param string $title         Title of the dialog box.
     * @param string $visible       Determines if the dialog box is visible ('true' or 'false').
     * @param string $width         Width of the dialog box (e.g., '500px', '50%').
     * @param string $height        Height of the dialog box (e.g., '500px', 'auto').
     */
    public function __construct($id = null, $title = '', $visible = 'false', $width = 'auto', $height = 'auto')
    {
        $this->id = $id ?? 'default-dialog';
        $this->title = $title;
        $this->visible = $visible;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Render the view for the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('components.ui-dialog-box');
    }
}
