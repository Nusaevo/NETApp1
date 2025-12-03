<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\{DB, Log};
use App\Services\TrdTire1\InventoryService;
use App\Enums\Constant;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        // Process Monthly Inventory - Berjalan setiap akhir bulan jam 23:59
        $schedule->call(function () {
            try {
                $appCodes = ['TrdTire1'];

                foreach ($appCodes as $appCode) {
                    try {
                        // Proses untuk bulan sebelumnya (karena dijalankan di akhir bulan)
                        InventoryService::processMonthlyInventory($appCode);
                        Log::info("Monthly inventory processed successfully for {$appCode}");
                    } catch (\Exception $e) {
                        Log::error("Failed to process monthly inventory for {$appCode}: " . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                Log::error("Scheduler error: " . $e->getMessage());
            }
        })->when(function () {
            // Jalankan hanya di hari terakhir bulan
            return Carbon::now()->isLastOfMonth();
        })
        ->dailyAt('23:59')
        ->timezone('Asia/Jakarta')
        ->name('process-monthly-inventory')
        ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
