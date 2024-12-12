<?php

namespace App\Jobs;

use App\Models\TrdRetail1\Config\ConfigAudit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use App\Enums\Status;

class ProcessExcelUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $dataTable;
    protected $auditId;
    protected $sheetName;
    protected $modelClass;
    protected $auditClass;
    protected $param;

    /**
     * Create a new job instance.
     *
     * @param string $sheetName
     * @param array $dataTable
     * @param int $auditId
     * @param string $modelClass
     * @param string $auditClass
     * @param array $param
     */
    public function __construct($sheetName, $dataTable, $auditId, $modelClass, $auditClass, $param)
    {
        $this->sheetName = $sheetName;
        $this->dataTable = $dataTable;
        $this->auditId = $auditId;
        $this->modelClass = $modelClass;
        $this->auditClass = $auditClass;
        $this->param = $param; // Store additional parameters
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $audit = $this->auditClass::find($this->auditId);

        try {
            $audit->updateAuditTrail(10, 'Starting validation of Excel data.', Status::IN_PROGRESS);
            $validationResult = $this->modelClass::validateExcelUpload($this->dataTable, $audit, $this->param);

            if (!$validationResult['success']) {
                return;
            }

            $audit->updateAuditTrail(50, 'Data validation successful. Processing...', Status::IN_PROGRESS);
            $this->modelClass::processExcelUpload($validationResult['dataTable'], $audit, $this->param);
        } catch (Throwable $e) {
            $this->failed($e);
        }
    }

    /**
     * Handle a job failure.
     *
     * @param Throwable $exception
     * @return void
     */
    public function failed(Throwable $exception)
    {
        $audit = $this->auditClass::find($this->auditId);
        if ($audit) {
            $audit->updateAuditTrail(
                100,
                'Processing failed: ' . $exception->getMessage() . '. Mohon download hasil untuk cek error.',
                Status::ERROR
            );
        }
    }
}
