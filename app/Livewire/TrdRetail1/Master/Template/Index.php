<?php
namespace App\Livewire\TrdRetail1\Master\Template;

use App\Livewire\Component\BaseComponent;
use Livewire\WithFileUploads;
use App\Models\TrdRetail1\Config\ConfigAudit;
use App\Models\TrdRetail1\Master\Material;
use App\Jobs\ProcessExcelUploadJob;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;

class Index extends BaseComponent
{
    use WithFileUploads;

    public $file;
    public $auditId;

    // Public mapper for sheet names to model classes
    public $modelMap = [
        'materials' => Material::class,
        // Add other mappings as needed
        // 'Product' => Product::class,
        // 'Inventory' => Inventory::class,
    ];

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
        if (!$this->file) {
            $this->notify('error', 'No file uploaded. Please select a file to upload.');
            return;
        }

        try {
            // Load the spreadsheet
            $spreadsheet = IOFactory::load($this->file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();

            // Get the sheet name
            $sheetName = $sheet->getTitle();

            // Check if the sheet name has a corresponding model in the mapper
            if (!array_key_exists($sheetName, $this->modelMap)) {
                $this->notify('error', 'Invalid sheet name. Please ensure the sheet name is correct.');
                return;
            }

            // Get the model class based on the sheet name from the mapper
            $modelClass = $this->modelMap[$sheetName];

            // Convert sheet data to an array, skipping the first row (header)
            $dataTable = array_slice($sheet->toArray(), 1);

            // Create a new audit record
            $audit = ConfigAudit::create([
                'log_time' => now(),
                'action_code' => 'UPLOAD',
                'audit_trail' => "Starting upload process for sheet: $sheetName",
                'table_name' => $sheetName,
            ]);

            // Dispatch the job with the model and audit class
            ProcessExcelUploadJob::dispatch($sheetName, $dataTable, $audit->id, $modelClass, ConfigAudit::class);
        } catch (Exception $e) {
        }
        $this->dispatch('renderAuditTable');
    }
}
