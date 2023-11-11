<?php
// app/View/Components/CustomCard.php

namespace App\View\Components;

use Illuminate\View\Component;

class UIExpandableCard extends Component
{
    public $id;
    public $title;
    public $isOpen;

    public function __construct($title = '',$id='' , $isOpen = "true")
    {
        $this->id = $id;
        $this->title = $title;
        $this->isOpen = $isOpen;
    }

    public function render()
    {
        return view('components.ui-expandable-card');
    }
}
