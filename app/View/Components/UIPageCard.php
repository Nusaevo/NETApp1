<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UIPageCard extends Component
{
    public $title;

    public function __construct($title = '')
    {
        $this->title = $title;
    }

    public function render()
    {
        return view('components.ui-page-card');
    }
}
