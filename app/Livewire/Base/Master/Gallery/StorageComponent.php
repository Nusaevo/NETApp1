<?php

namespace App\Livewire\Base\Master\Gallery;

use App\Livewire\Component\BaseComponent;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Base\Attachment;
use Illuminate\Support\Facades\Session;

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
}
