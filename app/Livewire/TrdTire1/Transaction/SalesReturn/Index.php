<?php

namespace App\Livewire\TrdTire1\Transaction\SalesReturn;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\Route;

class Index extends BaseComponent
{
    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        parent::mount($action, $objectId, $actionValue, $objectIdValue, $additionalParam);

        // // Redirect langsung ke mode create
        // $currentRoute = Route::currentRouteName();
        // $detailRoute = $currentRoute . '.Detail';

        // return $this->redirect(route($detailRoute, [
        //     'action' => encryptWithSessionKey('Create')
        // ]), navigate: true);
    }

    protected function onPreRender()
    {

    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
