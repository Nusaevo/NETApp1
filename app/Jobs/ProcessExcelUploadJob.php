<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use Livewire\Livewire;

class ProcessExcelUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $dataTable;
    protected $auditId;
    protected $sheetName;
    protected $modelClass;
    protected $auditClass;

    /**
     * Create a new job instance.
     *
     * @param string $sheetName
     * @param array $dataTable
     * @param int $auditId
     * @param string $modelClass
     * @param string $auditClass
     */
    public function __construct($sheetName, $dataTable, $auditId, $modelClass, $auditClass)
    {
        $this->sheetName = $sheetName;
        $this->dataTable = $dataTable;
        $this->auditId = $auditId;
        $this->modelClass = $modelClass;
        $this->auditClass = $auditClass;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $audit = $this->auditClass::find($this->auditId);

        // Start validation
        $audit->updateAuditTrail(10, 'Starting validation of Excel data.');

        $validationResult = $this->modelClass::validateExcelUpload($this->dataTable, $audit);

        if (!$validationResult['success']) {
            $audit->updateAuditTrail(0, 'Validation failed: ' . implode('; ', $validationResult['errors']));
            return;
        }

        // Start processing if validation is successful
        $audit->updateAuditTrail(50, 'Validation successful. Starting data processing.');
        $this->modelClass::processExcelUpload($validationResult['dataTable'], $audit);

        // Finalize with 100% progress and success status
        $audit->updateAuditTrail(100, 'Data processing completed successfully.');
    }

    /**
     * Handle a job failure.
     *
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        // Access the audit record and update the status or add error messages
        $audit = $this->auditClass::find($this->auditId);
        if ($audit) {
            $audit->updateAuditTrail(0, "Processing failed: " . $exception->getMessage());
        }
    }
}
