<?php

namespace App\Livewire\Component;

use Livewire\Component;
use App\Models\SysConfig1\ConfigRight;
use App\Models\SysConfig1\ConfigMenu;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Enums\Status;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class BaseComponent extends Component
{
    public $object;
    public $action;
    public $objectId;
    // Decrypted object ID and action
    public $objectIdValue;
    public $actionValue = 'Create';
    public $inputs = [];
    public $status = '';
    public $VersionNumber;
    public $permissions;
    public $appCode;

    // Current route
    public $baseRoute;
    // Route for translation and getting base component
    public $langBasePath;
    // Route to blade PHP
    public $baseRenderRoute;
    public $renderRoute;
    public $route;

    public $additionalParam;
    public $customValidationAttributes;
    public $customRules;
    public $bypassPermissions = false;
    public $menuName = "";

    // Mount method to initialize the component
    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        app(config('settings.KT_THEME_BOOTSTRAP.default'))->init();
        session(['previous_url' => url()->previous()]);
        $this->onPreRender();
        $this->additionalParam = $additionalParam;
        $this->appCode =  Session::get('app_code', '');

        $this->action = $action ? $action : null;
        $this->objectId = $action ? $objectId : null;

        // Decrypt action value if provided
        if ($actionValue !== null) {
            $this->actionValue = $actionValue;
        } else {
            $this->actionValue = $action ? decryptWithSessionKey($action) : null;
        }

        // Decrypt object ID value if provided
        if ($objectIdValue !== null) {
            $this->objectIdValue = $objectIdValue;
        } else {
            $this->objectIdValue = $objectId ? decryptWithSessionKey($objectId) : null;
        }

        // Set base route if not already set
        if (empty($this->baseRoute)) {
            $this->baseRoute = Route::currentRouteName();
        }
        $route = ConfigMenu::getRoute($this->baseRoute);
        $this->baseRenderRoute = strtolower($route);
        $this->renderRoute = 'livewire.' . $this->baseRenderRoute;

        // Convert base route to URL segments
        $fullUrl = str_replace('.', '/', $this->baseRoute);
        $menu_link = ConfigMenu::getFullPathLink($fullUrl, $this->actionValue, $this->additionalParam);
        $this->permissions = ConfigRight::getPermissionsByMenu($menu_link);
        $this->menuName = ConfigMenu::getMenuNameByLink($menu_link);
        $this->langBasePath  = str_replace('.', '/', $this->baseRenderRoute);
        // Check for valid permissions
        if (!$this->hasValidPermissions()) {
            abort(403, 'You don\'t have access to this page.');
        }

        // Handle specific actions like 'Edit', 'View', and 'Create'
        if (in_array($this->actionValue, ['Edit', 'View'])) {
            $this->onPopulateDropdowns();
            $this->onLoadForEdit();
            if($this->object) {
                $this->status = Status::getStatusString($this->object->status_code);
                $this->VersionNumber = $this->object->version_number;
            }
        } elseif ($this->actionValue === 'Create') {
            $this->resetForm();
            if ($this->objectIdValue !== null) {
                $this->onPopulateDropdowns();
                $this->onLoadForEdit();
                if($this->object) {
                    $this->status = Status::getStatusString($this->object->status_code);
                    $this->VersionNumber = $this->object->version_number;
                }
            }
        } else {
            $this->route .=  $this->baseRoute.'.Detail';
            $this->renderRoute .=  '.index';
        }
    }

    // Translate method
    public function trans($key)
    {
        $fullKey = $this->langBasePath . "." . $key;
        $translation = __($fullKey);
        if ($translation === $fullKey) {
            return $key;
        } else {
            return $translation;
        }
    }

    // Check if user has valid permissions
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

    // Validate form
    protected function validateForm()
    {
        try {
            $this->validate($this->rules,[],$this->customValidationAttributes);
        } catch (Exception $e) {
            $this->notify('error', __('generic.error.create', ['message' => $e->getMessage()]));
            throw $e;
        }
    }

    // Notify method
    protected function notify($type, $message)
    {

        $this->dispatch('notify-swal', [
            'type' => $type,
            'message' => $message,
        ]);
    }

    // Reset form
    protected function resetForm()
    {
        if ($this->actionValue == 'Create') {
            $this->onReset();
            $this->onPopulateDropdowns();
        } elseif ($this->actionValue == 'Edit') {
            $this->VersionNumber = $this->object->version_number ?? null;
        }
    }

    // Save method
    public function Save()
    {
        $this->validateForm();
        DB::beginTransaction();
        try {
            $this->updateVersionNumber();
            $this->onValidateAndSave();
            DB::commit();
            $this->notify('success',__('generic.string.save'));
            $this->resetForm();
        } catch (Exception $e) {
            DB::rollBack();
            $this->notify('error', __('generic.error.save', ['message' => $e->getMessage()]));
        }
    }

    // Save without notification
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

    // Change method
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
                    $this->object->status_code =  Status::NONACTIVE;
                }
                $this->object->save();
                $this->object->delete();
                $messageKey = 'generic.string.disable';
            }

            $this->object->save();
            $this->notify('success', __($messageKey));
        } catch (Exception $e) {
            $this->notify('error',__('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        $this->dispatch('refresh');
    }

    // Update version number method
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

    public function goBack()
    {
        // Retrieve the previous URL from the session
        $previousUrl = session('previous_url', url()->previous());

        // Redirect to the previous URL
        return redirect()->to($previousUrl);
    }
}
