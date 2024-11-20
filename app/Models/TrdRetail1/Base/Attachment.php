<?php

namespace App\Models\TrdRetail1\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use App\Enums\Constant;
use Illuminate\Support\Facades\Session;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Attachment extends Model
{
    use HasFactory;

    protected $table = 'attachments';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $sessionAppCode = Session::get('app_code');
        $this->connection = $sessionAppCode ?: Session::get('app_code');
    }

    protected $fillable = ['name', 'path', 'content_type', 'extension', 'descr', 'attached_objectid', 'attached_objecttype', 'seq'];

    public function getAllColumns()
    {
        return $this->fillable;
    }

    public function getAllColumnValues($attribute)
    {
        if (array_key_exists($attribute, $this->attributes)) {
            return $this->attributes[$attribute];
        }
        return null;
    }

    public static function saveAttachmentByFileName($imageDataUrl, $objectId = null, $objectType, $filename)
    {
        // Get the upload path from .env
        $uploadPath = config('app.storage_path') . '/' . Session::get('app_code');
        // Create attachment data
        $attachmentData = [
            'name' => $filename,
            'content_type' => 'image/jpeg',
            'extension' => 'jpg',
            'attached_objectid' => $objectId,
            'attached_objecttype' => $objectType,
        ];

        // Create attachment directory if it doesn't exist
        $attachmentsPath = $uploadPath . '/' . $objectType . '/' . $objectId;
        if (!File::isDirectory($attachmentsPath)) {
            File::makeDirectory($attachmentsPath, 0777, true, true);
        }

        // Decode image data
        $imageData = substr($imageDataUrl, strpos($imageDataUrl, ',') + 1);
        $imageData = base64_decode($imageData);

        // Check if attachment with the same filename already exists
        $existingAttachment = self::where('attached_objectid', $objectId)->where('attached_objecttype', $objectType)->where('name', $filename)->first();
        if ($objectType == 'NetStorage') {
            $filePath = $objectType . '/' . $filename;
        } else {
            $filePath = $objectType . '/' . $objectId . '/' . $filename;
        }
        $fullPath = $attachmentsPath . '/' . $filename;

        if ($existingAttachment) {
            // Update existing attachment
            $existingAttachment->path = $filePath;
            $existingAttachment->save();
            return $existingAttachment->path;
        } else {
            // Save new file and attachment record
            if (file_put_contents($fullPath, $imageData)) {
                $attachmentData['path'] = $filePath;
                $newAttachment = self::create($attachmentData);

                // Update attached_objectid if it was null
                if (is_null($objectId)) {
                    $newAttachment->attached_objectid = $newAttachment->id;
                    $newAttachment->save();
                }
                return $newAttachment->path;
            }
        }

        return false;
    }

    public static function uploadExcelAttachment($dataTable, $filename, $objectId, $objectType, $sheetName)
    {
        // Get the upload path from .env
        $storageBasePath = rtrim(config('app.storage_path'), '/');
        $appCode = Session::get('app_code');

        // Normalize upload path
        $uploadPath = $storageBasePath . '/' . $appCode;

        // Create attachment directory if it doesn't exist
        $attachmentsPath = $uploadPath . '/' . $objectType . '/' . $objectId;
        if (!File::isDirectory($attachmentsPath)) {
            if (!File::makeDirectory($attachmentsPath, 0777, true)) {
                throw new \Exception("Failed to create directory at $attachmentsPath");
            }
        }

        // Define file path
        $filePath = $objectType . '/' . $objectId . '/' . $filename;
        $fullPath = $attachmentsPath . '/' . $filename;

        try {
            // Create a new Spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($sheetName);

            // Apply headers and data
            foreach ($dataTable as $rowIndex => $row) {
                foreach ($row as $colIndex => $cell) {
                    $columnLetter = self::getExcelColumnName($colIndex + 1);
                    $sheet->setCellValue("{$columnLetter}" . ($rowIndex + 1), $cell);

                    // Apply styling for headers
                    if ($rowIndex === 0) {
                        // Header row
                        $sheet->getStyle("{$columnLetter}1")->applyFromArray([
                            'font' => ['bold' => true],
                            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                        ]);

                        // If the header contains an asterisk (*), color it red
                        if (strpos($cell, '*') !== false) {
                            $sheet->getStyle("{$columnLetter}1")->applyFromArray([
                                'font' => ['color' => ['rgb' => 'FF0000']],
                            ]);
                        }
                    }
                }
            }

            // Adjust column width dynamically
            $headerCount = count($dataTable[0]);
            for ($i = 1; $i <= $headerCount; $i++) {
                $columnLetter = self::getExcelColumnName($i);
                $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
            }

            // Write the file to the defined path
            $writer = new Xlsx($spreadsheet);
            $writer->save($fullPath);

            // Save the attachment
            $existingAttachment = self::where('attached_objectid', $objectId)->where('attached_objecttype', $objectType)->where('name', $filename)->first();

            if ($existingAttachment) {
                $existingAttachment->update([
                    'path' => $filePath,
                    'content_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]);
                return $existingAttachment;
            } else {
                return self::create([
                    'name' => $filename,
                    'content_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'extension' => 'xlsx',
                    'path' => $filePath,
                    'attached_objectid' => $objectId,
                    'attached_objecttype' => $objectType,
                ]);
            }
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate and save Excel file: ' . $e->getMessage());
        }
    }

    /**
     * Convert a column index to an Excel column name (1 => A, 2 => B, etc.).
     *
     * @param int $index
     * @return string
     */
    private static function getExcelColumnName($index)
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(($index % 26) + 65) . $letter;
            $index = (int) ($index / 26);
        }
        return $letter;
    }

    public static function deleteAttachmentByFilename($objectId, $objectType, $filename)
    {
        $attachment = self::where('attached_objectid', $objectId)->where('attached_objecttype', $objectType)->where('name', $filename)->first();

        if ($attachment) {
            $uploadPath = config('app.storage_path') . '/' . Session::get('app_code');
            $path = $uploadPath . '/' . $attachment->path;

            if (File::exists($path)) {
                File::delete($path);
            }
            $attachment->delete();

            return true;
        }

        return false;
    }

    public static function deleteAttachmentById($imageId)
    {
        $attachment = self::find($imageId);

        if ($attachment) {
            $uploadPath = config('app.storage_path') . '/' . Session::get('app_code');
            $path = $uploadPath . '/' . $attachment->path;
            if (File::exists($path)) {
                File::delete($path);
            }
            $attachment->delete();

            return true;
        }

        return false;
    }

    public function getUrl()
    {
        $uploadPath = config('app.storage_url') . '/' . Session::get('app_code');
        $fullPath = $uploadPath . '/' . $this->path;
        $urlPath = str_replace('/', '/', $fullPath);
        return $urlPath;
    }

    public function getUrlAttribute()
    {
        $uploadPath = config('app.storage_url') . '/' . Session::get('app_code');
        $fullPath = $uploadPath . '/' . $this->path;
        return str_replace('/', '/', $fullPath);
    }

    protected static function reSortSequences($objectId, $objectType)
    {
        $attachments = self::where('attached_objectid', $objectId)->where('attached_objecttype', $objectType)->orderBy('id')->get();

        $seq = 1;
        foreach ($attachments as $attachment) {
            $attachment->seq = $seq;
            $attachment->save();
            $seq++;
        }
    }
}
