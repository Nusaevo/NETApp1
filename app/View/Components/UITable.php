<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UITable extends Component
{
    public $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.ui-table');
    }
}
