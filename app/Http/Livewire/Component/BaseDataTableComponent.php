<?php

namespace App\Http\Livewire\Component;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Lang;
use Exception;
use App\Enums\Status;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

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
        $this->route .= Route::currentRouteName().'.Detail';
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
        if (!empty($this->customRoute)) {
            return redirect()->route($this->customRoute, ['action' => encryptWithSessionKey('View'), 'objectId' => encryptWithSessionKey($id)]);
        }

        return redirect()->route($this->route, ['action' => encryptWithSessionKey('View'), 'objectId' => encryptWithSessionKey($id)]);
    }


    public function editData($id)
    {
        if (!empty($this->customRoute)) {
            return redirect()->route($this->customRoute, ['action' => encryptWithSessionKey('Edit'), 'objectId' => encryptWithSessionKey($id)]);
        }
        return redirect()->route($this->route, ['action' => encryptWithSessionKey('Edit'), 'objectId' => encryptWithSessionKey($id)]);
    }

    public function SelectObject($id)
    {
        $this->object = $this->model::findOrFail($id);
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
}
