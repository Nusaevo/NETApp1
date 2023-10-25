<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <a href="{{ route('config_applications.index') }}" class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2">
            <i class="bi bi-arrow-left-circle fs-2 me-2"></i> Back
        </a>
    </div>

    <x-ui-page-card title="{{ $action }} Application" status="{{ $status }}">
        <x-uitab-view id="myTab" class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">General</button>
            </li>
        </x-uitab-view>

        <form wire:submit.prevent="{{ $action }}" class="form w-100">
            <x-uitab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-expandable-card id="UserCard" title="Application" :isOpen="true">
                        @if ($action == 'Create')
                            <x-ui-text-field label="Application Code" model="inputs.code" type="text" :action="$action" :required="true" placeHolder="Enter Application Code (e.g., app01)" />
                        @else
                            <x-ui-text-field label="Application Code" model="inputs.code" type="text" :action="$action" :required="true" :enabled="false" placeHolder="Enter Application Code (e.g., app01)" />
                        @endif

                        <x-ui-text-field label="Nama" model="inputs.name" type="text" :action="$action" :required="true" placeHolder="Enter Name (e.g., POS Indo)" />
                        <x-ui-text-field label="Description" model="inputs.descr" type="textarea" :action="$action" :required="true" placeHolder="Enter Description (e.g., Application's information)" />
                        <x-ui-text-field label="Version" model="inputs.version" type="text" :action="$action" placeHolder="Enter Version (optional)" />
                    </x-ui-expandable-card>
                </div>
            </x-uitab-view-content>
        </form>
        <div class="card-footer d-flex justify-content-end">
            @if ($action !== 'Create')
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
