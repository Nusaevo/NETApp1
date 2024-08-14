<?php

namespace App\Livewire\TrdJewel1\Master\Gallery;

use App\Livewire\Component\BaseComponent;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Base\Attachment;

class StorageComponent extends BaseComponent
{
    use WithPagination, WithFileUploads;

    public $images = [];
    public $isDialogBoxComponent;
    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null, $isDialogBoxComponent = true)
    {
        $this->isDialogBoxComponent = $isDialogBoxComponent;
        if($isDialogBoxComponent)
        {
            $this->bypassPermissions = true;
        }
        parent::mount($action, $objectId, $actionValue, $objectIdValue);
    }

    public function render()
    {
        if($this->isDialogBoxComponent == false)
        {
            $attachments = Attachment::where('attached_objecttype', 'NetStorage')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        }else{
            $attachments = Attachment::where('attached_objecttype', 'NetStorage')
            ->orderBy('created_at', 'desc')->get();
        }

        return view('livewire.trd-jewel1.master.gallery.storage-component', ['attachments' => $attachments]);
    }

    protected function onPreRender()
    {
    }

    protected $listeners = [
        'captureImages'  => 'captureImages',
        'deleteImage'  => 'deleteImage',
        'deleteSelectedImages' => 'deleteSelectedImages',
        'submitImages' => 'submitImages'
    ];

    public function submitImages($imageByteArrays)
    {
        $this->dispatch('saveImages', $imageByteArrays);
        $this->notify('success', 'Images submitted successfully: ');
    }

    public function captureImages($imageData)
    {
        $filename = uniqid() . '_' . time() . '.jpg';
        $filePath = Attachment::saveAttachmentByFileName($imageData, null, 'NetStorage', $filename);
        if ($filePath) {
            $this->notify('success', 'Image uploaded successfully.');
        } else {
            $this->notify('error','Image upload failed.');
        }
    }

    public function selectImage($imageId)
    {
        // Implement logic to handle image selection, if needed.
    }

    public function deleteImage($imageId)
    {
        $attachment = Attachment::find($imageId);
        if ($attachment) {
            $deleted = Attachment::deleteAttachmentById($imageId);
            if ($deleted) {
                $this->notify('success','Image deleted successfully.');
            } else {
                $this->notify('error','Image deletion failed.');
            }
        } else {
            $this->notify('error','Image not found.');
        }
    }

    public function deleteSelectedImages($imageIds)
    {
        foreach ($imageIds as $imageId) {
            $this->deleteImage($imageId);
        }
    }
}
