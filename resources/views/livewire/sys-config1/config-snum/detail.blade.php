<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card title="{{ $actionValue }} {!! $menuName !!}" status="{{ $status }}">
        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>

            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-card>
                        <x-ui-text-field label="Code" model="inputs.code" type="code" :action="$actionValue" required="true" enabled="true"  visible="true" span="Full" />
                        <x-ui-dropdown-select label="Application" clickEvent="refreshApplication" model="inputs.app_id" :options="$applications" required="true" :action="$actionValue" />
                        <x-ui-text-field label="Last Count" model="inputs.last_cnt" type="number" :action="$actionValue"  visible="true" span="Full" />
                        <x-ui-text-field label="Wrap Low" model="inputs.wrap_low" type="number" :action="$actionValue"  visible="true" span="Full" />
                        <x-ui-text-field label="Wrap High" model="inputs.wrap_high" type="number" :action="$actionValue"  visible="true" span="Full" />
                        <x-ui-text-field label="Step Count" model="inputs.step_cnt" type="number" :action="$actionValue"  visible="true" span="Full" />
                        <x-ui-text-field label="Descr" model="inputs.descr" type="text" :action="$actionValue" required="false"  visible="true" span="Full"/>
                    </x-ui-card>
                </div>
            </x-ui-tab-view-content>

        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
