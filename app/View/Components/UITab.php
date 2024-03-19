<?php
namespace App\View\Components;

use Illuminate\View\Component;

class UITab extends Component
{
    public $id;
    public $active;

    public function __construct($id = '', $active = 'true')
    {
        $this->id = $id;
        $this->active = $active;
    }

    public function render()
    {
        return view('components.ui-tab');
    }
}
