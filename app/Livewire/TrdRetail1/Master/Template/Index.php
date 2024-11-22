<?php
namespace App\Livewire\TrdRetail1\Master\Template;

use App\Livewire\Component\BaseComponent;
use Livewire\WithFileUploads;
use App\Models\TrdRetail1\Config\ConfigAudit;
use App\Models\TrdRetail1\Master\Material;
use App\Jobs\ProcessExcelUploadJob;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;
use Illuminate\Support\Str;
use Livewire\Livewire;
use App\Enums\Status;

class Index extends BaseComponent
{
    use WithFileUploads;

    public $file;
    public $auditId;
    public $isUploading = false;

    // Public mapper for sheet names to model classes
    public $modelMap = [
        'Material Template' => Material::class,
        // Add other mappings as needed
        // 'Product' => Product::class,
        // 'Inventory' => Inventory::class,
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


    public function uploadExcel()
    {
        // Validasi file
        $this->validate([
            'file' => 'required|mimes:xlsx,xls|max:10240',
        ]);

        try {
            // Simpan file sementara
            $filePath = $this->file->store('temp');

            // Load file Excel menggunakan PhpSpreadsheet
            $spreadsheet = IOFactory::load(storage_path('app/' . $filePath));
            $sheet = $spreadsheet->getActiveSheet();
            $sheetName = $sheet->getTitle();

            // Validate sheet name
            if (!array_key_exists($sheetName, $this->modelMap)) {
                $availableSheets = implode(', ', array_keys($this->modelMap));
                $this->notify('error', "Sheet '{$sheetName}' not found. Use one of the following: {$availableSheets}.");
                return;
            }

            // Map sheet name to model class
            $modelClass = $this->modelMap[$sheetName];

            // Log audit
            $audit = ConfigAudit::create([
                'log_time' => now(),
                'action_code' => 'UPLOAD',
                'audit_trail' => "Starting upload process for sheet: $sheetName",
                'table_name' => $sheetName,
                'status_code' => Status::IN_PROGRESS,
            ]);

            // Dispatch job for processing
            ProcessExcelUploadJob::dispatch($sheetName, $sheet->toArray(), $audit->id, $modelClass, ConfigAudit::class);

            session()->flash('message', 'File uploaded successfully.');

        } catch (\Exception $e) {
            session()->flash('error', 'Failed to process the Excel file: ' . $e->getMessage());
        }
        $this->file = null;
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
