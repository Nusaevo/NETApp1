<?php
namespace App\Http\Livewire\Component;

use Livewire\Component;
use Ratchet\Client\Connector;
use React\EventLoop\Factory;
use React\Socket\Connector as ReactConnector;
use Exception;

class RfidScanner extends Component
{
    public $isScanning = false;
    public $scannedTags = [];
    public $errorMessage = '';
    private $wsUrl = 'ws://192.168.166.64:8081/RFID';
    private $loop;
    private $conn;

    public function render()
    {
        return view('livewire.component.rfid-scanner');
    }

    public function startScan()
    {
        $this->isScanning = true;
        $this->errorMessage = '';
        $this->scannedTags = [];
        $this->emitSelf('updateScanningState');

        try {
            $this->loop = Factory::create();
            $connector = new Connector($this->loop, new ReactConnector($this->loop));

            $connector($this->wsUrl)->then(function ($conn) {
                $this->conn = $conn;
                $conn->on('message', function ($msg) {
                    if (strpos($msg, 'Scanned Tags:') === 0 || $msg === 'No tag scanned') {
                        if (strpos($msg, 'Scanned Tags:') === 0) {
                            $this->scannedTags = explode(', ', substr($msg, 14));
                            $this->emit('tagScanned', $this->scannedTags);
                        } else {
                            $this->errorMessage = 'No tag scanned';
                            $this->emit('errorOccurred', $this->errorMessage);
                        }
                        $this->closeConnection();
                    } else {
                        $this->errorMessage = 'No tag scanned';
                        $this->emit('errorOccurred', $this->errorMessage);
                        $this->closeConnection();
                    }
                });

                $conn->on('close', function () {
                    $this->isScanning = false;
                    $this->emitSelf('updateScanningState');
                    $this->loop->stop();
                });

                $conn->on('error', function ($e) {
                    throw new Exception("Could not connect: {$e->getMessage()}");
                });

                $conn->send('start_scan');
            }, function ($e) {
                throw new Exception("Could not connect: {$e->getMessage()}");
            });

            // Set a timeout for 5 seconds
            $this->loop->addTimer(5, function () {
                if (empty($this->scannedTags)) {
                    $this->errorMessage = 'No tag scanned';
                    $this->emit('errorOccurred', $this->errorMessage);
                }
                $this->closeConnection();
            });

            $this->loop->run();
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            $this->emit('errorOccurred', $this->errorMessage);
            $this->isScanning = false;
            $this->emitSelf('updateScanningState');
        }
    }


    private function closeConnection()
    {
        if ($this->conn) {
            $this->conn->close();
            $this->conn = null;
        }
        $this->isScanning = false;
        $this->emitSelf('updateScanningState');
        if ($this->loop) {
            $this->loop->stop();
        }
    }

    protected $listeners = ['errorOccurred' => 'handleError', 'updateScanningState' => 'render'];

    public function handleError($message)
    {
        $this->errorMessage = $message;
        $this->isScanning = false;
        $this->emitSelf('updateScanningState');
    }
}

