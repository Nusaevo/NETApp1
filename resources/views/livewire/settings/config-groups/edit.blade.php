<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <div>
            <x-ui-button click-event="{{ route('config_groups.index') }}" type="Back" button-name="Back"/>
        </div>
    </div>

    <x-ui-page-card title="{{ $action }} Group" status="{{ $status }}">

        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>

        <form wire:submit.prevent="{{ $action }}" class="form w-100">
            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-expandable-card id="UserCard" title="Group" :isOpen="true">
                        <x-ui-text-field label="Group Code" model="inputs.code" type="text" :action="$action" required="true" enabled="false" placeHolder="" visible="true" span="Full"/>
                        <x-ui-text-field label="Group Name" model="inputs.name" type="text" :action="$action" required="true" placeHolder="Enter Group Name" visible="true" span="Full"/>

                        <x-ui-dropdown-select label="Application"
                        click-event="refreshApplication"
                        model="inputs.appl_id"
                        :options="$applications"
                        :selectedValue="$inputs['appl_id']"
                        required="true"
                        :action="$action"
                        span="Full"/>

                        <x-ui-text-field-search
                        label="User"
                        click-event="refreshUser"
                        model="inputs.user_id"
                        name="User"
                        placeHolder="Search User"
                        :options="$users"
                        :selectedValue="$inputs['user_id']"
                        :action="$action"
                        span="Full"/>

                    </x-ui-expandable-card>
                </div>
            </x-ui-tab-view-content>
        </form>
        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
