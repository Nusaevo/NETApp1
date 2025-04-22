<?php

namespace App\Livewire\Component;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use App\Models\SysConfig1\{ConfigRight,ConfigMenu};
use Rappasoft\LaravelLivewireTables\Views\Column;
use Exception;
use App\Enums\Status;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Util\GenericExport;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Support\Facades\Session;

abstract class BaseDataTableComponent extends DataTableComponent
{
    public $object;
    public $baseRoute;
    public $route;
    public $customRoute;
    public $langBasePath;
    public $appCode;
    public $isComponent = false;
    public $baseRenderRoute;
    public $permissions = ['create' => false, 'read' => false, 'update' => false, 'delete' => false];
    public $menu_link;
    protected $versionSessionKey = 'session_version_number';
    protected $permissionSessionKey = 'session_permissions';
    public ?string $initialQueryString = null;
    abstract public function columns(): array;

    protected $listeners = [
        'refreshData' => 'render',
        'viewData' => 'View',
        'editData' => 'Edit',
        'deleteData' => 'Delete',
        'disableData' => 'Disable',
        'selectData' => 'SelectObject',
    ];
    private function captureOriginalQueryFromReferer(): ?string
    {
        // 1) get the Referer (or use url()->previous())
        $referer = request()->headers->get('referer')
                 ?: url()->previous();

        // 2) if we got something, parse out the "foo=bar&baz=qux" part
        if ($referer) {
            return parse_url($referer, PHP_URL_QUERY);
        }

        return null;
    }


    public function configure(): void
    {
        if (empty($this->baseRoute)) {
            $this->baseRoute = Route::currentRouteName();
        }
        $this->appCode = Session::get('app_code', '');

        $this->route = ConfigMenu::getRoute($this->baseRoute);
        $this->baseRenderRoute = strtolower($this->route);

        // Convert base route to URL segments

        $fullUrl = str_replace('.', '/', $this->baseRoute);
        $this->menu_link = ConfigMenu::getFullPathLink($fullUrl);
        $this->langBasePath = str_replace('.', '/', $this->baseRenderRoute);

        if (!empty($this->customRoute)) {
            $this->langBasePath = str_replace('.', '/', $this->customRoute) . '/index';
        } else {
            $this->langBasePath = str_replace('.', '/', $this->baseRenderRoute) . '/index';
        }
        $this->permissions = Session::get($this->permissionSessionKey, []);
        $this->initialQueryString = $this->captureOriginalQueryFromReferer();
        $this->setPrimaryKey('id');
        $this->setTableAttributes([
            'class' => 'data-table',
        ]);

        $this->setTheadAttributes([
            'class' => 'data-table-header',
        ]);

        $this->setTbodyAttributes([
            'class' => 'data-table-body',
        ]);
        $this->setTdAttributes(function (Column $column, $row, $columnIndex, $rowIndex) {
            if ($column->isField('deleted_at')) {
                return [
                    'class' => 'text-center',
                ];
            }
            return [];
        });
        $this->setConfigurableAreas([
            'toolbar-left-start' => [
                'layout.customs.buttons.create',
                [
                    'route' => $this->cleanBaseRoute($this->baseRoute) . '.Detail',
                    'permissions' => $this->permissions,
                    'isComponent' => $this->isComponent,
                ],
            ],
        ]);
        $this->setFilterPillsDisabled();
        $this->setSortingPillsDisabled();
        $this->setFilterLayout('slide-down');
        $this->setFilterSlideDownDefaultStatusEnabled();
        $this->setQueryStringStatusForSort(true);
        $this->setSingleSortingDisabled();
        $this->setQueryStringStatus(true);
    }

    private function cleanBaseRoute($route)
    {
        return Str::endsWith($route, '.Detail') ? Str::replaceLast('.Detail', '', $route) : $route;
    }


    public function viewData($id)
    {
        $route = $this->customRoute
            ? str_replace('/', '.', $this->customRoute) . '.Detail'
            : $this->baseRoute . '.Detail';

        return $this->redirectDetail($id, 'View', $route);
    }

    public function editData($id)
    {
        $route = $this->customRoute
            ? str_replace('/', '.', $this->customRoute) . '.Detail'
            : $this->baseRoute . '.Detail';
        return $this->redirectDetail($id, 'Edit', $route);
    }

    /**
     * Redirect to a .Detail route with action, objectId, AND
     * preserve the entire current query string.
     */
    private function redirectDetail(string $id, string $action, string $routeName)
    {
        // 1) build the named‐route URL
        $url = route($routeName, [
            'action'   => encryptWithSessionKey($action),
            'objectId' => encryptWithSessionKey($id),
        ]);

        // 2) grab raw query string, e.g. "TYPE=C&foo=bar"
        if (! empty($this->initialQueryString)) {
            $url .= '?' . $this->initialQueryString;
        }
        // 3) redirect to the fully assembled URL
        return redirect()->to($url);
    }

    public function SelectObject($id)
    {
        $this->object = $this->model::findOrFail($id);
    }


    public function Disable()
    {
        try {
            $this->object->status_code = Status::NONACTIVE;
            $this->object->save();
            $this->object->updateObject($this->object->version_number);
            $this->object->delete();

            $this->dispatch('success', __('generic.string.disable', ['object' => 'object']));
        } catch (Exception $e) {
            // Handle the exception

            $this->dispatch('error', __('generic.string.disable', ['object' => 'object', 'message' => $e->getMessage()]));
        }
        $this->dispatch('refreshData');
    }

    public function trans($key)
    {
        $fullKey = $this->langBasePath . '.' . $key;
        $translation = __($fullKey);
        if ($translation === $fullKey) {
            return $key;
        } else {
            return $translation;
        }
    }

    public function getPermission($customRoute)
    {
        $this->permissions = ConfigRight::getPermissionsByMenu($customRoute ? $customRoute : $this->menu_link);
    }

    public function createTextFilter($name, $field, $placeholder, $filterCallback)
    {
        return TextFilter::make($name, $field)
            ->config([
                'placeholder' => $placeholder,
                'maxlength' => '500',
            ])
            ->filter($filterCallback)
            ->setWireLive();
    }

    protected function notify($type, $message)
    {
        $this->dispatch('notify-swal', [
            'type' => $type,
            'message' => $message,
        ]);
    }
    // public function bulkActions(): array
    // {
    //     return [
    //         'export' => 'Export excel',
    //     ];
    // }

    // public function export()
    // {
    //     // Start query
    //     $query = $this->model::query();

    //     // Check and apply filters if any
    //     if ($filters = $this->getFilters()) {
    //         foreach ($filters as $filter => $value) {
    //             // Apply the filter to the query. This might require custom logic depending on how your filters are set up.
    //             // Example:
    //             // if ($filter === 'status' && $value) {
    //             //     $query->where('status', $value);
    //             // }
    //         }
    //     }

    //     // You may need to modify this part to ensure $data contains the results you want to export
    //     $data = $query->get();

    //     // Define the filename based on the model's basename and the current timestamp
    //     $filename = class_basename($this->model) . '-' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    //     // Return the Excel download response
    //     return Excel::download(new GenericExport($data), $filename);
    // }

}

