<?php
namespace App\Models\Bases;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'attachments';

    protected $fillable = [
        'name',
        'path',
        'content_type',
        'extension',
        'descr',
        'attached_objectid',
        'attached_objecttype',
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

     /**
     * Save attachment to storage.
     *
     * @param string $imageDataUrl
     * @param int $objectId
     * @param string $objectType
     * @return string|bool
     */
    public static function saveAttachmentToStorage($imageDataUrl, $objectId, $objectType)
    {
        $attachmentsPath = public_path('storage/attachments/' . $objectId);
        if (!File::isDirectory($attachmentsPath)) {
            File::makeDirectory($attachmentsPath, 0777, true, true);
        }
        $imageData = substr($imageDataUrl, strpos($imageDataUrl, ',') + 1);
        $imageData = base64_decode($imageData);
        $filename = 'image_' . time() . '.jpg';
        $filePath = 'storage/attachments/' . $objectId . '/' . $filename;
        $fullPath = $attachmentsPath . '/' . $filename;
        if (file_put_contents($fullPath, $imageData)) {
            $attachmentData = [
                'name' => $filename,
                'path' => $filePath,
                'content_type' => 'image/jpeg', 
                'extension' => 'jpg',
                'attached_objectid' => $objectId,
                'attached_objecttype' => $objectType
            ];

            $existingAttachment = self::where('attached_objectid', $objectId)
                                       ->where('attached_objecttype', $objectType)
                                       ->first();

            if ($existingAttachment) {
                $existingAttachment->update($attachmentData);
            } else {
                self::create($attachmentData);
            }

            return $filePath; 
        }

        return false;
    }

}
