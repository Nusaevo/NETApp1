<?php

namespace App\Livewire\TrdTire1\Inventory\MonthlyInventory;

use App\Livewire\Component\BaseComponent;
use Illuminate\Support\Facades\{DB, Session};
use App\Services\TrdTire1\InventoryService;
use Carbon\Carbon;

class Index extends BaseComponent
{
    public $period = ''; // Format: YYYYMM (contoh: 202510)
    public $periodLabel = ''; // Label periode untuk ditampilkan
    public $nextPeriod = ''; // Periode berikutnya
    public $nextPeriodLabel = ''; // Label periode berikutnya
    public $dateFrom = ''; // Tanggal mulai periode
    public $dateTo = ''; // Tanggal akhir periode
    public $isProcessing = false;
    public $processMessage = '';

    protected function onPreRender()
    {
        // Set default periode ke bulan ini jika belum diisi
        if (empty($this->period)) {
            $this->period = Carbon::now()->format('Ym');
            $this->updatePeriodInfo();
        }
    }

    public function updatedPeriod()
    {
        $this->updatePeriodInfo();
    }

    protected function updatePeriodInfo()
    {
        if (empty($this->period) || strlen($this->period) !== 6) {
            $this->periodLabel = '';
            $this->nextPeriod = '';
            $this->nextPeriodLabel = '';
            $this->dateFrom = '';
            $this->dateTo = '';
            return;
        }

        try {
            $year = (int) substr($this->period, 0, 4);
            $month = (int) substr($this->period, 4, 2);

            // Validasi bulan
            if ($month < 1 || $month > 12) {
                $this->periodLabel = 'Periode tidak valid';
                return;
            }

            $dateFrom = Carbon::create($year, $month, 1);
            $dateTo = $dateFrom->copy()->endOfMonth();
            $nextPeriodDate = $dateFrom->copy()->addMonth();

            $this->dateFrom = $dateFrom->format('Y-m-d');
            $this->dateTo = $dateTo->format('Y-m-d');
            $this->periodLabel = $dateFrom->format('F Y'); // Contoh: October 2025
            $this->nextPeriod = $nextPeriodDate->format('Ym');
            $this->nextPeriodLabel = $nextPeriodDate->format('F Y');
        } catch (\Exception $e) {
            $this->periodLabel = 'Periode tidak valid';
        }
    }

    public function processPeriod()
    {
        $this->resetErrorBag();
        $this->isProcessing = true;
        $this->processMessage = '';

        // Validasi periode
        if (empty($this->period) || strlen($this->period) !== 6) {
            $this->dispatch('warning', __('Periode harus dalam format YYYYMM (contoh: 202510)'));
            $this->isProcessing = false;
            return;
        }

        try {
            // Panggil service untuk proses monthly inventory
            InventoryService::processMonthlyInventory(
                Session::get('app_code'),
                $this->period
            );

            $this->processMessage = "Proses selesai untuk periode {$this->periodLabel}";
            $this->dispatch('success', __('Proses inventory bulanan berhasil dilakukan'));

        } catch (\Exception $e) {
            $this->processMessage = 'Error: ' . $e->getMessage();
            $this->dispatch('error', __('Terjadi kesalahan: ') . $e->getMessage());
        } finally {
            $this->isProcessing = false;
        }
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }
}
