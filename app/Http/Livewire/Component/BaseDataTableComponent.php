<?php

namespace App\Http\Livewire\Component;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Lang;
use Exception;
use App\Enums\Status;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\SysConfig1\ConfigRight;
use App\Models\Util\GenericExport;

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

    public function __construct()
    {
        parent::__construct();
        $this->route = Route::currentRouteName();
        $this->baseRoute = Str::replace('.', '/', $this->route);
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
        $this->langBasePath  = str_replace('.', '/', $this->baseRenderRoute);

        if (!empty($this->customRoute)) {
            $this->langBasePath = str_replace('.', '/', $this->customRoute) . "/index";
        } else {
            $this->langBasePath  = str_replace('.', '/', $this->baseRenderRoute)."/index";
        }
    }

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
    }

    abstract public function columns(): array;

    public function viewData($id)
    {
        $route = !empty($this->customRoute) ? str_replace('/', '.', $this->customRoute) . ".Detail" : $this->route . ".Detail";
        return $this->redirectDetail($id, 'View', $route);
    }

    public function editData($id)
    {
        $route = !empty($this->customRoute) ? str_replace('/', '.', $this->customRoute) . ".Detail" : $this->route . ".Detail";
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
            $this->object->status_code = Status::DEACTIVATED;
            $this->object->save();
            $this->object->updateObject($this->object->version_number);
            $this->object->delete();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.string.disable', ['object' => "object"])
            ]);
        } catch (Exception $e) {
            // Handle the exception
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.string.disable', ['object' => "object", 'message' => $e->getMessage()])
            ]);
        }
        $this->emit('refreshData');
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

    public function getPermission($customRoute)
    {
        $this->permissions = ConfigRight::getPermissionsByMenu($customRoute ? $customRoute : $this->baseRoute);
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
