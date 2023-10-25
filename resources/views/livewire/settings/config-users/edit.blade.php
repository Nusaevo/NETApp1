<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <a href="{{ route('config_users.index') }}" class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2">
            <i class="bi bi-arrow-left-circle fs-2 me-2"></i> Back
        </a>
    </div>

    <x-ui-page-card title="{{ $action }} User" status="{{ $status }}">
        <x-uitab-view id="myTab" class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">General</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="credential-tab" data-bs-toggle="tab" data-bs-target="#credential" type="button" role="tab" aria-controls="credential" aria-selected="false">Credential</button>
            </li>
        </x-uitab-view>

        <form wire:submit.prevent="{{ $action }}" class="form w-100">
            <x-uitab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-expandable-card id="UserCard" title="User" :isOpen="true">
                        @if ($action == 'Create')
                            <x-ui-text-field label="Login ID" model="inputs.code" type="text" :action="$action" :required="true" placeHolder="Enter Login ID (e.g., johndoe123)" />
                        @else
                            <x-ui-text-field label="Login ID" model="inputs.code" type="text" :action="$action" :required="true" :enabled="false" placeHolder="Enter Login ID" />
                        @endif

                        <x-ui-text-field label="Nama" model="inputs.name" type="text" :action="$action" :required="true" placeHolder="Enter Name (e.g., John Doe)" />
                        <x-ui-text-field label="Email" model="inputs.email" type="email" :action="$action" :required="true" placeHolder="Enter Email (e.g., johndoe@example.com)" />
                        <x-ui-text-field label="Phone" model="inputs.phone" type="text" :action="$action" placeHolder="Enter Phone (optional)" />
                        <x-ui-text-field label="Department" model="inputs.dept" type="text" :action="$action" placeHolder="Enter Department (optional)" />
                    </x-ui-expandable-card>
                </div>
                <div class="tab-pane fade" id="credential" role="tabpanel" aria-labelledby="credential-tab">
                    <x-ui-expandable-card id="UserPassword" title="User Credential" :isOpen="true">
                        @if ($action == 'Create')
                            <x-ui-text-field label="Password" model="inputs.password" type="password" :action="$action" placeHolder="Enter a secure password with at least 8 characters, including lowercase, uppercase, and numbers. (e.g., Password123)" :required="true" />
                        @else
                            <x-ui-text-field label="Password" model="inputs.password" type="password" :action="$action" placeHolder="Enter a secure password with at least 8 characters, including lowercase, uppercase, and numbers. (e.g., Password123)" />
                        @endif
                        <x-ui-text-field label="Confirm Password" model="inputs.newpassword" type="password" :action="$action" placeHolder="Enter same Password" />
                    </x-ui-expandable-card>
                </div>
            </x-uitab-view-content>
        </form>
        <div class="card-footer d-flex justify-content-end">
            @if ($action !== 'Create' && auth()->user()->id !== $user->id)
                <div style="padding-right: 10px;">
                    @if ($status === 'Active')
                        <x-ui-button click-event="Disable" button-name="Disable" :loading="true" :action="$action" cssClass="btn-danger" iconPath="images/disable-icon.svg" />
                    @else
                        <x-ui-button click-event="Enable" button-name="Enable" :loading="true" :action="$action" cssClass="btn-success" iconPath="images/enable-icon.png" />
                    @endif
                </div>
            @endif

            <div>
                <x-ui-button click-event="{{ $action }}" button-name="Save" :loading="true" :action="$action" cssClass="btn-primary" iconPath="images/save-icon.png" />
            </div>
        </div>
    </x-ui-page-card>
</div>
