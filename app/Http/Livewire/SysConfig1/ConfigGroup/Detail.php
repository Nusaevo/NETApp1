<?php

namespace App\Http\Livewire\SysConfig1\ConfigGroup;
use App\Http\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigGroup;
use App\Models\SysConfig1\ConfigAppl;
use App\Models\SysConfig1\ConfigUser;
use App\Models\SysConfig1\ConfigRight;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Lang;
use Exception;
use DB;
use App\Enums\Status;


class Detail extends BaseComponent
{
    public $inputs = [];
    public $applications;
    public $users;
    public $selectedMenus = [];
    public $selectedUserIds = [];

    protected function onPreRender()
    {

    }

    protected function onLoadForEdit()
    {
        $this->object = ConfigGroup::withTrashed()->find($this->objectIdValue);
        $this->inputs = populateArrayFromModel($this->object);
        $this->applicationChanged();
        $this->populateSelectedRights();
        $this->populateSelectedUsers();
    }

    public function refreshApplication()
    {
        $applicationsData = ConfigAppl::GetActiveData();
        $this->applications = $applicationsData->map(function ($data) {
            return [
                'label' => $data->code . ' - ' . $data->name,
                'value' => $data->id,
            ];
        })->toArray();
        $this->inputs['app_id'] = null;
    }

    public function applicationChanged()
    {
        $this->selectedMenus = [];
        $this->emit('applicationChanged', $this->inputs['app_id'], $this->selectedMenus);
    }

    // public function refreshUser()
    // {
    //     $usersData = ConfigUser::GetActiveData();

    //     $this->users = $usersData->map(function ($user) {
    //         return [
    //             'label' => $user->id . ' - ' . $user->name,
    //             'value' => $user->id,
    //         ];
    //     })->toArray();

    //     $this->inputs['user_id'] = null;
    // }

    protected function onPopulateDropdowns()
    {
        $this->refreshApplication();
    }

    public function render()
    {
        return view($this->renderRoute);
    }

    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'selectedMenus' => 'selectedMenus',
        'selectedUserIds' => 'selectedUserIds'
    ];

    public function populateSelectedRights()
    {
        if (!is_null($this->object->id)) {
            $configRights = ConfigRight::where('group_id', $this->object->id)->get();
            foreach ($configRights as $configRight) {
                $this->selectedMenus[$configRight->menu_id] = [
                    'selected' => true,
                    'menu_seq' => $configRight->menu_seq,
                    'create' => strpos($configRight->trustee, 'C') !== false,
                    'read' => strpos($configRight->trustee, 'R') !== false,
                    'update' => strpos($configRight->trustee, 'U') !== false,
                    'delete' => strpos($configRight->trustee, 'D') !== false,
                ];
            }
        }
    }

    public function populateSelectedUsers()
    {
        if (!is_null($this->object->id)) {
            $configGroup = ConfigGroup::with('ConfigUser')->find($this->object->id);

            if ($configGroup && $configGroup->ConfigUser) {
                foreach ($configGroup->ConfigUser as $user) {
                    $this->selectedUserIds[$user->id]['selected'] = true;
                }
            }
        }
    }

    public function selectedMenus($selectedMenus)
    {
        $this->selectedMenus = $selectedMenus;
    }

    public function selectedUserIds($selectedUserIds)
    {
        $this->selectedUserIds = $selectedUserIds;
    }

    protected function rules()
    {
        $rules = [
            'inputs.app_id' =>  'required',
            'inputs.descr' => 'required|string|min:1|max:100',
            // 'inputs.code' => [
            //     'required',
            //     'string',
            //     'min:1',
            //     'max:50',
            //     Rule::unique('config.config_groups', 'code')->ignore($this->object ? $this->object->id : null),
            // ],
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input Group',
        'inputs.*'              => 'Input Group',
        'inputs.code'           => 'Group Code',
        'inputs.app_id'      => 'Application',
        'inputs.descr'      => 'Group Descr'
    ];

    protected function onReset()
    {
        $this->reset('inputs');
        $this->selectedMenus = [];
        $this->object = new ConfigGroup();
    }

    public function validateApplicationUsers()
    {
        $appId = $this->inputs['app_id'];
        $userIds = array_keys(array_filter($this->selectedUserIds, function ($value) {
            return $value['selected'] ?? false;
        }));

        if ($this->object->isNew()) {
            $existingConfigGroups = ConfigGroup::where('app_id', $appId)
                ->whereHas('ConfigUser', function ($query) use ($userIds) {
                    $query->whereIn('user_id', $userIds);
                })
                ->get();
        } else {
            $existingConfigGroups = ConfigGroup::where('app_id', $appId)
                ->whereHas('ConfigUser', function ($query) use ($userIds) {
                    $query->whereIn('user_id', $userIds);
                })
                ->where('id', '!=', $this->object->id)
                ->get();
        }
        if ($existingConfigGroups->isNotEmpty()) {
            $existingUserIds = $existingConfigGroups->flatMap(function ($configGroup) {
                if ($configGroup->ConfigUser) {
                    return $configGroup->ConfigUser->pluck('id');
                }
            })->toArray();

            if (!empty($existingUserIds)) {
                $existingUserCodes = ConfigUser::whereIn('id', $existingUserIds)->pluck('code')->implode(', ');
                throw new Exception("Pengguna dengan loginID: $existingUserCodes sudah terdaftar pada group lain di aplikasi ini.");
            }
        }

    }

    public function onValidateAndSave()
    {
        $this->validateApplicationUsers();
        $application = ConfigAppl::find($this->inputs['app_id']);
        $this->inputs['app_code'] = $application->code;
        $this->object->fillAndSanitize($this->inputs);
        $this->object->save();
        $userIds = array_keys(array_filter($this->selectedUserIds, function ($value) {
            return $value['selected'] ?? false;
        }));

        $syncData = [];
        foreach ($userIds as $userId) {
            $configUser = ConfigUser::find($userId);
            $syncData[$userId] = [
                'group_code' =>  $this->object->code,
                'user_code' => $configUser->code,
                'status_code' => Status::ACTIVE,
            ];
        }
        $this->object->ConfigUser()->sync($syncData);
        ConfigRight::saveRights($this->object, $this->selectedMenus);
    }

    public function changeStatus()
    {
        $this->change();
    }
}
