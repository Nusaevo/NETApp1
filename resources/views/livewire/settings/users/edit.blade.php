<div>
    <div>
        @include('layout.customs.notification')
    </div>
    <div>
        <a href="{{ route('users.index') }}" class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2">
            <i class="bi bi-arrow-left-circle fs-2 me-2"></i> Kembali
        </a>
    </div>

    <x-ui-page-card title="{{ $action }} User">
        <x-uitab-view id="myTab" class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">General</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="detail-tab" data-bs-toggle="tab" data-bs-target="#detail" type="button" role="tab" aria-controls="detail" aria-selected="false">User Detail</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="credential-tab" data-bs-toggle="tab" data-bs-target="#credential" type="button" role="tab" aria-controls="credential" aria-selected="false">Credential</button>
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
                </form>
            </div>
            <div class="tab-pane fade" id="credential" role="tabpanel" aria-labelledby="credential-tab">
                <form wire:submit.prevent="{{ $action }}" class="form w-100">
                    <x-ui-expandable-card id="UserPassword" title="User Credential" :isOpen="true">
                        <x-ui-text-field label="Password" model="inputs.passsword" type="password" :action="$action" />
                        <x-ui-text-field label="Confirm Password" model="inputs.newpasssword" type="password" :action="$action" />
                    </x-ui-expandable-card>
                </form>
            </div>
        </x-uitab-view-content>

        @if ($action !== 'View')
        <div class="card-footer">
            <x-ui-button click-event="{{ $action }}" button-name="Submit" :loading="true" />
        </div>
        @endif
    </x-ui-page-card>
</div>
