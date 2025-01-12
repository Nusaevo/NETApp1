<?php

namespace App\Livewire\Component;

use Livewire\Component;
use App\Models\SysConfig1\ConfigMenu;
use App\Enums\Status;
use Illuminate\Support\Facades\{DB, Auth, Request, Session, Route, Log};
use Illuminate\Support\Str;
use Exception;
use Illuminate\Database\QueryException;
use PDOException;

class DetailComponent extends Component
{
    public $object;
    public $action;
    public $objectId;
    public $objectIdValue;
    public $actionValue = 'Create';
    public $customActionValue = '';
    public $inputs = [];
    public $status = '';
    public $appCode;
    public $baseRoute;
    public $langBasePath;
    public $baseRenderRoute;
    public $currentRoute;
    public $route;
    public $resetAfterCreate = true;
    public $additionalParam;
    public $permissions;
    public $customValidationAttributes;
    public $customRules;
    public $menuName = "";
    protected $versionSessionKey = 'session_version_number';
    protected $permissionSessionKey = 'session_permissions';

    protected function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        try {
            $this->additionalParam = $additionalParam;
            $this->appCode = Session::get('app_code', '');

            $this->setActionAndObject($action, $objectId);
            $this->setActionValue($action, $actionValue);
            $this->setObjectIdValue($objectId, $objectIdValue);

            $this->onReset();
            $this->getRoute();
            $this->checkPermissions();

            $this->handleRouteChange();
        } catch (Exception $e) {
            Log::error("Method Mount :" . $e->getMessage());
            $this->dispatch('error', "Failed to load page, error :" . $e->getMessage());
            throw $e;
        }
    }

    private function checkPermissions()
    {
        // Retrieve permissions from the session
        $this->permissions = Session::get($this->permissionSessionKey , []);

        if (!$this->hasValidPermissions()) {
            abort(403, "You don't have access to this page.");
        }
    }

    protected function hasValidPermissions()
    {
        if ($this->actionValue === 'Edit' && !$this->permissions['update']) {
            $this->customActionValue = 'View';
            $this->actionValue = 'View';
        }

        if (
            ($this->actionValue === 'View' && !$this->permissions['read']) ||
            ($this->actionValue === 'Create' && !$this->permissions['create']) ||
            (is_null($this->actionValue) && !$this->permissions['read'])
        ) {
            return false;
        }

        return true;
    }


    protected function updateSharedVersionNumber($increment = true)
    {
        if (in_array($this->actionValue, ['Edit', 'View'])) {
            $currentVersion = Session::get($this->versionSessionKey, 1);
            $newVersion = $increment ? $currentVersion + 1 : max($currentVersion - 1, 1);

            Session::put($this->versionSessionKey, $newVersion);
        }
    }

    private function setActionAndObject($action, $objectId)
    {
        $this->action = $action ?: null;
        $this->objectId = $action ? $objectId : null;
    }

    private function setActionValue($action, $actionValue)
    {
        if ($actionValue !== null) {
            $this->actionValue = $actionValue;
        } elseif ($action) {
            $this->actionValue = decryptWithSessionKey($action);
        } else {
            $this->actionValue = null;
        }
    }

    private function setObjectIdValue($objectId, $objectIdValue)
    {
        if ($objectIdValue !== null) {
            $this->objectIdValue = $objectIdValue;
        } elseif ($objectId) {
            $this->objectIdValue = decryptWithSessionKey($objectId);
        } else {
            $this->objectIdValue = null;
        }
    }

    private function handleRouteChange()
    {
        $initialRoute = $this->baseRoute;
        $this->onPreRender();

        if ($initialRoute !== $this->baseRoute) {
            $this->getRoute();
        }
    }

    protected function getRoute()
    {
        if (isNullOrEmptyString($this->baseRoute)) {
            $this->baseRoute = Route::currentRouteName();
        }
        $route = ConfigMenu::getRoute($this->baseRoute);
        $this->baseRenderRoute = strtolower($route);
        $this->langBasePath = str_replace('.', '/', $this->baseRenderRoute);
    }

    protected function trans($key)
    {
        $fullKey = $this->langBasePath . "." . $key;
        $translation = __($fullKey);
        return $translation === $fullKey ? $key : $translation;
    }

    protected function validateForm()
    {
        try {
            $this->validate($this->rules, [], $this->customValidationAttributes);
        } catch (Exception $e) {
            Log::error("Method ValidateForm :" . $e->getMessage());
            $this->dispatch('error', __('generic.error.create', ['message' => $e->getMessage()]));
            throw $e;
        }
    }

    protected function isEditOrView()
    {
        return in_array($this->actionValue, ['Edit', 'View']);
    }

    protected function Save()
    {
        $this->validateForm();
        DB::beginTransaction();
        try {
            $this->updateVersionNumber();
            $this->object->save();
            $this->onValidateAndSave();
            DB::commit();

            if (!$this->isEditOrView() && $this->resetAfterCreate) {
                $this->onReset();
            }

            $this->dispatch('success', __('generic.string.save'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Method Save :" . $e->getMessage());
            $this->dispatch('error', __('generic.error.save', ['message' => $e->getMessage()]));
        }
    }

    protected function SaveWithoutNotification()
    {
        $this->validateForm();
        DB::beginTransaction();
        try {
            $this->updateVersionNumber();
            $this->object->save();
            $this->onValidateAndSave();
            DB::commit();

            if (!$this->isEditOrView() && $this->resetAfterCreate) {
                $this->onReset();
            }
        } catch (QueryException | PDOException | Exception $e) {
            Log::error("Method SaveWithoutNotification :" . $e->getMessage());
            DB::rollBack();
            dd($e->getMessage());
        }
    }

    protected function change()
    {
        try {
            $this->updateVersionNumber();

            if ($this->object->deleted_at) {
                if (isset($this->object->status_code)) {
                    $this->object->status_code = Status::ACTIVE;
                }
                $this->object->deleted_at = null;
                $messageKey = 'generic.string.enable';
            } else {
                if (isset($this->object->status_code)) {
                    $this->object->status_code = Status::NONACTIVE;
                }
                $this->object->save();
                $this->object->delete();
                $messageKey = 'generic.string.disable';
            }

            $this->object->save();
            $this->dispatch('success', __($messageKey));
        } catch (Exception $e) {
            Log::error("Method Change :" . $e->getMessage());
            $this->updateSharedVersionNumber(false);
            $this->dispatch('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        $this->dispatch('refresh');
    }

    protected function updateVersionNumber()
    {
        if ($this->actionValue === 'Edit' && isset($this->object->id)) {
            $sessionVersion = Session::get($this->versionSessionKey);

            if ($this->object->version_number != $sessionVersion) {
                throw new Exception("This object has already been updated by another user. Please refresh the page and try again.");
            }

            $this->updateSharedVersionNumber(true);
        }
    }

    protected function onReset()
    {
        // This method is intentionally left empty.
        // Override this method in child classes to implement specific reset logic.
    }

}
