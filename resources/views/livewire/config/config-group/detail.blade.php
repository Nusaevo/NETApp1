<div>
    <div>
        <x-ui-button click-event="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card title="{{ $actionValue }} Group" status="{{ $status }}">

        <x-ui-tab-view id="myTab" tabs="general,users"> </x-ui-tab-view>

        <form wire:submit.prevent="{{ $actionValue }}" class="form w-100">
            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-expandable-card id="GroupCard" title="Group" :isOpen="true">
                        <x-ui-text-field label="Group Code" model="inputs.code" type="code" :action="$actionValue" required="true" enabled="true" placeHolder="" visible="true" span="Full" />
                        <x-ui-dropdown-select label="Application" click-event="refreshApplication" model="inputs.app_id" :options="$applications" :selectedValue="$inputs['app_id']" required="true" :action="$actionValue" span="Full" onChanged="applicationChanged" />
                        <x-ui-text-field label="Group Descr" model="inputs.descr" type="text" :action="$actionValue" required="true" placeHolder="Enter Group Name" visible="true" span="Full" />
                        {{-- <x-ui-text-field-search label="User" click-event="refreshUser" model="inputs.user_id" name="User" placeHolder="Search User" :options="$users" :selectedValue="$inputs['user_id']" :action="$actionValue" required="true" span="Full" /> --}}

                        @livewire('config.config-group.right-data-table', ['groupId' => $objectIdValue,'appId' => $inputs['app_id'], 'selectedMenus' => $selectedMenus])

                    </x-ui-expandable-card>
                </div>

                <div class="tab-pane fade show" id="users" role="tabpanel" aria-labelledby="users-tab">
                    <x-ui-expandable-card id="UserCard" title="Users" :isOpen="true">
                        @livewire('config.config-group.user-data-table', ['groupId' => $objectIdValue, 'selectedUserIds' => $selectedUserIds])
                    </x-ui-expandable-card>
                </div>

                {{-- <div class="tab-pane fade show" id="rights" role="tabpanel" aria-labelledby="rights-tab">
                        <x-ui-expandable-card id="RightCard" title="Rights" :isOpen="true">
                            @livewire('config.config-groups.right-data-table', ['groupId' => $objectIdValue])
                        </x-ui-expandable-card>
                    </div> --}}
            </x-ui-tab-view-content>
        </form>
        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
