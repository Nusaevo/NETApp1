<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <div>
            <x-ui-button click-event="" type="Back" button-name="Back"/>
        </div>
    </div>

    <x-ui-page-card title="{{ $actionValue }} Menu" status="{{ $status }}">
        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>

        <form wire:submit.prevent="{{ $actionValue }}" class="form w-100">
            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-expandable-card id="MenuCard" title="Menu" :isOpen="true">
                        <x-ui-text-field label="Menu Code" model="inputs.code" type="code" :action="$actionValue" required="true" enabled="true" placeHolder="" visible="true" span="Full"/>
                        <x-ui-dropdown-select label="Application"
                        click-event="refreshApplication"
                        model="inputs.app_id"
                        :options="$applications"
                        :selectedValue="$inputs['app_id']"
                        required="true"
                        :action="$actionValue"/>
                        <x-ui-text-field label="Menu Header" model="inputs.menu_header" type="text" :action="$actionValue" required="true" placeHolder="Enter Menu Header" visible="true" span="Full"/>
                        {{-- <x-ui-text-field label="Sub Menu" model="inputs.sub_menu" type="text" :action="$actionValue" required="false" placeHolder="Enter Sub Menu" visible="true" span="Full"/> --}}
                        <x-ui-text-field label="Menu Caption" model="inputs.menu_caption" type="text" :action="$actionValue" required="true" placeHolder="Enter Menu Caption" visible="true" span="Full"/>
                        <x-ui-text-field label="Seq" model="inputs.seq" type="number" :action="$actionValue" required="true" placeHolder="Enter Seq" visible="true" span="Full"/>
                        <x-ui-text-field label="Link" model="inputs.link" type="text" :action="$actionValue" required="true" placeHolder="Enter Menu Link" visible="true" span="Full"/>
                    </x-ui-expandable-card>
                </div>
            </x-ui-tab-view-content>
        </form>
        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
