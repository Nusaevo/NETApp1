<?php
namespace App\Livewire\TrdRetail1\Master\Template;

use App\Livewire\Component\BaseComponent;
use Livewire\WithFileUploads;
use App\Models\TrdRetail1\Config\AppAudit;
use App\Jobs\ProcessExcelUploadJob;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Auth;
use Exception;

class Index extends BaseComponent
{
    use WithFileUploads;

    public $file;
    public $auditId;

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
        // Validate file presence and type
        // $this->validate([
        //     'file' => 'required|file|mimes:xlsx,xls|max:10240',
        // ]);

        // Check if file is still null
        if (!$this->file) {
            $this->notify('error', 'No file uploaded. Please select a file to upload.');
            return;
        }

        try {
            // Load the spreadsheet only if file exists
            $spreadsheet = IOFactory::load($this->file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();

            // Get the sheet name
            $sheetName = $sheet->getTitle();

            // Convert sheet data to an array and skip the first row (header)
            $dataTable = array_slice($sheet->toArray(), 1);

            // Create a new audit record
            $audit = AppAudit::create([
                'key_code' => 'excel_upload',
                'log_time' => now(),
                'action_code' => 'UPLOAD',
                'audit_trail' => "Starting upload process for sheet: $sheetName",
                'table_name' => 'materials',
            ]);

            // Dispatch the job for asynchronous processing
            ProcessExcelUploadJob::dispatch($sheetName, $dataTable, $audit->id);
            $this->dispatch('renderAuditTable');
            $this->notify('success', 'File uploaded. Processing started. Check progress in the audit log.');
        } catch (Exception $e) {
            $this->notify('error', 'An unexpected error occurred: ' . $e->getMessage());
        }

    }


}
