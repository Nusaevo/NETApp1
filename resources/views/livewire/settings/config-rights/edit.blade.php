<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <a href="{{ route('config_rights.index') }}" class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2">
            <i class="bi bi-arrow-left-circle fs-2 me-2"></i> Back
        </a>
    </div>

    <x-ui-page-card title="{{ $action }} Group" status="{{ $status }}">
        <x-uitab-view id="myTab" class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">General</button>
            </li>
        </x-uitab-view>

        <form wire:submit.prevent="{{ $action }}" class="form w-100">
            <x-uitab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-expandable-card id="UserCard" title="Group" :isOpen="true">

                        <x-uidropdown-select label="Application Code"
                        name="inputs.applications"
                        :options="$applications"
                        :selectedValue="$inputs['applications']"
                        :required="true"
                        :action="$action"
                        :onChanged="'loadGroupsAndMenus'"/>

                        <x-uidropdown-select label="Group Code"
                        name="inputs.groups"
                        :options="$groups"
                        :selectedValue="$inputs['groups']"
                        :required="true"
                        :action="$action" />

                        <x-uidropdown-select label="Menu Code"
                        name="inputs.menus"
                        :options="$menus"
                        :selectedValue="$inputs['menus']"
                        :required="true"
                        :action="$action" />

                        <x-ui-checklist label="Application Code"
                        label="Access :"
                        name="inputs.trustee"
                        :options="$trustee"
                        :action="$action"/>

                        <x-ui-text-field label="Menu Seq" model="inputs.menu_seq" type="number" :action="$action" :required="true" placeHolder="Enter Menu Seq" />
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
