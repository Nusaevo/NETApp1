<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card title="{{ $actionValue }} {!! $menuName !!}" status="{{ $status }}">
        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>


        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card>
                    <div class="row"><x-ui-text-field label="Var Code" model="inputs.code" type="code"
                            :action="$actionValue" required="true" enabled="true" visible="true" />
                        <x-ui-dropdown-select label="Application" clickEvent="refreshApplication" model="inputs.app_id"
                            :options="$applications" required="true" :action="$actionValue" />
                    </div>
                    <div class="row"> <x-ui-text-field label="Var Group" model="inputs.var_group" type="text"
                            :action="$actionValue" required="true" visible="true" />
                        <x-ui-text-field label="Default Value" model="inputs.default_value" type="text"
                            :action="$actionValue" required="true" visible="true" />
                        <x-ui-text-field label="Seq" model="inputs.seq" type="number" :action="$actionValue"
                            required="false" visible="true" />
                    </div>
                    <x-ui-text-field label="Descr" model="inputs.descr" type="textarea" :action="$actionValue"
                        required="false" visible="true" />
                </x-ui-card>
            </div>
        </x-ui-tab-view-content>

        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
