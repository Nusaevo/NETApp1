<?php

namespace App\Http\Livewire;

use Livewire\Component;

class TransactionNumber extends Component
{
    public $vehicle_type;
    public $transaction_id;

    public function generateBasicTransactionId()
    {
        // Logika untuk menghasilkan nomor transaksi berdasarkan vehicle_type
        $appCode = $this->getAppCode($this->vehicle_type);
        $this->transaction_id = $this->generateTransactionId($appCode, 'some_code');
    }

    public function generateTransactionIdWithTax()
    {
        // Logika untuk menghasilkan nomor transaksi dengan faktur pajak
        $appCode = $this->getAppCode($this->vehicle_type);
        $this->transaction_id = $this->generateTransactionId($appCode, 'some_code_with_tax');
    }

    private function getAppCode($vehicleType)
    {
        switch ($vehicleType) {
            case '0':
                return 'Motor';
            case '1':
                return 'Mobil';
            case '2':
                return 'Lain-lain';
            default:
                return 'Lain-lain';
        }
    }

    private function generateTransactionId($appCode, $code)
    {
        // Implementasi logika penomoran seperti yang telah dibahas sebelumnya
        // Contoh: menggabungkan appCode dan code untuk menghasilkan transactionId
        return $appCode . '-' . $code . '-' . uniqid();
    }

    public function render()
    {
        return view('livewire.transaction-number');
    }
}
