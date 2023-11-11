<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UIChecklist extends Component
{
    public $options = [];
    public $name;
    public $enabled;
    public $visible;
    public $label;
    public $action;

    public function __construct($label, $options, $action, $name, $enabled = true, $visible = true)
    {
        $this->label = $label;
        $this->options = $options;
        $this->name = $name;
        if($action == 'View')
        {
            $this->enabled = false;
        }else{
            $this->enabled = $enabled;
        }
        $this->visible = $visible;
    }

    public function render()
    {
        return view('components.ui-checklist');
    }
}
