<?php

namespace App\Jobs;

use App\Models\TrdRetail1\Master\Material;
use App\Models\TrdRetail1\Config\AppAudit;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
class ProcessExcelUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $dataTable;
    protected $auditId;
    protected $sheetName;

    /**
     * Create a new job instance.
     *
     * @param array $dataTable
     * @param int $auditId
     */
    public function __construct($sheetName, $dataTable, $auditId)
    {
        $this->sheetName = $sheetName;
        $this->dataTable = $dataTable;
        $this->auditId = $auditId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $audit = AppAudit::find($this->auditId);

        // Start validation
        $audit->updateAuditTrail(10, 'Starting validation of Excel data.');
        $validationResult = Material::validateExcelUpload($this->dataTable, $audit);

        if (!$validationResult['success']) {
            $audit->updateAuditTrail(10, 'Validation failed: ' . implode('; ', $validationResult['errors']));
            return;
        }

        // Start processing if validation is successful
        $audit->updateAuditTrail(50, 'Validation successful. Starting data processing.');
        Material::processExcelUpload($validationResult['dataTable'], $audit);
    }

        /**
     * Handle a job failure.
     *
     * @param  Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        // Access the audit record and update the status or add error messages
        $audit = AppAudit::find($this->auditId);
        if ($audit) {
            $audit->updateAuditTrail(100, "Processing failed: " . $exception->getMessage());
        }
    }
}
