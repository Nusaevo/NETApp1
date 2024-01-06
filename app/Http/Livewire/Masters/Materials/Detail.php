<?php

namespace App\Http\Livewire\Masters\Materials;

use Livewire\Component;
use App\Models\Material;
use App\Models\ItemUnit;
use App\Models\Settings\ConfigConst;
use App\Models\IvtBal;
use App\Models\Attachment;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Lang;
use Exception;
use DB;
use Livewire\WithFileUploads;

class Detail extends Component
{
    public $status = '';

    public $actionValue = 'Create';
    public $objectIdValue;

    public function mount($action, $objectId = null)
    {
        $this->actionValue = Crypt::decryptString($action);

        if (($this->actionValue === 'Edit' || $this->actionValue === 'View') && $objectId) {
            $this->objectIdValue = Crypt::decryptString($objectId);
        }
    }

    public function render()
    {
        return view('livewire.masters.materials.edit');
    }
}
