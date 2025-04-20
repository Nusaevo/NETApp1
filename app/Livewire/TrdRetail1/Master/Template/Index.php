<?php

namespace App\Livewire\TrdRetail1\Master\Template;

use App\Livewire\Component\BaseComponent;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use Livewire\Livewire;
use App\Models\TrdRetail1\Master\Material;
use App\Models\TrdRetail1\Config\ConfigAudit;
use App\Jobs\ProcessExcelUploadJob;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Enums\Status;
use Exception;


class Index extends BaseComponent
{
    use WithFileUploads;

    public $file;
    public $auditId;
    public $isUploading = false;

    // Public mapper for sheet names to model classes
    public $modelMap = [
        'Material_Create_Template' => [
            'model' => Material::class,
            'param' => 'Create',
        ],
        'Material_Update_Template' => [
            'model' => Material::class,
            'param' => 'Update',
        ],
    ];

    protected $listeners = ['uploadExcel' => 'handleUploadExcel'];

    protected function onPreRender()
    {
    }

    public function render()
    {
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute);
    }

    public function handleUploadExcel($fileData, $fileName)
    {
        try {
            // Decode the base64 file data
            $fileContent = base64_decode(preg_replace('#^data:application/vnd\..+;base64,#i', '', $fileData));

            // Save file temporarily
            $tempPath = storage_path('app/temp_' . uniqid() . '.xlsx');
            file_put_contents($tempPath, $fileContent);

            // Load spreadsheet
            $spreadsheet = IOFactory::load($tempPath);
            $sheet = $spreadsheet->getActiveSheet();
            $sheetName = $sheet->getTitle();

            // Validate sheet name and fetch model and param
            if (!isset($this->modelMap[$sheetName])) {
                $availableSheets = implode(', ', array_keys($this->modelMap));
                $this->dispatch('error', "Nama sheet template '{$sheetName}' tidak ditemukan. Harap gunakan salah satu dari: {$availableSheets}.");
                return;
            }

            $modelData = $this->modelMap[$sheetName];
            $modelClass = $modelData['model'];
            $param = $modelData['param'];

            // Log audit
            $audit = ConfigAudit::create([
                'log_time' => now(),
                'action_code' => 'UPLOAD',
                'audit_trail' => "Starting upload process for sheet: $sheetName",
                'table_name' => $sheetName,
                'status_code' => Status::IN_PROGRESS,
            ]);
            // Dispatch processing job with param parameter
            ProcessExcelUploadJob::dispatch($sheetName, $sheet->toArray(), $audit->id, $modelClass, ConfigAudit::class, $param);

        } catch (\Exception $e) {
            $this->dispatch('error', $e->getMessage());
        }

        $this->dispatch('excelUploadComplete');
        $this->dispatch('renderAuditTable');
    }


    public function pollRefresh()
    {
        // Check if there are any incomplete jobs in the database
        $incompleteJobs = ConfigAudit::where('progress', '<', 100)
            ->where('action_code', 'UPLOAD')
            ->exists();
        if ($incompleteJobs) {
            $this->dispatch('renderAuditTable');
        }
    }
}
