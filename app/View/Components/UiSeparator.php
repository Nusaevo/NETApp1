<?php
namespace App\View\Components;

use Illuminate\View\Component;

class UiSeparator extends Component
{
    public $caption;
    public $visible;
    public $orientation;
    public $orientationMargin;
    public $header;
    public $type;
    public $span;

    public function __construct($caption = null, $visible = 'true', $orientation = "left", $orientationMargin = 5, $header = 'true', $type = 'horizontal', $span = 'Full')
    {
        $this->caption = $caption;
        $this->visible = $visible;
        $this->orientation = $orientation;
        $this->orientationMargin = $orientationMargin;
        $this->header = $header;
        $this->type = $type;
        $this->span = $span;
    }

    public function render()
    {
        return view('components.ui-separator');
    }
}
