<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card title="{{ $actionValue }} {{ $menuName }}" status="{{ $status }}">
        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>


            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-card>
                        <x-ui-text-field label="Menu Code" model="inputs.code" type="code" :action="$actionValue" required="true" enabled="true" placeHolder="" visible="true" span="Full" />
                        <x-ui-dropdown-select label="Application" clickEvent="refreshApplication" model="inputs.app_id" :options="$applications" required="true" :action="$actionValue" />
                        <x-ui-text-field label="Menu Header" model="inputs.menu_header" type="text" :action="$actionValue" placeHolder="Enter Menu Header" visible="true" span="Full" />
                        {{-- <x-ui-text-field label="Sub Menu" model="inputs.sub_menu" type="text" :action="$actionValue" required="false" placeHolder="Enter Sub Menu" visible="true" span="Full"/> --}}
                        <x-ui-text-field label="Menu Caption" model="inputs.menu_caption" type="text" :action="$actionValue" required="true" placeHolder="Enter Menu Caption" visible="true" span="Full" />
                        <x-ui-text-field label="Menu Link" model="inputs.menu_link" type="text" :action="$actionValue" required="true" placeHolder="Enter Menu Link" visible="true" span="Full" />
                    </x-ui-card>
                </div>
            </x-ui-tab-view-content>

        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
