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
use App\Models\Util\GenericExport; // You'll create this export class next


abstract class BaseDataTableComponent extends DataTableComponent
{
    public $object;
    public $baseRoute;
    public $route;
    public $customRoute;

    public function __construct()
    {
        parent::__construct();
        $this->baseRoute = Str::replace('.', '/', Route::currentRouteName());
        $this->route .= Route::currentRouteName();
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
        $route = !empty($this->customRoute) ? $this->customRoute . ".Detail" : $this->route . ".Detail";
        return $this->redirectDetail($id, 'View', $route);
    }

    public function editData($id)
    {
        $route = !empty($this->customRoute) ? $this->customRoute . ".Detail" : $this->route . ".Detail";
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
                'message' => Lang::get('generic.success.disable', ['object' => "object"])
            ]);
        } catch (Exception $e) {
            // Handle the exception
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.disable', ['object' => "object", 'message' => $e->getMessage()])
            ]);
        }
        $this->emit('refreshData');
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
