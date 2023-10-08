<div>
    <div>
        @include('layout.customs.notification')
    </div>
    <div>
        <a href="{{ route('config_groups.index') }}" class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2">
            <i class="bi bi-arrow-left-circle fs-2 me-2"></i> Kembali
        </a>
    </div>

     <x-ui-page-card title="{{ $action }} Config Group">
        <x-uitab-view id="myTab" class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">User</button>
            </li>
        </x-uitab-view>

        <x-uitab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <form wire:submit.prevent="{{ $action }}" class="form w-100">
                    <x-ui-expandable-card id="UserCard" title="User" :isOpen="true">
                        <x-ui-text-field label="Nama" model="inputs.name" type="text" :action="$action" :required="true" />
                        <x-ui-text-field label="Email" model="inputs.email" type="email" :action="$action" :required="true" />
                        <x-uidropdown-select label="Group Code"
                        name="inputs.group_codes"
                        :options="$group_codes"
                        :optionValueProperty="'group_code'"
                        :optionLabelProperty="'note1'"
                        :selectedValue="$inputs['group_codes']"
                        :required="true" />
                    </x-ui-expandable-card>
                    @if ($action !== 'View')
                    <div class="card-footer">
                        <x-ui-button click-event="{{ $action }}" button-name="Submit" />
                    </div>
                    @endif
                </form>
            </div>
            <div class="tab-pane fade" id="detail" role="tabpanel" aria-labelledby="detail-tab">
                <form wire:submit.prevent="{{ $action }}" class="form w-100">
                    <x-ui-expandable-card id="UserDetailCard" title="User Detail" :isOpen="true">
                        <x-ui-text-field label="Phone" model="inputs.phone" type="text" :action="$action" />
                        <x-ui-text-field label="Company" model="inputs.company" type="text" :action="$action" />

                        <x-uidropdown-select label="Language"
                        name="inputs.language"
                        :options="$languages"
                        :optionValueProperty="'id'"
                        :optionLabelProperty="'name'"
                        :selectedValue="$inputs['language']"
                        :required="true" />
                    </x-ui-expandable-card>
                    @if ($action !== 'View')
                    <div class="card-footer">
                        <x-ui-button click-event="{{ $action }}" button-name="Submit" />
                    </div>
                    @endif
                </form>
            </div>
            @if ($action !== 'View')
            <div class="tab-pane fade" id="credential" role="tabpanel" aria-labelledby="credential-tab">
                <form wire:submit.prevent="{{ $action }}" class="form w-100">
                    <x-ui-expandable-card id="UserPassword" title="User Credential" :isOpen="true">
                        <x-ui-text-field label="Password" model="inputs.passsword" type="password" :action="$action" />
                        <x-ui-text-field label="Confirm Password" model="inputs.newpasssword" type="password" :action="$action" />
                    </x-ui-expandable-card>
                    <div class="card-footer">
                        <x-ui-button click-event="{{ $action }}" button-name="Submit" />
                    </div>
                </form>
            </div>
            @endif
        </x-uitab-view-content>

    </x-ui-page-card>


    <div id="kt_content_container" class="container-xxl mb-5" wire:ignore>
        <div class="card shadow-sm">
            <div>
                <h3>{{ $action }} Config Group</h3>
            </div>
            <form wire:submit.prevent="{{ $action === 'Edit' ? 'update' : 'store' }}" class="form w-100">

                <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#config_group_general_tab" wire:ignore.self>
                    <h3 class="card-title">Config Group General Info</h3>
                    <div class="card-toolbar rotate-180">
                        <i class="bi bi-arrow-bar-down"></i>
                    </div>
                </div>

                <div id="config_group_general_tab" class="collapse show" wire:ignore.self>
                    <div class="card-body">
                        <div class="mb-10">
                            <label class="required form-label">Appl Code</label>
                            <input wire:model.defer="inputs.appl_code" type="text" class="form-control @error('configGroup.appl_code') is-invalid @enderror" {{ $action === 'View' ? 'disabled' : '' }}/>
                            @error('configGroup.appl_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Group Code</label>
                            <input wire:model.defer="inputs.group_code" type="text" class="form-control @error('configGroup.group_code') is-invalid @enderror" {{ $action === 'View' ? 'disabled' : '' }}/>
                            @error('configGroup.group_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">User Code</label>
                            <input wire:model.defer="inputs.user_code" type="text" class="form-control @error('configGroup.user_code') is-invalid @enderror" {{ $action === 'View' ? 'disabled' : '' }}/>
                            @error('configGroup.user_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Note 1</label>
                            <input wire:model.defer="inputs.note1" type="text" class="form-control @error('configGroup.note1') is-invalid @enderror" {{ $action === 'View' ? 'disabled' : '' }}/>
                            @error('configGroup.note1') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Status Code</label>
                            <input wire:model.defer="inputs.status_code" type="text" class="form-control @error('configGroup.status_code') is-invalid @enderror" {{ $action === 'View' ? 'disabled' : '' }}/>
                            @error('configGroup.status_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Is Active</label>
                            <input wire:model.defer="inputs.is_active" type="text" class="form-control @error('configGroup.is_active') is-invalid @enderror" {{ $action === 'View' ? 'disabled' : '' }}/>
                            @error('configGroup.is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    @if ($action === 'Edit')
                        <button wire:click="update" class="btn btn-primary">Update</button>
                    @elseif ($action === 'Create')
                        <button wire:click="store" class="btn btn-success">Create</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
