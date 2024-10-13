<?php

namespace App\Livewire\TrdRetail1\Master\Template;

use App\Livewire\Component\BaseComponent;
use Livewire\WithFileUploads;
use App\Models\TrdRetail1\Master\Material;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;
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
        // Custom logic for pre-render
    }

    // Function to handle file upload and process the Excel file
    public function uploadExcel()
    {
        // Validate the uploaded file


        // Begin the database transaction
        DB::beginTransaction();
        try {
            // Read the Excel file into a collection
            $collection = Excel::toCollection(null, $this->file);

            // Assuming the first sheet in the Excel file
            $rows = $collection->first();
            dd($rows);
            // Iterate over the rows and insert them into the materials table
            foreach ($rows as $row) {
                // Check if the row is completely empty
                if (empty(array_filter($row->toArray()))) {
                    // If all values in the row are empty, stop processing
                    break;
                }

                // Check if 'kode_barang' is empty
                $kodeBarang = $row['kode_barang'] ?? null;

                // If 'kode_barang' is missing, generate one based on the last inserted material ID
                if (empty($kodeBarang)) {
                    $lastId = Material::max('id') + 1; // Get the last ID and increment by 1
                    $kodeBarang = 'MAT-' . str_pad($lastId, 6, '0', STR_PAD_LEFT); // Example format: MAT-000001
                }

                // Insert the material into the materials table
                $material = Material::create([
                    'code' => $kodeBarang,  // Use generated or existing 'kode_barang'
                    'name' => $row['kategori'] ?? null, // Assuming the 'name' is mapped to 'kategori'
                    'descr' => $row['jenis'] ?? null,
                    'brand' => $row['merk'] ?? null,
                    'uom' => $row['uom'] ?? null,
                    'img_file' => $row['gambar'] ?? null, // Assuming the file path or URL is in the 'gambar' column
                    'info' => $row['note'] ?? null,
                ]);

                // Save the image attachment for the material
                if (!empty($row['gambar'])) {
                    $this->saveAttachment($material->id, $row['gambar'], $kodeBarang);
                }
            }

            // Commit the transaction
            DB::commit();

            // Notify the user about the successful upload
            $this->notify('success', 'File uploaded and materials added successfully!');
        } catch (Exception $e) {
            // Rollback the transaction in case of any errors
            DB::rollback();
            $this->notify('error', 'Failed to upload the file: ' . $e->getMessage());
        }
    }

    // Function to handle saving of attachments (images, files)
    public function saveAttachment($materialId, $imagePath, $filename)
    {
        try {
            // Check if the image path is valid and exists
            if (!empty($imagePath)) {
                // Save the image path as an attachment for the material
                $filePath = Attachment::saveAttachmentByFileName($imagePath, $materialId, 'Material', $filename);

                // Check if the attachment was saved successfully
                if ($filePath === false) {
                    throw new Exception("Failed to save attachment for $filename.");
                }
            }
        } catch (Exception $e) {
            throw new Exception("Failed to save attachment: " . $e->getMessage());
        }
    }
}
