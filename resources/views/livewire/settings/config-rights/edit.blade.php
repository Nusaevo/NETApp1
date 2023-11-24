<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <div>
            <x-ui-button click-event="" type="Back" button-name="Back"/>
        </div>
    </div>

    <x-ui-page-card title="{{ $actionValue }} Group" status="{{ $status }}">
        <x-ui-tab-view id="myTab" class="nav nav-tabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">General</button>
            </li>
        </x-ui-tab-view>

        <form wire:submit.prevent="{{ $actionValue }}" class="form w-100">
            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-expandable-card id="UserCard" title="Group" :isOpen="true">

                        <x-ui-dropdown-select label="Application Code"
                        name="inputs.applications"
                        :options="$applications"
                        :selectedValue="$inputs['applications']"
                        required="true"
                        :action="$actionValue"
                        :onChanged="'loadGroupsAndMenus'"/>

                        <x-ui-dropdown-select label="Group Code"
                        name="inputs.groups"
                        :options="$groups"
                        :selectedValue="$inputs['groups']"
                        required="true"
                        :action="$actionValue" />

                        <x-ui-dropdown-select label="Menu Code"
                        name="inputs.menus"
                        :options="$menus"
                        :selectedValue="$inputs['menus']"
                        required="true"
                        :action="$actionValue" />

                        <x-ui-checklist label="Application Code"
                        label="Access :"
                        name="inputs.trustee"
                        :options="$trustee"
                        :action="$actionValue"/>

                        <x-ui-text-field label="Menu Seq" model="inputs.menu_seq" type="number" :action="$actionValue" required="true" placeHolder="Enter Menu Seq" />
                    </x-ui-expandable-card>
                </div>
            </x-ui-tab-view-content>
        </form>
        <div class="card-footer d-flex justify-content-end">
            @if ($actionValue !== 'Create')
                <div style="padding-right: 10px;">
                    @if ($status === 'Active')
                        <x-ui-button click-event="Disable" button-name="Disable" loading="true" :action="$actionValue" cssClass="btn-danger" iconPath="images/disable-icon.svg" />
                    @else
                        <x-ui-button click-event="Enable" button-name="Enable" loading="true" :action="$actionValue" cssClass="btn-success" iconPath="images/enable-icon.png" />
                    @endif
                </div>
            @endif

            <div>
                <x-ui-button click-event="{{ $actionValue }}" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="images/save-icon.png" />
            </div>
        </div>
    </x-ui-page-card>
</div>
