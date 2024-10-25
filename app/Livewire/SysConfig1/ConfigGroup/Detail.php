<?php

namespace App\Livewire\SysConfig1\ConfigGroup;

use App\Livewire\Component\BaseComponent;
use App\Models\SysConfig1\ConfigGroup;
use App\Models\SysConfig1\ConfigAppl;
use App\Models\SysConfig1\ConfigUser;
use App\Models\SysConfig1\ConfigRight;
use App\Services\SysConfig1\ConfigService;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Enums\Status;


class Detail extends BaseComponent
{

    #region Constant Variables
    public $inputs = [];
    public $applications;
    public $users;
    public $selectedMenus = [];
    public $selectedUserIds = [];
    protected $configService;


    public $rules = [
        'inputs.app_id' =>  'required',
        'inputs.descr' => 'required|string|min:1|max:100',
        'inputs.code' => 'required|string|min:1|max:100',
    ];
    protected $listeners = [
        'changeStatus'  => 'changeStatus',
        'selectedMenus' => 'selectedMenus',
        'selectedUserIds' => 'selectedUserIds'
    ];


    #endregion

    #region Populate Data methods

    protected function onPreRender()
    {
        $this->customValidationAttributes  = [
            'inputs'                => 'Input Group',
            'inputs.*'              => 'Input Group',
            'inputs.code'           => 'Group Code',
            'inputs.app_id'      => 'Application',
            'inputs.descr'      => 'Group Descr'
        ];
        $this->configService = new ConfigService();
        $this->applications = $this->configService->getActiveApplications();
        if ($this->isEditOrView()) {
            $this->object = ConfigGroup::withTrashed()->find($this->objectIdValue);
            $this->inputs = populateArrayFromModel($this->object);
            $this->applicationChanged();
            $this->populateSelectedRights();
            $this->populateSelectedUsers();
        }
    }

    public function onReset()
    {
        $this->reset('inputs');
        $this->inputs['app_id'] = null;
        $this->selectedMenus = [];
        $this->object = new ConfigGroup();
        $this->dispatch('renderRightTable');
        $this->dispatch('renderUserTable');
        $this->dispatch('applicationChanged', appId: $this->inputs['app_id'], selectedMenus: $this->selectedMenus);
    }


    public function render()
    {
        return view($this->renderRoute);
    }
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

    #endregion

    #region CRUD Methods

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
            dd($existingConfigGroups);
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
        if($this->object->isDuplicateCode())
        {
            $this->addError('inputs.code', __('generic.error.duplicate_code'));
            throw new Exception(__('generic.error.duplicate_code'));
        }
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

    #endregion

    #region Components Events

    public function applicationChanged()
    {
        $this->selectedMenus = [];
        $this->dispatch('applicationChanged', appId: $this->inputs['app_id'], selectedMenus: $this->selectedMenus);
    }

    public function selectedMenus($selectedMenus)
    {
        $this->selectedMenus = $selectedMenus;
    }

    public function selectedUserIds($selectedUserIds)
    {
        $this->selectedUserIds = $selectedUserIds;
    }

    #endregion





}
