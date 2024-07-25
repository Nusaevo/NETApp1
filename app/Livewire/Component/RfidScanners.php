<?php
namespace App\Livewire\Component;

use Livewire\Component;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;

class RfidScanners extends Component
{
    public $isScanning = false;
    public $scannedTags = [];
    public $errorMessage = '';
    private $wsUrl = 'ws://localhost:8081/RFID';
    private $conn;
    private $loop;
    private $timer;

    public function render()
    {
        return view('livewire.component.rfid-scanner');
    }

    public function startScan()
    {
        $this->isScanning = true;
        $this->errorMessage = '';
        $this->dispatch('scanStatusChanged', $this->isScanning);

        $this->loop = Factory::create();
        $connector = new Connector($this->loop);

        $connector($this->wsUrl)->then(function ($conn) {
            $this->conn = $conn;
            $conn->on('message', function ($msg) {
                $barcodes = explode(', ', (string) $msg);
                foreach ($barcodes as $barcode) {
                    if ($barcode !== "No tag scanned" && $barcode !== "Unknown command" && $barcode !== "Failed to open USB connection" && $barcode !== "Scan stopped") {
                        $this->scannedTags[] = $barcode;
                        $this->dispatch('tagScanned', $barcode);
                    } else {
                        $this->dispatch('errorOccurred', $barcode);
                    }
                }
            });

            $conn->send('start_scan');

            // Set up a timer to check the scanning status periodically
            $this->timer = $this->loop->addPeriodicTimer(0.5, function () {
                if (!$this->isScanning) {
                    $this->stopScan();
                }
            });
        }, function ($e) {
            $this->dispatch('errorOccurred', "Could not connect: {$e->getMessage()}");
            $this->loop->stop();
        });

        $this->loop->run();
    }

    public function stopScan()
    {
        if ($this->conn) {
            $this->conn->send('stop_scan');
            $this->conn->close();
            $this->conn = null;
        }
        $this->isScanning = false;
        $this->dispatch('scanStatusChanged', $this->isScanning);
        if ($this->loop) {
            $this->loop->cancelTimer($this->timer);
            $this->loop->stop();
        }
    }

    protected $listeners = ['errorOccurred' => 'handleError', 'scanStopped' => 'handleScanStopped', 'scanStatusChanged' => 'updateScanStatus'];

    public function handleError($message)
    {
        $this->errorMessage = $message;
        $this->isScanning = false;
    }

    public function handleScanStopped()
    {
        $this->isScanning = false;
    }

    public function updateScanStatus($status)
    {
        $this->isScanning = $status;
    }
}
