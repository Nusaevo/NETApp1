<?php

namespace App\Http\Livewire\Components;

use Livewire\Component;
use Exception;
use Lang;
use DB;
use App\Enums\Status;

class BaseComponent extends Component
{
    public $object;
    public $objectIdValue;
    public $actionValue = 'Create'; // Default action
    public $inputs = [];
    public $status = '';
    public $VersioNumber;

    /**
     * The "mount" method is automatically called when the component is instantiated.
     * Use this method to initialize your component's state.
     *
     * @param string $action Action to be performed.
     * @param mixed $objectId Optional ID of the object being manipulated.
     */
    public function mount($action, $objectId = null)
    {
        try {
            $this->actionValue = decryptWithSessionKey($action);
            if(isset($objectId))
            {
                $this->objectIdValue = decryptWithSessionKey($objectId);
            }
            $this->onPopulateDropdowns();
            if ($this->actionValue === 'Edit' || $this->actionValue === 'View') {
                $this->onLoad();
                $this->status = Status::getStatusString($this->object->status_code);
                $this->VersioNumber = $this->object->version_number;
            } else {
                $this->resetForm();
            }
        } catch (Exception $e) {
            abort(404, 'Page not found.');
        }
    }

    protected function validateForm()
    {
        try {
            $this->validate();
        } catch (Exception $e) {
            $this->notify('error', Lang::get('generic.error.create', ['message' => $e->getMessage()]));
            throw $e;
        }
    }

    protected function notify($type, $message)
    {
        $this->dispatchBrowserEvent('notify-swal', [
            'type' => $type,
            'message' => $message,
        ]);
    }

    protected function resetForm()
    {
        if ($this->actionValue == 'Create') {
            $this->onPopulateDropdowns();
            $this->onReset();
        }elseif ($this->actionValue == 'Edit') {
            $this->VersioNumber = $this->object->version_number ?? null;
        }
    }

    public function Save()
    {
        $this->validateForm();
        DB::beginTransaction();
        try {
            if ($this->actionValue == 'Edit') {
                $this->object->updateObject($this->VersioNumber);
            }
            if ($this->object) {
                $this->onValidateAndSave();
            }
            DB::commit();
            $this->notify('success',Lang::get('generic.success.save'));
            $this->resetForm();
        } catch (Exception $e) {
            DB::rollBack();
            $this->notify('error', Lang::get('generic.error.save', ['message' => $e->getMessage()]));
        }
    }

    protected function change()
    {
        try {
            $this->object->updateObject($this->VersioNumber);

            if ($this->object->deleted_at) {
                $this->object->status_code = Status::ACTIVE;
                $this->object->deleted_at = null;
                $messageKey = 'generic.success.enable';
            } else {
                $this->object->status_code = Status::DEACTIVATED;
                $this->object->save();
                $this->object->delete();
                $messageKey = 'generic.success.disable';
            }

            $this->object->save();
            $this->notify('success', Lang::get($messageKey));
        } catch (Exception $e) {
            $this->notify('error',Lang::get('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        $this->dispatchBrowserEvent('refresh');
    }
}
