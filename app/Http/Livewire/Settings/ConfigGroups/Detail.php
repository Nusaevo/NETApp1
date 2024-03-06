<?php

namespace App\Http\Livewire\Settings\ConfigGroups;
use Livewire\Component;
use App\Models\Settings\ConfigGroup;
use App\Models\Settings\ConfigAppl;
use App\Models\Settings\ConfigUser;
use App\Models\Settings\ConfigMenu;
use App\Models\Settings\ConfigRight;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Crypt;
use Lang;
use Exception;
use DB;


class Detail extends Component
{
    public $object;
    public $VersioNumber;
    public $actionValue = 'Create';
    public $objectIdValue;
    public $inputs = [];
    public $status = '';
    public $applications;
    public $users;
    public $selectedMenus = [];
    public $selectedUserIds = [];
    public function mount($action, $objectId = null)
    {
        $this->actionValue = decryptWithSessionKey($action);
        $this->populateDropdowns();
        if (($this->actionValue === 'Edit' || $this->actionValue === 'View') && $objectId) {
            $this->objectIdValue = decryptWithSessionKey($objectId);
            $this->object = ConfigGroup::withTrashed()->find($this->objectIdValue);
            $this->status = $this->object->deleted_at ? 'Non-Active' : 'Active';
            $this->VersioNumber = $this->object->version_number;
            $this->inputs = populateArrayFromModel($this->object);
            $this->applicationChanged();
            $this->populateSelectedRights();
            $this->populateSelectedUsers();
        } else {
            $this->resetForm();
        }
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

    protected function populateDropdowns()
    {
        $this->refreshApplication();
        // $this->refreshUser();
    }

    public function render()
    {
        return view('livewire.settings.config-groups.edit');
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
            'inputs.name' => 'required|string|min:1|max:100',
            'inputs.code' => [
                'required',
                'string',
                'min:1',
                'max:50',
                Rule::unique('config.config_groups', 'code')->ignore($this->object ? $this->object->id : null),
            ],
        ];
        return $rules;
    }

    protected $validationAttributes = [
        'inputs'                => 'Input Group',
        'inputs.*'              => 'Input Group',
        'inputs.code'           => 'Group Code',
        'inputs.app_id'      => 'Application',
        'inputs.name'      => 'Group Name'
    ];

    protected function validateForm()
    {
        try {
            $this->validate();
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.create', ['object' => "object", 'message' => $e->getMessage()])
            ]);
            throw $e;
        }
    }

    protected function resetForm()
    {
        if ($this->actionValue == 'Create') {
            $this->reset('inputs');
            $this->populateDropdowns();
            $this->selectedMenus = [];
            $this->object = new ConfigGroup();
        }elseif ($this->actionValue == 'Edit') {
            $this->VersioNumber = $this->object->version_number ?? null;
        }
    }

    public function validateApplicationUsers()
    {
        $appId = $this->inputs['app_id'];
        $userIds = array_keys(array_filter($this->selectedUserIds, function ($value) {
            return $value['selected'] ?? false;
        }));
    
        // If the object is new, skip the database check
        if ($this->object->isNew()) {
            // Get all existing config groups for the given application ID
            $existingConfigGroups = ConfigGroup::where('app_id', $appId)
                ->whereHas('ConfigUser', function ($query) use ($userIds) {
                    $query->whereIn('user_id', $userIds);
                })
                ->get();
        } else {
            // Check if any of the user IDs are already associated with the given application ID
            $existingConfigGroups = ConfigGroup::where('app_id', $appId)
                ->whereHas('ConfigUser', function ($query) use ($userIds) {
                    $query->whereIn('user_id', $userIds);
                })
                ->where('id', '!=', $this->object->id) // Exclude the current object
                ->get();
        }
        if ($existingConfigGroups->isNotEmpty()) {
            $existingUserIds = $existingConfigGroups->flatMap(function ($configGroup) {
                if ($configGroup->ConfigUser) { // Check if users relationship is not null
                    return $configGroup->ConfigUser->pluck('id');
                } 
            })->toArray();
        
            if (!empty($existingUserIds)) {
                $existingUserCodes = ConfigUser::whereIn('id', $existingUserIds)->pluck('code')->implode(', ');
                throw new Exception("Pengguna dengan loginID: $existingUserCodes sudah terdaftar.");
            }
        }
        
    }

    public function Save()
    {
        $this->validateForm();

        DB::beginTransaction();
        try {
            $this->validateApplicationUsers();
            $application = ConfigAppl::find($this->inputs['app_id']);
            $this->inputs['app_code'] = $application->code;
            if ($this->object) {
                $this->object->updateObject($this->VersioNumber);
                $this->object->fill($this->inputs);
                $this->object->save();
            }
            $userIds = array_keys(array_filter($this->selectedUserIds, function($value) {
                return $value['selected'] ?? false;
            }));

            $this->object->ConfigUser()->sync($userIds);
            ConfigRight::saveRights($this->object->id, $this->selectedMenus, $this->object->code);

            DB::commit();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get('generic.success.save', ['object' => $this->inputs['name']])
            ]);
            $this->resetForm();
        } catch (Exception $e) {
            DB::rollBack();
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.save', ['object' => $this->inputs['name'], 'message' => $e->getMessage()])
            ]);
        }
    }


    public function changeStatus()
    {
        try {
            $this->object->updateObject($this->VersioNumber);

            if ($this->object->deleted_at) {
                $this->object->deleted_at = null;
                $messageKey = 'generic.success.enable';
            } else {
                $this->object->delete();
                $messageKey = 'generic.success.disable';
            }

            $this->object->save();

            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'success',
                'message' => Lang::get($messageKey, ['object' => $this->inputs['name']])
            ]);
        } catch (Exception $e) {
            $this->dispatchBrowserEvent('notify-swal', [
                'type' => 'error',
                'message' => Lang::get('generic.error.' . ($this->object->deleted_at ? 'enable' : 'disable'), ['object' => $this->inputs['name'], 'message' => $e->getMessage()])
            ]);
        }

        $this->dispatchBrowserEvent('refresh');
    }
}
