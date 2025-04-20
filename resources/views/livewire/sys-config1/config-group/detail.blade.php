<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card isForm="true" title="{{ $actionValue }} {!! $menuName !!}" status="{{ $status }}">

        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>

        {{-- <x-ui-tab-view id="myTab" tabs="general,users"> </x-ui-tab-view> --}}
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <div class="row mt-4">
                    <div class="col-md-4">
                        <x-ui-card title="Main Information">
                            <x-ui-text-field label="Group Code" model="inputs.code" type="code" :action="$actionValue"
                                required="true" enabled="true" visible="true" />
                            <x-ui-dropdown-select label="Application" clickEvent="refreshApplication"
                                model="inputs.app_id" :options="$applications" required="true" :action="$actionValue"
                                onChanged="applicationChanged" />
                            <x-ui-text-field label="Group Descr" model="inputs.descr" type="text" :action="$actionValue"
                                required="true" placeHolder="Enter Group Name" visible="true" />
                        </x-ui-card>

                        @include('layout.customs.form-footer')
                    </div>
                    <div class="col-md-8">
                        <x-ui-card title="Group" height="400px">
                            @livewire('sys-config1.config-group.right-data-table', ['groupId' => $objectIdValue, 'appId' => $inputs['app_id'], 'selectedMenus' => $selectedMenus])
                        </x-ui-card>

                        <x-ui-card title="Users" height="400px">
                            @livewire('sys-config1.config-group.user-data-table', ['groupId' => $objectIdValue, 'selectedUserIds' => $selectedUserIds])
                        </x-ui-card>
                    </div>
                </div>
                {{-- <div class="tab-pane fade show" id="users" role="tabpanel" aria-labelledby="users-tab">
                    <x-ui-card>
                        @livewire('sys-config1.config-group.user-data-table', ['groupId' => $objectIdValue, 'selectedUserIds' => $selectedUserIds])
                    </x-ui-card>
                </div> --}}

                {{-- <div class="tab-pane fade show" id="rights" role="tabpanel" aria-labelledby="rights-tab">
                        <x-ui-expandable-card id="RightCard" title="Rights" :isOpen="true">
                            @livewire('sys-config1.config-groups.right-data-table', ['groupId' => $objectIdValue])
                        </x-ui-expandable-card>
                    </div> --}}
        </x-ui-tab-view-content>
    </x-ui-page-card>
</div>
