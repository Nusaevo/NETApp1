<?php
namespace App\Livewire\Component;

use Livewire\Component;

class RfidScanner extends Component
{
    public $scannedTags = [];
    public $action = '';
    public $errorMessage = '';
    public $duration = 4000;

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    protected $listeners = ['tagScanned' => 'handleTagScanned', 'errorOccurred' => 'errorOccurred', 'showNotification' => 'showNotification'];

    public function handleTagScanned($tags)
    {
        $this->scannedTags = $tags;
        $this->resetErrorMessage();
    }

    public function errorOccurred($message)
    {
        $this->errorMessage = $message;
        $this->dispatch('notify-swal', [
            'type' => 'error',
            'message' => $message,
        ]);
    }

    private function resetErrorMessage()
    {
        $this->errorMessage = '';
    }

    public function showNotification($data)
    {
        $this->dispatch('notify-swal', [
            'message' => $data,
        ]);
    }
}
