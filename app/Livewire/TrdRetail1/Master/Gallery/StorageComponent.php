<?php

namespace App\Livewire\TrdRetail1\Master\Gallery;

use App\Livewire\Component\BaseComponent;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\TrdRetail1\Base\Attachment;

class StorageComponent extends BaseComponent
{
    use WithPagination, WithFileUploads;

    public $images = [];
    public $isDialogBoxComponent;

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null, $isDialogBoxComponent = true)
    {
        $this->isDialogBoxComponent = $isDialogBoxComponent;
        $this->bypassPermissions = $isDialogBoxComponent;
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }

    public function render()
    {
        $query = Attachment::where('attached_objecttype', 'NetStorage')
            ->orderBy('created_at', 'desc');

        $attachments = $this->isDialogBoxComponent ? $query->get() : $query->paginate(15);
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
        if ($this->isDialogBoxComponent) {
            return;
        }

        $this->dispatch('saveImages', $imageByteArrays);
        $this->notify('success', 'Images submitted successfully.');
    }

    public function captureImages($imageData)
    {
        if ($this->isDialogBoxComponent) {
            return;
        }

        $filename = uniqid() . '_' . time() . '.jpg';
        $filePath = Attachment::saveAttachmentByFileName($imageData, null, 'NetStorage', $filename);
        $message = $filePath ? 'Image uploaded successfully.' : 'Image upload failed.';
        $this->notify($filePath ? 'success' : 'error', $message);
    }

    public function selectImage($imageId)
    {
        // Implement logic to handle image selection, if needed.
    }

    public function deleteImage($imageId)
    {
        if ($this->isDialogBoxComponent) {
            return;
        }

        $attachment = Attachment::find($imageId);
        if ($attachment) {
            $deleted = Attachment::deleteAttachmentById($imageId);
            $message = $deleted ? 'Image deleted successfully.' : 'Image deletion failed.';
            $this->notify($deleted ? 'success' : 'error', $message);
        } else {
            $this->notify('error', 'Image not found.');
        }
    }

    public function deleteSelectedImages($imageIds)
    {
        if ($this->isDialogBoxComponent) {
            return;
        }

        foreach ($imageIds as $imageId) {
            $this->deleteImage($imageId);
        }
    }
}
