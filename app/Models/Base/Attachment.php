<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class Attachment extends Model
{
    use HasFactory;

    protected $table = 'attachments';

    protected $fillable = [
        'name',
        'path',
        'content_type',
        'extension',
        'descr',
        'attached_objectid',
        'attached_objecttype',
        'seq', // Add seq column
    ];

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
        $uploadPath = config('upload_path');
        // Normalize the upload path to use the correct directory separator
        $normalizedUploadPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uploadPath);

        // Create attachment data
        $attachmentData = [
            'name' => $filename,
            'content_type' => 'image/jpeg',
            'extension' => 'jpg',
            'attached_objectid' => $objectId,
            'attached_objecttype' => $objectType,
        ];

        // Create attachment directory if it doesn't exist
        $attachmentsPath = $normalizedUploadPath . DIRECTORY_SEPARATOR . $objectType . DIRECTORY_SEPARATOR . $objectId;
        if (!File::isDirectory($attachmentsPath)) {
            File::makeDirectory($attachmentsPath, 0777, true, true);
        }

        // Decode image data
        $imageData = substr($imageDataUrl, strpos($imageDataUrl, ',') + 1);
        $imageData = base64_decode($imageData);

        // Check if attachment with the same filename already exists
        $existingAttachment = self::where('attached_objectid', $objectId)
                                    ->where('attached_objecttype', $objectType)
                                    ->where('name', $filename)
                                    ->first();
        $filePath = $objectType . DIRECTORY_SEPARATOR . $objectId . DIRECTORY_SEPARATOR . $filename;
        $fullPath = $attachmentsPath . DIRECTORY_SEPARATOR . $filename;

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

    public static function deleteAttachmentByFilename($objectId, $objectType, $filename)
    {
        $attachment = self::where('attached_objectid', $objectId)
            ->where('attached_objecttype', $objectType)
            ->where('name', $filename)
            ->first();

        if ($attachment) {
            $uploadPath = config('upload_path');
            $normalizedUploadPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uploadPath);
            $path = $normalizedUploadPath . DIRECTORY_SEPARATOR . $attachment->path;

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
        $uploadPath = config('upload_path');
        $relativePath = str_replace(public_path() . DIRECTORY_SEPARATOR, '', storage_path($uploadPath));
        $urlPath = str_replace(['\\'], '/', $relativePath . '/' . $this->path);
        return asset($urlPath);
    }

    protected static function reSortSequences($objectId, $objectType)
    {
        $attachments = self::where('attached_objectid', $objectId)
            ->where('attached_objecttype', $objectType)
            ->orderBy('id')
            ->get();

        $seq = 1;
        foreach ($attachments as $attachment) {
            $attachment->seq = $seq;
            $attachment->save();
            $seq++;
        }
    }
}
