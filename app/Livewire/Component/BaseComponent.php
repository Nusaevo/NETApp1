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
    // Decrypted object ID and action
    public $objectIdValue;
    public $actionValue = 'Create';
    public $customActionValue = '';
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
    public $resetAfterCreate = true;

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

        initDatabaseConnection();
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
            Log::Error("Method Mount :". $e->getMessage());
            $this->notify('error', "Failed to load page, error :".$e->getMessage());
            throw $e;
        }
    }

    private function setActionAndObject($action, $objectId)
    {
        $this->action = $action ? $action : null;
        $this->objectId = $action ? $objectId : null;
    }

    private function setActionValue($action, $actionValue)
    {
        $this->actionValue = $actionValue !== null
            ? $actionValue
            : ($action ? decryptWithSessionKey($action) : null);
    }

    private function setObjectIdValue($objectId, $objectIdValue)
    {
        $this->objectIdValue = $objectIdValue !== null
            ? $objectIdValue
            : ($objectId ? decryptWithSessionKey($objectId) : null);
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
        if (!$this->hasValidPermissions()) {
            abort(403, 'You don\'t have access to this page.');
        }
    }

    private function handleActionSpecificLogic()
    {
        if (in_array($this->actionValue, ['Edit', 'View'])) {
            $this->handleEditViewAction();
        } elseif ($this->actionValue === 'Create') {
            $this->handleCreateAction();
        } else {
            $this->route .=  $this->baseRoute . '.Detail';
            // $this->renderRoute .= '.index';
        }
    }

    private function handleEditViewAction()
    {
        if ($this->object) {
            $this->status = Status::getStatusString($this->object->status_code);
            $this->VersionNumber = $this->object->version_number;
        }
    }

    private function handleCreateAction()
    {
        if ($this->objectIdValue !== null && $this->object) {
            $this->status = Status::getStatusString($this->object->status_code);
            $this->VersionNumber = $this->object->version_number;
        }
    }


    // Translate method
    public function getRoute()
    {
        if (isNullOrEmptyString($this->baseRoute)) {
            $this->baseRoute = Route::currentRouteName();
        }
        $route = ConfigMenu::getRoute($this->baseRoute);
        $this->baseRenderRoute = strtolower($route);
        // $this->renderRoute = 'livewire.' . $this->baseRenderRoute;
        // Convert base route to URL segments
        $fullUrl = str_replace('.', '/', $this->baseRoute);
        $menu_link = ConfigMenu::getFullPathLink($fullUrl, $this->actionValue, $this->additionalParam);
        $this->permissions = ConfigRight::getPermissionsByMenu($menu_link);
        $this->menuName = ConfigMenu::getMenuNameByLink($menu_link);
        $this->langBasePath  = str_replace('.', '/', $this->baseRenderRoute);
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
            $this->customActionValue = "View";
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
            $this->validate($this->rules, [], $this->customValidationAttributes);
        } catch (Exception $e) {
            Log::Error("Method ValidateForm :". $e->getMessage());
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
            // Start detailed logging for onValidateAndSave
            $this->onValidateAndSave();
            // $start = microtime(true);
            // $end = microtime(true);
            // Log::info('Execution time for onValidateAndSave: ' . ($end - $start) . ' seconds');

            DB::commit();
            if (!$this->isEditOrView() && $this->resetAfterCreate) {
                $this->onReset();
            }

            $this->notify('success', __('generic.string.save'));
        } catch (Exception $e) {
            DB::rollBack();
            if ($this->isEditOrView()) {
                $this->VersionNumber--;
            }
            Log::Error("Method Save :". $e->getMessage());
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
            if (!$this->isEditOrView() && $this->resetAfterCreate) {
                $this->onReset();
            }
        } catch (QueryException $e) {
            Log::Error("Method SaveWithoutNotification :". $e->getMessage());
            DB::rollBack();
            if ($this->isEditOrView()) {
                $this->VersionNumber--;
            }

            dd($e->getMessage());
        } catch (PDOException $e) {
            Log::Error("Method SaveWithoutNotification :". $e->getMessage());
            DB::rollBack();
            if ($this->isEditOrView()) {
                $this->VersionNumber--;
            }
            dd($e->getMessage());
        } catch (Exception $e) {
            Log::Error("Method SaveWithoutNotification :". $e->getMessage());
            DB::rollBack();
            if ($this->isEditOrView()) {
                $this->VersionNumber--;
            }
            $this->notify('error', __('generic.error.save', ['message' => $e->getMessage()]));
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
            Log::Error("Method Change :". $e->getMessage());
            $this->VersionNumber--;
            $this->notify('error', __('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['message' => $e->getMessage()]));
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
            $this->VersionNumber++;
        }
    }

    public function goBack()
    {
        // Retrieve the previous URL from the session
        $previousUrl = session('previous_url', url()->previous());

        // Redirect to the previous URL
        return redirect()->to($previousUrl);
    }

    protected function onReset() {}
}
