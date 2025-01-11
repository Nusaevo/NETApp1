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
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use PDOException;

class BaseComponent extends Component
{
    public $object;
    public $action;
    public $objectId;
    public $objectIdValue;
    public $actionValue = 'Create';
    public $customActionValue = '';
    public $inputs = [];
    public $status = '';
    public $permissions;
    public $appCode;
    public $baseRoute;
    public $langBasePath;
    public $baseRenderRoute;
    public $currentRoute;
    public $route;
    public $resetAfterCreate = true;
    public $additionalParam;
    public $customValidationAttributes;
    public $customRules;
    public $bypassPermissions = false;
    public $menuName = "";
    public $isComponent = false;

    protected $versionSessionKey = 'session_version_number';
    protected $permissionSessionKey = 'session_permissions';

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        app(config('settings.KT_THEME_BOOTSTRAP.default'))->init();
        session(['previous_url' => url()->previous()]);
        if (!$this->isComponent) {
            Session::forget($this->permissionSessionKey);
        }
        try {
            $this->additionalParam = $additionalParam;
            $this->appCode = Session::get('app_code', '');

            $this->setActionAndObject($action, $objectId);
            $this->setActionValue($action, $actionValue);
            $this->setObjectIdValue($objectId, $objectIdValue);

            $this->onReset();
            $this->getRoute();
            $this->handleRouteChange();
            $this->checkPermissions();
            $this->handleActionSpecificLogic();
        } catch (Exception $e) {
            Log::error("Method Mount : " . $e->getMessage());
            $this->dispatch('error', "Failed to load page, error: " . $e->getMessage());
            throw $e;
        }
        if (!$this->isComponent) {
            Session::forget($this->versionSessionKey);
            $this->initializeVersionNumber();
        }
    }

    protected function initializeVersionNumber()
    {
        if (in_array($this->actionValue, ['Edit', 'View'])) {
            $currentVersion = Session::get($this->versionSessionKey);

            if (is_null($currentVersion) && isset($this->object->version_number)) {
                Session::put($this->versionSessionKey, $this->object->version_number);
            }
        }
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

    private function checkPermissions()
    {
        if ($this->isComponent) {
            return;
        }

        // Retrieve permissions from the session
        $permissions = Session::get($this->permissionSessionKey , []);

        if (!$this->hasValidPermissions($permissions)) {
            abort(403, "You don't have access to this page.");
        }
    }

    private function handleActionSpecificLogic()
    {
        $this->currentRoute = $this->baseRenderRoute;

        if (in_array($this->actionValue, ['Edit', 'View'])) {
            $this->handleEditViewAction();
        } elseif ($this->actionValue === 'Create') {
            $this->handleCreateAction();
        } else {
            $this->route .= $this->baseRoute . '.Detail';
            return;
        }

        $segments = explode('.', $this->currentRoute);
        array_pop($segments);
        $this->currentRoute = implode('.', $segments);
    }

    private function handleEditViewAction()
    {
        if ($this->object) {
            $this->status = $this->object->deleted_at === null ? 'Active' : Status::getStatusString($this->object->status_code);
        }
    }

    private function handleCreateAction()
    {
        if ($this->objectIdValue !== null && $this->object) {
            $this->status = $this->object->deleted_at === null ? 'Active' : Status::getStatusString($this->object->status_code);
        }
    }

    public function getRoute()
    {
        if (isNullOrEmptyString($this->baseRoute)) {
            $this->baseRoute = Route::currentRouteName();
        }

        $route = ConfigMenu::getRoute($this->baseRoute);
        $this->baseRenderRoute = strtolower($route);

        $fullUrl = str_replace('.', '/', $this->baseRoute);
        $menu_link = ConfigMenu::getFullPathLink($fullUrl, $this->actionValue, $this->additionalParam);
        $this->menuName = ConfigMenu::getMenuNameByLink($menu_link);
        $this->langBasePath = str_replace('.', '/', $this->baseRenderRoute);

        if ($this->isComponent) {
            return;
        }

        // Retrieve permissions and store them in the session
        $this->permissions = ConfigRight::getPermissionsByMenu($menu_link);
        Session::put($this->permissionSessionKey , $this->permissions); // Store permissions in the session
    }


    public function trans($key)
    {
        $fullKey = $this->langBasePath . "." . $key;
        $translation = __($fullKey);

        return $translation === $fullKey ? $key : $translation;
    }

    protected function hasValidPermissions()
    {
        if ($this->bypassPermissions) {
            return true;
        }

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

    protected function validateForm()
    {
        try {
            $this->validate($this->rules, [], $this->customValidationAttributes);
        } catch (Exception $e) {
            Log::error("Method ValidateForm : " . $e->getMessage());
            $this->dispatch('error', __('generic.error.create', ['message' => $e->getMessage()]));
            throw $e;
        }
    }

    protected function notify($type, $message)
    {
        $this->dispatch('notify-swal', [
            'type' => $type,
            'message' => $message,
        ]);
    }

    public function isEditOrView()
    {
        return in_array($this->actionValue, ['Edit', 'View']);
    }

    public function Save()
    {
        $this->validateForm();
        DB::beginTransaction();

        try {
            $this->updateVersionNumber();
            $this->onValidateAndSave();
            DB::commit();

            if (!$this->isEditOrView() && $this->resetAfterCreate) {
                $this->onReset();
            }

            $this->dispatch('success', __('generic.string.save'));
        } catch (Exception $e) {
            DB::rollBack();
            $this->updateSharedVersionNumber(false);
            Log::error("Method Save : " . $e->getMessage());
            $this->dispatch('error', __('generic.error.save', ['message' => $e->getMessage()]));
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

            if (!$this->isEditOrView() && $this->resetAfterCreate) {
                $this->onReset();
            }
        } catch (QueryException | PDOException | Exception $e) {
            Log::error("Method SaveWithoutNotification : " . $e->getMessage());
            DB::rollBack();

            if ($this->isEditOrView()) {
                $this->updateSharedVersionNumber(false);
            }

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
            Log::error("Method Change : " . $e->getMessage());
            $this->updateSharedVersionNumber(false);
            $this->dispatch('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        $this->dispatch('refresh');
    }

    protected function updateVersionNumber()
    {
        if ($this->isComponent) {
            return;
        }
        if ($this->actionValue === 'Edit' && isset($this->object->id)) {
            $sessionVersion = Session::get($this->versionSessionKey);

            if ($this->object->version_number != $sessionVersion) {
                throw new Exception("This object has already been updated by another user. Please refresh the page and try again.");
            }

            $this->updateSharedVersionNumber(true);
        }
    }

    public function goBack()
    {
        return redirect()->to(session('previous_url', url()->previous()));
    }

    protected function onReset()
    {
        // This method is intentionally left empty.
        // Override this method in child classes to implement specific reset logic.
    }

}
