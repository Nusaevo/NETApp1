<?php
namespace App\Livewire\TrdRetail2\Home;

use App\Livewire\Component\BaseComponent;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
class Index extends BaseComponent
{
    #region Constant Variables


    #endregion

    #region Populate Data methods
    protected function onPreRender()
    {
        $this->bypassPermissions = true;
    }

    public function render()
    {
        return view('livewire.index');
    }

    #endregion

    #region CRUD Methods


    #endregion

    #region Component Events


    #endregion

}
