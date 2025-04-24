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

    public function mount($action = null, $objectId = null, $actionValue = null, $objectIdValue = null, $additionalParam = null)
    {
        app(config('settings.KT_THEME_BOOTSTRAP.default'))->init();
        session(['previous_url' => url()->previous()]);
        try {
            $this->additionalParam = $additionalParam;
            $this->appCode = Session::get('app_code', '');
            $this->setActionAndObject($action, $objectId);
            $this->setActionValue($action, $actionValue);
            $this->setObjectIdValue($objectId, $objectIdValue);
            $this->onReset();
            $this->getRoute();
            $this->handleRouteChange();
            $this->handleActionSpecificLogic();
        } catch (Exception $e) {
            Log::error("Method Mount : " . $e->getMessage());
            $this->dispatch('error', "Failed to load page, error: " . $e->getMessage());
            throw $e;
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
    }


    public function trans($key)
    {
        $fullKey = $this->langBasePath . "." . $key;
        $translation = __($fullKey);

        return $translation === $fullKey ? $key : $translation;
    }


    protected function onReset()
    {
        // This method is intentionally left empty.
        // Override this method in child classes to implement specific reset logic.
    }

}
