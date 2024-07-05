<?php

namespace App\Http\Livewire\Component;

use Livewire\Component;
use App\Models\SysConfig1\ConfigRight;
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
    public $VersionNumber;
    public $permissions;
    public $appCode;
    public $action;
    public $objectId;

    public $baseRoute;
    public $baseRenderRoute;
    public $langBasePath;
    public $renderRoute;
    public $route;

    public $additionalParam;
    public $customValidationAttributes;
    public $customRules;
    public $bypassPermissions = false;

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        $this->onPreRender();
        $this->additionalParam = $additionalParam;
        $this->appCode =  Session::get('app_code', '');
        // Get all URL segments

        $this->action = $action ? $action : null;
        $this->objectId = $action ? $objectId : null;

        if ($actionValue !== null) {
            $this->actionValue = $actionValue;
        } else {
            $this->actionValue = $action ? decryptWithSessionKey($action) : null;
        }

        if ($objectIdValue !== null) {
            $this->objectIdValue = $objectIdValue;
        } else {
            $this->objectIdValue = $objectId ? decryptWithSessionKey($objectId) : null;
        }
        $this->baseRoute = Route::currentRouteName();

        $this->renderRoute =  implode('.', array_map(function($segment) {
            // Insert hyphens after the first uppercase letter in each word, except for the very first character
            return preg_replace_callback('/(?<=\w)([A-Z])/', function($match) use ($segment) {
                $prevChar = substr($segment, strpos($segment, $match[0]) - 1, 1);
                if ($prevChar === '_') {
                    return $match[0];
                } else {
                    return '-' . strtolower($match[1]);
                }
            }, $segment);
        }, explode('.', $this->baseRoute)));
        // Convert the entire route to lowercase except the first character of each segment
        $this->renderRoute = implode('.', array_map(function($segment) {
            return lcfirst($segment);
        }, explode('.', $this->renderRoute)));
        // Convert the entire route to lowercase
        $this->baseRenderRoute = strtolower($this->renderRoute);
        $this->renderRoute = 'livewire.' .$this->baseRenderRoute;

        $segments = Request::segments();
        $segmentsToIgnore = 0;
         if (in_array($this->actionValue, ['Edit', 'View'])) {
            $segmentsToIgnore = 3;
        } elseif ($this->actionValue == 'Create') {
            $segmentsToIgnore = 2;
        }

        if (isset($this->additionalParam)) {
            $additionalSegments = count(explode('/', $this->additionalParam));
            $segmentsToIgnore += $additionalSegments;
        }
        if ($segmentsToIgnore > 0 && count($segments) > $segmentsToIgnore) {
            $segments = array_slice($segments, 0, -$segmentsToIgnore);
        }

        // Check if the last segment contains "Detail" string and remove it
        // this only form inside form like material form component
        if (!empty($segments)) {
            $lastSegmentIndex = count($segments) - 1;
            if (strpos($segments[$lastSegmentIndex], 'Detail') !== false) {
                array_pop($segments);
            }
        }
        $fullPath = implode('/', $segments);
        $this->permissions = ConfigRight::getPermissionsByMenu($fullPath);

        if (!$this->hasValidPermissions()) {
            abort(403, 'You don\'t have access to this page.');
        }

        if (in_array($this->actionValue, ['Edit', 'View'])) {
            $this->onPopulateDropdowns();
            $this->onLoadForEdit();
            if($this->object)
            {
                $this->status = Status::getStatusString($this->object->status_code);
                $this->VersionNumber = $this->object->version_number;
            }
        } elseif ($this->actionValue === 'Create') {
            $this->resetForm();
            if ($this->objectIdValue !== null) {

                $this->onPopulateDropdowns();
                $this->onLoadForEdit();
                if($this->object)
                {
                    $this->status = Status::getStatusString($this->object->status_code);
                    $this->VersionNumber = $this->object->version_number;
                }
            }
        }else{
            $this->route .=  $this->baseRoute.'.Detail';
            $this->renderRoute .=  '.index';
        }
        $this->langBasePath  = str_replace('.', '/', $this->baseRenderRoute);
    }

    public function trans($key)
    {
        $fullKey = $this->langBasePath . "." . $key;
        $translation = Lang::get($fullKey);
        if ($translation === $fullKey) {
            return $key;
        } else {
            return $translation;
        }
    }

    protected function hasValidPermissions()
    {
        if ($this->bypassPermissions) {
            return true;
        }

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
            $this->validate($this->customRules,[],$this->customValidationAttributes);
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
            $this->VersionNumber = $this->object->version_number ?? null;
        }
    }

    public function Save()
    {
        $this->validateForm();
        DB::beginTransaction();
        try {
            $this->updateVersionNumber();
            $this->onValidateAndSave();
            DB::commit();
            $this->notify('success',Lang::get('generic.string.save'));
            $this->resetForm();
        } catch (Exception $e) {
            DB::rollBack();
            $this->notify('error', Lang::get('generic.error.save', ['message' => $e->getMessage()]));
        }
    }

    public function SaveWithoutNotification()
    {
        $this->validateForm();
        DB::beginTransaction();
        try {
            $this->updateVersionNumber();
            $this->onValidateAndSave();
            DB::commit();
            $this->resetForm();
        } catch (Exception $e) {
            DB::rollBack();
            $this->notify('error', $e->getMessage());
        }
    }

    protected function change()
    {
        try {
            $this->updateVersionNumber();
            if ($this->object->deleted_at) {
                if (isset($this->object->status_code)) {
                    $this->object->status_code =  Status::ACTIVE;
                }
                $this->object->deleted_at = null;
                $messageKey = 'generic.string.enable';
            } else {
                if (isset($this->object->status_code)) {
                    $this->object->status_code =  Status::DEACTIVATED;
                }
                $this->object->save();
                $this->object->delete();
                $messageKey = 'generic.string.disable';
            }

            $this->object->save();
            $this->notify('success', Lang::get($messageKey));
        } catch (Exception $e) {
            $this->notify('error',Lang::get('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        $this->dispatchBrowserEvent('refresh');
    }

    protected function updateVersionNumber()
    {
        if ($this->actionValue == 'Edit' && isset($this->object->id)) {
            if ($this->object->version_number != $this->VersionNumber) {
                throw new \Exception("This object has already been updated by another user. Please refresh the page and try again.");
            }
            if ($this->object->isDirty()) {
                $this->VersionNumber ++;
            }
        }
    }


}
