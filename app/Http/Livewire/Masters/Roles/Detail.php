<?php

namespace App\Http\Livewire\Masters\Roles;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Detail extends Component
{
    public $role;
    public $permissions = [];

    public function mount($role_id)
    {
        $this->role = Role::findOrfail($role_id);
        $permissions = Permission::all();
        foreach($permissions as $permission){
            $this->permissions[$permission->id]['checked'] = $this->role->hasPermissionTo($permission->name);
            $this->permissions[$permission->id]['name'] = $permission->name;
            $this->permissions[$permission->id]['desc_id'] = $permission->desc_id;
            $this->permissions[$permission->id]['desc_en'] = $permission->desc_en;
            $this->permissions[$permission->id]['module'] = $permission->module;
        }
    }

    public function render()
    {
        return view('livewire.masters.roles.detail');
    }

    public function store()
    {
        $temp_selected_permission = [];
        foreach($this->permissions as $input_permission){
            if($input_permission['checked']){
                $temp_selected_permission[] = $input_permission['name'];
            }
        }
        $this->role->syncPermissions($temp_selected_permission);
        $this->dispatchBrowserEvent('notify-swal',['type' => 'success','title' => 'Berhasil','message' =>  "Data Izin berhasil disimpan untuk peran {$this->role->name}."]);
    }
}
