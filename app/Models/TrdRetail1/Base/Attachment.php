<?php

namespace App\Models\TrdRetail1\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use App\Enums\Constant;
use Illuminate\Support\Facades\Session;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Util\GenericExcelExport;

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

    /**
     * Upload Excel Attachment using a given template configuration.
     *
     * @param array $templateConfig Template configuration (e.g., from getCreateTemplateConfig)
     * @param string $objectId Identifier for the object (e.g., audit ID)
     * @param string $objectType Type of object (e.g., 'ConfigAudit')
     */
    public static function uploadExcelAttachment(array $templateConfig, string $objectId, string $objectType)
    {
        // ✅ 1. Tentukan Path Penyimpanan
        $storageBasePath = rtrim(config('app.storage_path'), '/');
        $appCode = Session::get('app_code');

        $uploadPath = $storageBasePath . '/' . $appCode;
        $attachmentsPath = "{$uploadPath}/{$objectType}/{$objectId}";

        if (!File::isDirectory($attachmentsPath)) {
            if (!File::makeDirectory($attachmentsPath, 0777, true)) {
                throw new \Exception("Failed to create directory at $attachmentsPath");
            }
        }

        // ✅ 2. Tentukan Nama File dan Path Lengkap
        $filename = $templateConfig['name'] . '_' . now()->format('Y-m-d_His') . '.xlsx';
        $filePath = "{$objectType}/{$objectId}/{$filename}";
        $fullPath = "{$attachmentsPath}/{$filename}";

        try {
            // ✅ 3. Generate dan Simpan Menggunakan GenericExcelExport
            $export = new GenericExcelExport([$templateConfig], $filename);
            $export->upload($fullPath);

            // ✅ 4. Simpan Metadata Attachment di Database
            Attachment::updateOrCreate(
                [
                    'attached_objectid' => $objectId,
                    'attached_objecttype' => $objectType,
                    'name' => $filename,
                ],
                [
                    'path' => $filePath,
                    'content_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'extension' => 'xlsx',
                ],
            );
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate and save Excel file: ' . $e->getMessage());
        }
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
