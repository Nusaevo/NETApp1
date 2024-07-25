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
        return view('livewire.component.rfid-scanner');
    }

    protected $listeners = ['tagScanned' => 'handleTagScanned', 'errorOccurred' => 'handleError', 'notify-alert' => 'showNotification'];

    public function handleTagScanned($tags)
    {
        $this->scannedTags = $tags;
        $this->resetErrorMessage();
    }

    public function handleError($message)
    {
        $this->errorMessage = $message;
         $this->dispatch('alert', [
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
         $this->dispatch('alert', $data);
    }
}
