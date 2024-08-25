<?php

namespace App\Livewire\Component;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Exception;
use App\Enums\Status;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\SysConfig1\ConfigRight;
use App\Models\SysConfig1\ConfigMenu;
use App\Models\Util\GenericExport;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

abstract class BaseDataTableComponent extends DataTableComponent
{
    public $object;
    public $baseRoute;
    public $route;
    public $customRoute;
    public $langBasePath;

    public $baseRenderRoute;
    public $renderRoute;
    public $permissions = ['create' => false, 'read' => false, 'update' => false, 'delete' => false];
    public $menu_link;


    abstract public function columns(): array;

    protected $listeners = [
        'refreshData' => 'render',
        'viewData'  => 'View',
        'editData'  => 'Edit',
        'deleteData'  => 'Delete',
        'disableData'  => 'Disable',
        'selectData'  => 'SelectObject',
    ];

    public function configure(): void
    {
        if (empty($this->baseRoute)) {
            $this->baseRoute = Route::currentRouteName();
        }

        $this->route = ConfigMenu::getRoute($this->baseRoute);
        $this->baseRenderRoute = strtolower($this->route);
        $this->renderRoute = 'livewire/' . $this->baseRenderRoute;

        // Convert base route to URL segments

        $fullUrl = str_replace('.', '/', $this->baseRoute);
        $this->menu_link = ConfigMenu::getFullPathLink($fullUrl);
        $this->langBasePath  = str_replace('.', '/', $this->baseRenderRoute);

        if (!empty($this->customRoute)) {
            $this->langBasePath = str_replace('.', '/', $this->customRoute) . "/index";
        } else {
            $this->langBasePath  = str_replace('.', '/', $this->baseRenderRoute)."/index";
        }
        $this->permissions = ConfigRight::getPermissionsByMenu($this->menu_link);

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
        $this->setTdAttributes(function(Column $column, $row, $columnIndex, $rowIndex) {
            if ($column->isField('deleted_at')) {
              return [
                'class' => 'text-center',
              ];
            }
            return [];
        });
        $this->setConfigurableAreas([
            'toolbar-left-start' =>  ['layout.customs.buttons.create', [
                'route' => $this->baseRoute.".Detail", 'permissions' => $this->permissions
            ],]
        ]);

        $this->setFilterPillsDisabled();
        $this->setSortingPillsDisabled();
        $this->setFilterLayout('slide-down');
        $this->setFilterSlideDownDefaultStatusEnabled();
        $this->setSingleSortingDisabled();
    }

    public function viewData($id)
    {
        $route = !empty($this->customRoute) ? str_replace('/', '.', $this->customRoute) . ".Detail" : $this->baseRoute . ".Detail";
        return $this->redirectDetail($id, 'View', $route);
    }

    public function editData($id)
    {
        $route = !empty($this->customRoute) ? str_replace('/', '.', $this->customRoute) . ".Detail" : $this->baseRoute . ".Detail";
        return $this->redirectDetail($id, 'Edit', $route);
    }

    public function SelectObject($id)
    {
        $this->object = $this->model::findOrFail($id);
    }

    private function redirectDetail($id, $action, $route)
    {
        return redirect()->route($route, [
            'action' => encryptWithSessionKey($action),
            'objectId' => encryptWithSessionKey($id),
        ]);
    }

    public function Disable()
    {
        try {
            $this->object->status_code = Status::NONACTIVE;
            $this->object->save();
            $this->object->updateObject($this->object->version_number);
            $this->object->delete();

            $this->notify('success', __('generic.string.disable', ['object' => "object"]));
        } catch (Exception $e) {
            // Handle the exception

            $this->notify('error', __('generic.string.disable', ['object' => "object", 'message' => $e->getMessage()]));
        }
        $this->dispatch('refreshData');
    }

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
    //     $filename = class_basename($this->model) . '-export-' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    //     // Return the Excel download response
    //     return Excel::download(new GenericExport($data), $filename);
    // }

}
