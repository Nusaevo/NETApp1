<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UIPageCard extends Component
{
    public $title;
    public $status;

    public function __construct($title = '',$status='')
    {
        $this->title = $title;
        $this->status = $status;
    }

    public function render()
    {
        return view('component.ui-page-card');
    }
}
