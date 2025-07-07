<?php

namespace App\Livewire\Component;

use Livewire\Component;
use App\Models\SysConfig1\{ConfigRight, ConfigMenu};
use Illuminate\Support\Facades\{DB, Auth, Request, Session, Route, Log};
use Illuminate\Support\Str;
use App\Enums\Status;
use Exception;
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
    public bool $hasChanges = false;

    public $versionNumber = 1;
    protected $versionSessionKey = 'session_version_number';
    protected $permissionSessionKey = 'session_permissions';
    public function updated($propertyName)
    {
        $this->hasChanges = true;
        Session::put('page_has_changes', true);
        $this->dispatch('form-changed', hasChanges: true);
    }

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
            $this->versionNumber = $this->object->version_number ?? 1;
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
        $this->permissions = Session::get($this->permissionSessionKey , []);

        if (!$this->hasValidPermissions()) {
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
            $this->status = Status::getStatusString($this->object->status_code);
        }
    }

    private function handleCreateAction()
    {
        if ($this->objectIdValue !== null && $this->object) {
            $this->status = Status::getStatusString($this->object->status_code);
        }
    }

    public function getRoute()
    {
        // Ambil nama route
        if (isNullOrEmptyString($this->baseRoute)) {
            $this->baseRoute = Route::currentRouteName();
        }

        // Dapatkan route path dari config
        $route = ConfigMenu::getRoute($this->baseRoute);
        $this->baseRenderRoute = strtolower($route);

        // Konversi ke format path (dot → slash)
        $path = str_replace('.', '/', $this->baseRoute);

        // Ambil query string mentah, kalau ada
        $queryString = request()->getQueryString(); // hasil: "TYPE=C&other=val" atau null

        // Gabungkan path + query jika ada
        $fullUrl = $queryString ? $path . '?' . $queryString : $path;
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
        $this->hasChanges = false;
        Session::forget('page_has_changes');
        $this->dispatch('form-changed', hasChanges: false);
        $this->validateForm();

        // DB::beginTransaction();
        try {
            $this->updateVersionNumber();
            $this->onValidateAndSave();
            // DB::commit();
        } catch (Exception $e) {
            // DB::rollBack();
            $this->rollbackVersionNumber();
            $this->dispatch('error', __('generic.error.save', ['message' => $e->getMessage()]));
            Log::error("Method Save : " . $e->getMessage());
            throw $e;
        }
        if (!$this->isEditOrView() && $this->resetAfterCreate) {
            $this->onReset();
        }
        $this->dispatch('success', __('generic.string.save'));
    }

    public function SaveWithoutNotification()
    {
        sleep(1);

        $this->hasChanges = false;
        $this->dispatch('form-changed', ['hasChanges' => false]);
        $this->validateForm();

        try {
            DB::transaction(function () {
                $this->updateVersionNumber();
                $this->onValidateAndSave();
            });

            if (!$this->isEditOrView() && $this->resetAfterCreate) {
                $this->onReset();
            }
        } catch (QueryException | PDOException | Exception $e) {
            $this->rollbackVersionNumber();
            Log::error("Method SaveWithoutNotification : " . $e->getMessage());
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
                $this->object->save();
                $messageKey = 'generic.string.enable';
            } else {
                if (isset($this->object->status_code)) {
                    $this->object->status_code = Status::NONACTIVE;
                }
                $this->object->save();
                $this->object->delete();
                $messageKey = 'generic.string.disable';
            }
            $this->status = Status::getStatusString($this->object->status_code);

            $this->dispatch('success', __($messageKey));
        } catch (Exception $e) {
            Log::error("Method Change : " . $e->getMessage());
            $this->rollbackVersionNumber();
            $this->dispatch('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
        }

        $this->dispatch('refresh');
    }

    protected function updateVersionNumber()
    {
        if ($this->isComponent || $this->actionValue !== 'Edit' || !isset($this->object->id)) {
            return;
        }

        if ($this->object->version_number !== $this->versionNumber) {
            throw new Exception(
                "This object has already been updated by another user. Please refresh the page and try again."
            );
        }

        $this->versionNumber++;
    }

    protected function rollbackVersionNumber()
    {
        if ($this->actionValue === 'Edit') {
            // Roll back on error
            $this->versionNumber = max($this->versionNumber - 1, 1);
            $this->object->version_number = $this->versionNumber;
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
    public function stringToNumeric($value)
    {
        if (empty($value) || !is_string($value)) {
            return (float) $value;
        }

        // Remove dots (thousand separators) and replace comma with dot for decimal
        $cleanValue = str_replace(['.', ','], ['', '.'], $value);

        return (float) $cleanValue;
    }

}
