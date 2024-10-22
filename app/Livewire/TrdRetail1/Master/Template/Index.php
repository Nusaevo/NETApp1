<?php
namespace App\Livewire\TrdRetail1\Master\Template;

use App\Livewire\Component\BaseComponent;
use Livewire\WithFileUploads;
use App\Models\TrdRetail1\Master\Material;
use App\Models\TrdRetail1\Base\Attachment;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Exception;

class Index extends BaseComponent
{
    use WithFileUploads;

    public $file; // Excel file upload

    public function render()
    {
        return view('livewire.trd-retail1.master.template.index');
    }
    protected function onPreRender()
    {

    }
    // Function to handle file upload and process the Excel file, including extracting images
    public function uploadExcel()
    {
        DB::beginTransaction();
        try {
            // Load the Excel file using PhpSpreadsheet
            $spreadsheet = IOFactory::load($this->file->getRealPath());
            $sheet = $spreadsheet->getActiveSheet();

            // Get all rows of data
            $rows = $sheet->toArray();
            foreach ($rows as $rowIndex => $row) {
                if ($rowIndex < 1) {
                    continue; // Skip the header row
                }
                if (empty(array_filter($row))) {
                    break; // Stop processing if the row is completely empty
                }

                // Retrieve relevant columns (adjust based on your Excel structure)
                $kodeBarang = $row[0]; // Assuming 'Kode Barang' is in the first column
                $kategori = $row[1] ?? "test";
                $jenis = $row[2];
                $merk = $row[3];
                $uom = $row[4];
                $note = $row[5];

                // Generate 'Kode Barang' if missing
                if (empty($kodeBarang)) {
                    $lastId = Material::max('id') + 1;
                    $kodeBarang = 'MAT-' . str_pad($lastId, 6, '0', STR_PAD_LEFT);
                }

                // Insert the material data into the database
                $material = Material::create([
                    'code' => $kodeBarang,
                    'name' => $kategori ?? null,
                    'descr' => $jenis ?? null,
                    'brand' => $merk ?? null,
                    'uom' => $uom ?? null,
                    'info' => $note ?? null,
                ]);

                // Process images embedded in the Excel file
                $this->processExcelImages($sheet, $rowIndex, $material->id, $kodeBarang);
            }

            DB::commit();
            $this->notify('success', 'File uploaded and materials added successfully!');
        } catch (Exception $e) {
            DB::rollback();
            $this->notify('error', 'Failed to upload the file: ' . $e->getMessage());
        }
    }

    // Process and save images from the Excel sheet
    public function processExcelImages($sheet, $rowIndex, $materialId, $kodeBarang)
    {
        foreach ($sheet->getDrawingCollection() as $drawing) {
            if ($drawing->getCoordinates()) {
                $cellCoordinates = $drawing->getCoordinates();
                $currentRow = $sheet->getCell($cellCoordinates)->getRow();
                if ($currentRow == $rowIndex + 1) {
                    if ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing) {
                        // Convert image resource to binary data
                        ob_start();
                        switch ($drawing->getMimeType()) {
                            case \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::MIMETYPE_PNG:
                                imagepng($drawing->getImageResource());
                                $imageExtension = 'png';
                                break;
                            case \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::MIMETYPE_JPEG:
                                imagejpeg($drawing->getImageResource());
                                $imageExtension = 'jpg';
                                break;
                            case \PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing::MIMETYPE_GIF:
                                imagegif($drawing->getImageResource());
                                $imageExtension = 'gif';
                                break;
                            default:
                                throw new Exception("Unsupported image type.");
                        }
                        $imageBytes = ob_get_contents();
                        ob_end_clean();

                    } elseif ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Drawing) {
                        // Handle external images
                        $imagePath = $drawing->getPath();
                        $imageExtension = pathinfo($imagePath, PATHINFO_EXTENSION);
                        $imageBytes = file_get_contents($imagePath);
                    }

                    // Generate image name and save the attachment
                    $imageName = $kodeBarang . '.' . $imageExtension;
                    $this->saveAttachment($materialId, $imageBytes, $imageName, $kodeBarang);
                }
            }
        }
    }

    // Save the image data to a file
    public function saveAttachment($materialId, $imageBytes, $filename, $kodeBarang)
    {
        try {
            $directoryPath = 'storage/attachments/';
            if (!file_exists($directoryPath)) {
                mkdir($directoryPath, 0755, true);
            }

            $fullFilePath = $directoryPath . $filename;
            $file = fopen($fullFilePath, 'wb');
            fwrite($file, $imageBytes);
            fclose($file);

            $filePath = Attachment::saveAttachmentByFileName($fullFilePath, $materialId, 'Material', $filename);
            if ($filePath === false) {
                throw new Exception("Failed to save attachment for $filename.");
            }
        } catch (Exception $e) {
            throw new Exception("Failed to save attachment: " . $e->getMessage());
        }
    }
}
