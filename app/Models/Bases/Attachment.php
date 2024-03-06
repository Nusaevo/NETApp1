<?php

namespace App\Models\Bases;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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

    public static function saveAttachmentByFileName($imageDataUrl, $objectId, $objectType, $filename)
    {
        $attachmentsPath = public_path('storage/attachments/' . $objectId);
        if (!File::isDirectory($attachmentsPath)) {
            File::makeDirectory($attachmentsPath, 0777, true, true);
        }
        $imageData = substr($imageDataUrl, strpos($imageDataUrl, ',') + 1);
        $imageData = base64_decode($imageData);
    
        // Check if attachment with the same filename already exists
        $existingAttachment = self::where('attached_objectid', $objectId)
                                    ->where('attached_objecttype', $objectType)
                                    ->where('name', $filename)
                                    ->first();
    
        if ($existingAttachment) {
            // Update existing attachment
            $existingAttachment->path = $filePath;
            $existingAttachment->content_type = 'image/jpeg';
            $existingAttachment->extension = 'jpg';
            $existingAttachment->save();
            return $existingAttachment->path;
        } else {
            // Generate file path
            $filePath = 'storage/attachments/' . $objectId . '/' . $filename;
            $fullPath = $attachmentsPath . '/' . $filename;
    
            if (file_put_contents($fullPath, $imageData)) {
                $attachmentData = [
                    'name' => $filename,
                    'path' => $filePath,
                    'content_type' => 'image/jpeg',
                    'extension' => 'jpg',
                    'attached_objectid' => $objectId,
                    'attached_objecttype' => $objectType,
                ];
                self::create($attachmentData);
                return $filePath;
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
            $path = $attachment->path;
            if (File::exists(public_path($path))) {
                File::delete(public_path($path));
            }
            $attachment->delete();
            
            return true;
        }
    
        return false;
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
    

    public function getUrl()
    {
        return asset($this->path);
    }
}
