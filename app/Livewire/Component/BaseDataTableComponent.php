<?php

namespace App\Livewire\Component;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use App\Models\SysConfig1\{ConfigRight, ConfigMenu};
use Rappasoft\LaravelLivewireTables\Views\Column;
use Exception;
use App\Enums\Status;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Util\GenericExport;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Arr;
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
        $referer = request()->headers->get('referer') ?: url()->previous();

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

        // Apply custom CSS classes for modern styling
        $this->setTableWrapperAttributes([
            'class' => 'datatable-wrapper',
        ]);

        $this->setTableAttributes([
            'class' => 'table data-table',
        ]);

        $this->setTheadAttributes([
            'class' => 'data-table-header',
        ]);

        $this->setTbodyAttributes([
            'class' => 'data-table-body',
        ]);

        // Custom toolbar classes
        $this->setToolbarAttributes([
            'class' => 'datatable-toolbar',
        ]);

        // Custom search attributes
        $this->setSearchFieldAttributes([
            'class' => 'form-control datatable-search-input',
            'placeholder' => 'Search records...',
        ]);
        $this->setTdAttributes(function (Column $column, $row, $columnIndex, $rowIndex) {
            return [
                'class' => 'align-middle',
            ];
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
        //$this->setSingleSortingDisabled();

        // Enable single sorting for TrdTire apps
        $this->setSingleSortingStatus(true);

        $this->setQueryStringStatus(true);
    }

    private function cleanBaseRoute($route)
    {
        return Str::endsWith($route, '.Detail') ? Str::replaceLast('.Detail', '', $route) : $route;
    }

    public function viewData($id)
    {
        $route = $this->customRoute ? str_replace('/', '.', $this->customRoute) . '.Detail' : $this->baseRoute . '.Detail';

        return $this->redirectDetail($id, 'View', $route);
    }

    public function editData($id)
    {
        $route = $this->customRoute ? str_replace('/', '.', $this->customRoute) . '.Detail' : $this->baseRoute . '.Detail';
        return $this->redirectDetail($id, 'Edit', $route);
    }
    private function redirectDetail(string $id, string $action, string $routeName)
    {
        // 1) build the named‑route URL
        $url = route($routeName, [
            'action' => encryptWithSessionKey($action),
            'objectId' => encryptWithSessionKey($id),
        ]);

        // 2) parse our stored QS into an array
        if (!empty($this->initialQueryString)) {
            parse_str($this->initialQueryString, $allParams);

            // 3) create a *new* filtered array without 'table-filter'
            $filtered = Arr::except($allParams, ['table-filters','table-sorts','page','tableperPage']);

            // 4) if there are any left, re‑append them
            if (!empty($filtered)) {
                $url .= '?' . http_build_query($filtered);
            }
        }

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
        return TextFilter::make($name)
            ->filter(function(\Illuminate\Database\Eloquent\Builder $builder, string $value) use ($filterCallback) {
                return $filterCallback($builder, $value);
            });
    }

    protected function notify($type, $message)
    {
        $this->dispatch('notify-swal', [
            'type' => $type,
            'message' => $message,
        ]);
    }

    /**
     * Determine if reorder button should be shown
     * Override this method in child classes to enable reordering
     */
    public function showReorderButton(): bool
    {
        return false;
    }
    /**
     * Determine if actions should be shown in toolbar left
     * Override this method in child classes if needed
     */
    public function showActionsInToolbarLeft(): bool
    {
        return false;
    }

    /**
     * Determine if actions should be shown in toolbar right
     * Override this method in child classes if needed
     */
    public function showActionsInToolbarRight(): bool
    {
        return false;
    }

    /**
     * Determine if search field should be shown
     * Override this method in child classes if needed
     */
    public function showSearchField(): bool
    {
        return !$this->searchIsDisabled();
    }

    /**
     * Determine if filters button should be shown
     * Override this method in child classes if needed
     */
    public function showFiltersButton(): bool
    {
        return $this->filtersAreEnabled() && $this->hasFilters();
    }

    /**
     * Determine if bulk actions dropdown should be shown
     * Override this method in child classes if needed
     */
    public function showBulkActionsDropdownAlpine(): bool
    {
        return $this->bulkActionsAreEnabled();
    }

    /**
     * Determine if pagination dropdown should be shown
     * Override this method in child classes if needed
     */
    public function showPaginationDropdown(): bool
    {
        return $this->paginationIsEnabled();
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
