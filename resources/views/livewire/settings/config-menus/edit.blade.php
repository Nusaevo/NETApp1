<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <div>
            <x-ui-button click-event="{{ route('config_menus.index') }}" type="Back" button-name="Back"/>
        </div>
    </div>

    <x-ui-page-card title="{{ $action }} Menu" status="{{ $status }}">
        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>

        <form wire:submit.prevent="{{ $action }}" class="form w-100">
            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-expandable-card id="UserCard" title="Menu" :isOpen="true">
                        <x-ui-text-field label="Menu Code" model="inputs.code" type="text" :action="$action" required="true" enabled="false" placeHolder="" visible="true" span="Full"/>
                        <x-ui-dropdown-select label="Application"
                        click-event="refreshApplication"
                        model="inputs.appl_id"
                        :options="$applications"
                        :selectedValue="$inputs['appl_id']"
                        required="true"
                        :action="$action" />
                        <x-ui-text-field label="Menu Header" model="inputs.menu_header" type="text" :action="$action" required="true" placeHolder="Enter Menu Header" visible="true" span="Full"/>
                        <x-ui-text-field label="Sub Menu" model="inputs.sub_menu" type="text" :action="$action" required="false" placeHolder="Enter Sub Menu" visible="true" span="Full"/>
                        <x-ui-text-field label="Menu Caption" model="inputs.menu_caption" type="text" :action="$action" required="true" placeHolder="Enter Menu Caption" visible="true" span="Full"/>
                        <x-ui-text-field label="Link" model="inputs.link" type="text" :action="$action" required="true" placeHolder="Enter Menu Link" visible="true" span="Full"/>
                    </x-ui-expandable-card>
                </div>
            </x-ui-tab-view-content>
        </form>
        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
