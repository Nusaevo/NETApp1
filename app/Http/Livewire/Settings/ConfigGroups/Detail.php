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

    public function refreshUser()
    {
        $usersData = ConfigUser::GetActiveData();

        $this->users = $usersData->map(function ($user) {
            return [
                'label' => $user->id . ' - ' . $user->name,
                'value' => $user->id,
            ];
        })->toArray();

        $this->inputs['user_id'] = null;
    }

    protected function populateDropdowns()
    {
        $this->refreshApplication();
        $this->refreshUser();
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
            'inputs.user_id' =>  'required',
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
        'inputs.user_id'      => 'User',
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
        }elseif ($this->actionValue == 'Edit') {
            $this->VersioNumber = $this->object->version_number ?? null;
        }
    }

    function prepareTrusteeString($permissions) {
        $trustee = '';
        $trustee .= $permissions['create'] ? 'C' : '';
        $trustee .= $permissions['read'] ? 'R' : '';
        $trustee .= $permissions['update'] ? 'U' : '';
        $trustee .= $permissions['delete'] ? 'D' : '';
        return $trustee;
    }

    public function saveConfigRights()
    {
        $groupId = $this->object->id;

        $existingMenuIds = ConfigRight::where('group_id', $groupId)->pluck('menu_id')->toArray();

        $selectedMenuIds = array_keys($this->selectedMenus);

        $menusToDelete = array_diff($existingMenuIds, $selectedMenuIds);

        ConfigRight::where('group_id', $groupId)
            ->whereIn('menu_id', $menusToDelete)
            ->delete();
        $group = ConfigGroup::find($groupId);
        foreach ($this->selectedMenus as $menuId => $permissions) {
            $trustee = $this->prepareTrusteeString($permissions);
            $configRight = ConfigRight::where('group_id', $groupId)
                                      ->where('menu_id', $menuId)
                                      ->first();

            $menu = ConfigMenu::find($menuId);
            if ($configRight) {
                $configRight->update([
                    'trustee' => $trustee,
                ]);
            } else {
                ConfigRight::create([
                    'group_id' => $groupId,
                    'menu_id' => $menuId,
                    'group_code' => $group->code ?? '',
                    'menu_code' => $menu->code ?? '',
                    'trustee' => $trustee,
                ]);
            }
        }
    }

    public function validateApplicationUsers()
    {
        // Start building the query
        $query = ConfigGroup::query()
            ->where('user_id', $this->inputs['user_id'])
            ->where('app_id', $this->inputs['app_id']);

        // Check if $this->object is not null and its id property is also not null before adding the condition
        if ($this->object !== null && !is_null($this->object->id)) {
            $query->where('id', '!=', $this->object->id);
        }

        // Execute the query
        $configGroup = $query->first();
        // If a configGroup is found, it means a record exists that conflicts with the current validation rules
        if (!empty($configGroup) && ($configGroup->id != $this->object->id)) {
            throw new Exception("Group telah dibuat untuk aplikasi dan user ini, Pilihlah user/aplikasi lain");
        }
    }


    public function Save()
    {
        $this->validateForm();

        DB::beginTransaction();
        try {
            $this->validateApplicationUsers();
            $application = ConfigAppl::find($this->inputs['app_id']);

            $user = ConfigUser::find($this->inputs['user_id']);
            $this->inputs['app_code'] = $application->code;
            $this->inputs['user_code'] =  $user->code;
            if ($this->actionValue == 'Create') {
                $this->object = ConfigGroup::create($this->inputs);
            } elseif ($this->actionValue == 'Edit') {
                if ($this->object) {
                    $this->object->updateObject($this->VersioNumber);
                    $this->object->update($this->inputs);
                }
            }
            $userIds = array_keys(array_filter($this->selectedUserIds, function($value) {
                return $value['selected'] ?? false;
            }));
            $this->object->ConfigUser()->sync($userIds);
            $this->saveConfigRights();
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
