<?php

namespace App\Livewire\SysConfig1\ConfigUser;

use App\Livewire\Component\BaseDataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\{Column, Filters\SelectFilter, Filters\TextFilter};
use App\Models\SysConfig1\ConfigGroup;
use App\Enums\Status;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Exception;

class GroupDataTable extends BaseDataTableComponent
{
    protected $model = ConfigGroup::class;
    public $userID;

    public function mount($userID = null): void
    {
        $this->customRoute = "SysConfig1.ConfigGroup";
        $this->getPermission($this->customRoute);
        $this->userID = $userID;
    }

    public function builder(): Builder
    {
        return ConfigGroup::query()
            ->with('configAppl')
            ->whereHas('configGroupUser', function ($query) {
                $query->where('user_id', $this->userID);
            })
            ->join('config_appls', 'config_groups.app_id', '=', 'config_appls.id')
            ->orderBy('config_appls.name', 'ASC')
            ->orderBy('config_groups.descr', 'ASC');
    }


    public function columns(): array
    {
        return [
            Column::make("Application", "ConfigAppl.name")
                ->searchable()
                ->sortable(),
            Column::make("Group Name", "descr")
                ->searchable()
                ->sortable(),
        ];
    }


}
