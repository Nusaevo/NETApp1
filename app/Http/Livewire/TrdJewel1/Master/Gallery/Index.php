<?php

namespace App\Http\Livewire\TrdJewel1\Master\Gallery;

use App\Http\Livewire\Component\BaseComponent;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\Base\Attachment;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Lang;

class Index extends BaseComponent
{
    use WithPagination, WithFileUploads;

    public $images = [];

    public function render()
    {
        $attachments = Attachment::where('attached_objecttype', 'gallery')
            ->paginate(9);

        return view('livewire.trd-jewel1.master.gallery.index', ['attachments' => $attachments]);
    }

    protected function onPreRender()
    {
    }

    protected $listeners = [
        'imagesCaptured'  => 'imagesCaptured'
    ];


    public function imagesCaptured($imageData)
    {
        $filename = uniqid() . '.jpg';
        $filePath = Attachment::saveAttachmentByFileName($imageData, null , 'gallery', $filename);
        if ($filePath) {
            session()->flash('message', 'Image uploaded successfully.');
        } else {
            session()->flash('error', 'Image upload failed.');
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
            Attachment::deleteAttachmentByFilename($attachment->attached_objectid, $attachment->attached_objecttype, $attachment->name);
            session()->flash('message', 'Image deleted successfully.');
        } else {
            session()->flash('error', 'Image deletion failed.');
        }
    }
}
