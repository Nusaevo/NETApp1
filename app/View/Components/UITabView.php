<?php

// app/View/Components/UITabView.php

namespace App\View\Components;

use Illuminate\View\Component;

class UITabView extends Component
{
    public $id;

    public function __construct($id = '')
    {
        $this->id = $id;
    }

    public function render()
    {
        return view('components.ui-tab-view');
    }
}
