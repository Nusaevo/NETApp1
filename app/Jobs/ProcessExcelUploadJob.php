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

    public function __construct($sheetName, $dataTable, $auditId, $modelClass, $auditClass)
    {
        $this->sheetName = $sheetName;
        $this->dataTable = $dataTable;
        $this->auditId = $auditId;
        $this->modelClass = $modelClass;
        $this->auditClass = $auditClass;
    }

    public function handle()
    {
        $audit = $this->auditClass::find($this->auditId);

        try {
            $audit->updateAuditTrail(10, 'Starting validation of Excel data.', Status::IN_PROGRESS);

            $validationResult = $this->modelClass::validateExcelUpload($this->dataTable, $audit);

            if (!$validationResult['success']) {
                return;
            }

            $audit->updateAuditTrail(50, 'Data validation successful. Processing...', Status::IN_PROGRESS );
            $this->modelClass::processExcelUpload($validationResult['dataTable'], $audit);
        } catch (Throwable $e) {
            $this->failed($e);
        }
    }

    public function failed(Throwable $exception)
    {
        $audit = $this->auditClass::find($this->auditId);
        if ($audit) {
            $audit->updateAuditTrail(100, 'Processing failed: ' . $exception->getMessage() . '. Mohon download hasil untuk cek error.',Status::ERROR );
        }
    }
}
