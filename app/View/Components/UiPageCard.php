<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UiPageCard extends Component
{
    public $title;
    public $status;
    public $isForm;

    public function __construct($title = '', $status = '', $isForm = 'false')
    {
        $this->title = $title;
        $this->status = $status;
        $this->isForm = $isForm;
    }

    public function render()
    {
        return view('components.ui-page-card');
    }
}
