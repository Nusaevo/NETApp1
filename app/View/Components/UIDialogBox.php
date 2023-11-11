<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UIDialogBox extends Component
{
    public $title;
    public $body;
    public $cancelAction;
    public $continueAction;
    public $visible;

    public function __construct($title, $body, $cancelAction = null, $continueAction = null, $visible = 'false')
    {
        $this->title = $title;
        $this->body = $body;
        $this->cancelAction = $cancelAction;
        $this->continueAction = $continueAction;
        $this->visible = $visible;
    }

    public function render()
    {
        return view('components.ui-dialog-box');
    }
}
