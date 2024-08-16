<?php

// app/View/Components/UITabView.php

// app/View/Components/UITabView.php

namespace App\View\Components;

use Illuminate\View\Component;

class UiTabView extends Component
{
    public $id;
    public $tabs;

    public function __construct($id = '', $tabs = '')
    {
        $this->id = $id;
        $this->tabs = $tabs;
    }

    public function render()
    {
        return view('components.ui-tab-view');
    }
}
