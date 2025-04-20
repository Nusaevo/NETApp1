<?php

namespace App\Livewire\Base\Master\Gallery;

use App\Livewire\Component\BaseComponent;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Base\Attachment;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\TrdRetail1\Master\Material;

class StorageComponent extends BaseComponent
{
    use WithPagination, WithFileUploads;

    public $images = [];
    public $isComponent;

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null, $isComponent = true)
    {
        $this->isComponent = $isComponent;
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }

    public function render()
    {
        $query = Attachment::where('attached_objecttype', 'NetStorage')
            ->orderBy('created_at', 'desc');

        $attachments = $this->isComponent ? $query->get() : $query->paginate(15);
        $renderRoute = getViewPath(__NAMESPACE__, class_basename($this));
        return view($renderRoute, ['attachments' => $attachments]);
    }

    protected function onPreRender()
    {
    }

    protected $listeners = [
        'captureImages' => 'captureImages',
        'deleteImage' => 'deleteImage',
        'deleteSelectedImages' => 'deleteSelectedImages',
        'submitImages' => 'submitImages'
    ];

    public function submitImages($imageByteArrays)
    {
        if ($this->isComponent) {
            return;
        }

        $this->dispatch('saveImages', $imageByteArrays);
        $this->dispatch('success', 'Images submitted successfully.');
    }

    public function captureImages($imageData, $originalFilename = null)
    {
        if ($this->isComponent) {
            return;
        }
        $appCode = Session::get('app_code');

        if ($appCode === 'TrdRetail1' && $originalFilename) {
            $filename = $originalFilename;
        } else {
            $filename = uniqid() . '_' . time() . '.jpg';
        }
        $filePath = Attachment::saveAttachmentByFileName($imageData, null, 'NetStorage', $filename);

        $message = $filePath ? 'Image uploaded successfully.' : 'Image upload failed.';
        $this->dispatch($filePath ? 'success' : 'error', $message);
    }


    public function selectImage($imageId)
    {
        // Implement logic to handle image selection, if needed.
    }

    public function deleteImage($imageId)
    {
        if ($this->isComponent) {
            return;
        }

        $attachment = Attachment::find($imageId);
        if ($attachment) {
            $deleted = Attachment::deleteAttachmentById($imageId);
            $message = $deleted ? 'Image deleted successfully.' : 'Image deletion failed.';
            $this->dispatch($deleted ? 'success' : 'error', $message);
        } else {
            $this->dispatch('error', 'Image not found.');
        }
    }

    public function deleteSelectedImages($imageIds)
    {
        if ($this->isComponent) {
            return;
        }

        foreach ($imageIds as $imageId) {
            $this->deleteImage($imageId);
        }
    }

    //Custom Action For TrdRetail1


    private function fetchUrlContent($url)
    {
        $response = Http::get($url);

        if ($response->failed()) {
            throw new Exception("Failed to fetch content from URL: {$url}, Status Code: {$response->status()}");
        }

        return $response->body();
    }
    public $syncProgress = 0;
    public $syncedImages = [];
    public $failedImages = [];
    public $status = 'Starting...';

    public function syncImages()
    {
        if (Session::get('app_code') != 'TrdRetail1') {
            $this->status = 'This feature is only available for TrdRetail1.';
            return;
        }

        // Reset properti sebelum mulai
        $this->syncProgress = 0;
        $this->syncedImages = [];
        $this->failedImages = [];
        $this->status = 'Starting sync process...';

        $attachments = Attachment::where('path', 'like', '%NetStorage%')->get();

        if ($attachments->isEmpty()) {
            $this->status = 'No attachments found in NetStorage.';
            return;
        }

        $totalAttachments = $attachments->count();
        $processed = 0;

        foreach ($attachments as $attachment) {
            try {
                DB::beginTransaction();

                $attachmentFilename = pathinfo($attachment->name, PATHINFO_FILENAME);
                $material = Material::where('code', '=', "{$attachmentFilename}")->first();

                if (!$material) {
                    throw new Exception("No material found matching filename: {$attachmentFilename}");
                }

                $url = $attachment->getUrl();
                $response = Http::get($url);

                if ($response->failed()) {
                    throw new Exception("Failed to fetch image from URL: {$url}");
                }

                $imageData = $response->body();
                $mimeType = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpeg';
                $dataUri = "data:image/{$mimeType};base64," . base64_encode($imageData);

                $filename = uniqid() . '.jpg';
                $filePath = Attachment::saveAttachmentByFileName($dataUri, $material->id, class_basename($material), $filename);

                if ($filePath === false) {
                    throw new Exception("Failed to save attachment: {$filename}");
                }

                $this->syncedImages[] = [
                    'material_id' => $material->id,
                    'material_code' => $material->code,
                    'file_name'   => $filename,
                    'path'        => $filePath,
                ];

                Attachment::deleteAttachmentById($attachment->id);

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();

                $errorMessage = $e->getMessage();
                if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                    $errorMessage = "Material not found for filename: {$attachmentFilename}";
                } elseif ($e instanceof \Illuminate\Http\Client\RequestException) {
                    $errorMessage = "Failed to fetch image from URL: {$url}";
                }

                $this->failedImages[] = [
                    'file_name' => $attachmentFilename,
                    'error'     => $errorMessage,
                ];
            }

            $processed++;
            $this->syncProgress = intval(($processed / $totalAttachments) * 100);
            $this->status = "Processing image {$processed} of {$totalAttachments}";
        }

        $completionMessage = empty($this->failedImages)
            ? 'Sync completed successfully.'
            : "Sync completed. Synced: " . count($this->syncedImages) . ", Failed: " . count($this->failedImages);
        $this->status = $completionMessage;
    }

}
