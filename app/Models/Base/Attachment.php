<?php

namespace App\Models\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

class Attachment extends Model
{
    use HasFactory;

    protected $table = 'attachments';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $sessionAppCode = Session::get('app_code');
        $this->connection = $sessionAppCode;
    }

    protected $fillable = [
        'name',
        'path',
        'content_type',
        'extension',
        'descr',
        'attached_objectid',
        'attached_objecttype',
        'seq',
    ];

    public function getAllColumns()
    {
        return $this->fillable;
    }

    public function getAllColumnValues($attribute)
    {
        return $this->attributes[$attribute] ?? null;
    }

    public static function saveAttachmentByFileName($imageDataUrl, $objectId = null, $objectType, $filename)
    {
        $appCode = Session::get('app_code');
        $uploadPath = config('app.storage_path') . "/$appCode";

        $attachmentData = [
            'name' => $filename,
            'content_type' => 'image/jpeg',
            'extension' => 'jpg',
            'attached_objectid' => $objectId,
            'attached_objecttype' => $objectType,
        ];

        $attachmentsPath = "$uploadPath/$objectType/$objectId";

        if (!File::isDirectory($attachmentsPath)) {
            File::makeDirectory($attachmentsPath, 0777, true, true);
        }

        $imageData = substr($imageDataUrl, strpos($imageDataUrl, ',') + 1);
        $imageData = base64_decode($imageData);

        $existingAttachment = self::where('attached_objectid', $objectId)
            ->where('attached_objecttype', $objectType)
            ->where('name', $filename)
            ->first();

        if ($objectType == 'NetStorage') {
            $filePath = "$objectType/$filename";
        } else {
            $filePath = "$objectType/$objectId/$filename";
        }

        $fullPath = "$attachmentsPath/$filename";

        if ($existingAttachment) {
            $existingAttachment->path = $filePath;
            $existingAttachment->save();
            return $existingAttachment->path;
        } else {
            if (file_put_contents($fullPath, $imageData)) {
                $attachmentData['path'] = $filePath;
                $newAttachment = self::create($attachmentData);

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
            $appCode = Session::get('app_code');
            $uploadPath = config('app.storage_path') . "/$appCode";
            $path = "$uploadPath/{$attachment->path}";

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
            $appCode = Session::get('app_code');
            $uploadPath = config('app.storage_path') . "/$appCode";
            $path = "$uploadPath/{$attachment->path}";

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
        $appCode = Session::get('app_code');
        $uploadPath = config('app.storage_url') . "/$appCode";
        $fullPath = "$uploadPath/{$this->path}";
        return str_replace("/", '/', $fullPath);
    }

    public function getUrlAttribute()
    {
        $appCode = Session::get('app_code');
        $uploadPath = config('app.storage_url') . "/$appCode";
        $fullPath = "$uploadPath/{$this->path}";
        return str_replace("/", '/', $fullPath);
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
