<?php

namespace App\Http\Livewire\Component;

use Livewire\Component;
use App\Models\Config\ConfigRight;
use Exception;
use Lang;
use DB;
use App\Enums\Status;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;


class BaseComponent extends Component
{
    public $object;
    public $objectIdValue;
    public $actionValue = 'Create';
    public $inputs = [];
    public $status = '';
    public $VersioNumber;
    public $permissions;
    public $appCode;
    public $action;
    public $objectId;

    public $baseRoute;
    public $renderRoute;
    public $route;
    public function mount($action = null, $objectId = null)
    {
        $this->appCode =  Session::get('app_code', '');
        // Get all URL segments

        $this->action = $action ? $action : null;
        $this->objectId = $action ? $objectId : null;
        $this->actionValue = $action ? decryptWithSessionKey($action) : null;
        $this->objectIdValue = $objectId ? decryptWithSessionKey($objectId) : null;
        $this->baseRoute = Route::currentRouteName();

        $this->renderRoute = 'livewire.' . implode('.', array_map(function($segment) {
            // Insert hyphens after the first uppercase letter in each word, except for the very first character
            return preg_replace_callback('/\B([A-Z])/', function($match) {
                return '-' . strtolower($match[1]);
            }, $segment);
        }, explode('.', $this->baseRoute)));
        
        // Convert the entire route to lowercase except the first character of each segment
        $this->renderRoute = implode('.', array_map(function($segment) {
            return lcfirst($segment);
        }, explode('.', $this->renderRoute)));
        // Convert the entire route to lowercase
        $this->renderRoute = strtolower($this->renderRoute);

        $segments = Request::segments();
        $segmentsToIgnore = 2;
        if (in_array($this->actionValue, ['Edit', 'View'])) {
            $segmentsToIgnore += 1;
        }

        if (count($segments) > $segmentsToIgnore) {
            $segments = array_slice($segments, 0, -$segmentsToIgnore);
        }

        $fullPath = implode('/', $segments);
        $this->permissions = ConfigRight::getPermissionsByMenu($fullPath);
        
        if (!$this->hasValidPermissions()) {
            abort(403, 'You don\'t have access to this page.');
        }
        if (in_array($this->actionValue, ['Edit', 'View'])) {
            $this->onPopulateDropdowns();
            $this->onLoad();
            if($this->object)
            {
                $this->status = Status::getStatusString($this->object->status_code);
                $this->VersioNumber = $this->object->version_number;
            }
        } elseif ($this->actionValue === 'Create') {
            $this->resetForm();
        }else{
            $this->route .=  $this->baseRoute.'.Detail';
            $this->renderRoute .=  '.index';
        }
    }

    protected function hasValidPermissions()
    {
        if ($this->actionValue === 'Edit' && !$this->permissions['update']) {
            $this->actionValue = 'View';
        }

        if ($this->actionValue === 'View' && !$this->permissions['read']) {
            return false;
        }

        if ($this->actionValue === 'Create' && !$this->permissions['create']) {
            return false;
        }

        if (is_null($this->actionValue) && !$this->permissions['read']) {
            return false;
        }

        return true;
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
            $this->onReset();
            $this->onPopulateDropdowns();
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
