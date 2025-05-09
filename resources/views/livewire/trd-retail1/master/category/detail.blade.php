<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card isForm="true" title="{{ $actionValue }} {!! $menuName !!}" status="{{ $status }}">
        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card>
                    {{-- <x-ui-text-field label="Const Code" model="inputs.code" type="code" :action="$actionValue" required="true" enabled="true"  visible="true" /> --}}
                    <div class="row"><x-ui-dropdown-select label="Application" clickEvent="refreshApplication"
                            model="selectedApplication" :options="$applications" required="true" :action="$actionValue"
                            visible="{{ $isSysConfig1 ? 'true' : 'false' }}" onChanged="applicationChanged"
                            :enabled="$isEnabled" />

                        <x-ui-text-field label="Const Group" model="inputs.const_group" type="text" :action="$actionValue"
                            required="true" visible="true" placeHolder="Ex : MMATL_JEWEL_COMPONENTS"/>
                        <x-ui-text-field label="Seq" model="inputs.seq" type="number" :action="$actionValue"
                            required="true" visible="true" />
                    </div>
                    <div class="row">
                        <x-ui-text-field label="Str1" model="inputs.str1" type="text" :action="$actionValue"
                            required="true" visible="true" placeHolder="Ex : RG"/>
                        <x-ui-text-field label="Str2" model="inputs.str2" type="text" :action="$actionValue"
                            required="false" visible="true" placeHolder="Ex : ROSE GOLD"/>

                        <x-ui-text-field label="Num1" model="inputs.num1" type="number" :action="$actionValue"
                            required="false" visible="true" />
                        <x-ui-text-field label="Num2" model="inputs.num2" type="number" :action="$actionValue"
                            required="false" visible="true" />
                    </div>
                    <x-ui-text-field label="Note1" model="inputs.note1" type="textarea" :action="$actionValue"
                        required="false" visible="true" />
                </x-ui-card>
            </div>
        </x-ui-tab-view-content>

        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
