<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card isForm="true" title="{{ $actionValue }} {!! $menuName !!}" status="{{ $status }}">
        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>

        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card>
                    <div class="row">
                        <x-ui-text-field
                            label="Code"
                            model="inputs.code"
                            type="code"
                            :action="$actionValue"
                            required="true"
                            enabled="true"
                            visible="true"
                            placeHolder="Enter application code (e.g., MMATL_BD_LASTID)"
                        />
                        <x-ui-dropdown-select
                            label="Application"
                            clickEvent="refreshApplication"
                            model="selectedApplication"
                            :options="$applications"
                            required="true"
                            :action="$actionValue"
                            visible="{{ $isSysConfig1 ? 'true' : 'false' }}"
                            onChanged="applicationChanged"
                            :enabled="$isEnabled"
                            placeHolder="Select an application from the list"
                        />
                    </div>
                    <div class="row">
                        <x-ui-text-field
                            label="Wrap Low"
                            model="inputs.wrap_low"
                            type="number"
                            :action="$actionValue"
                            visible="true"
                            placeHolder="Starting serial number (e.g., 1)"
                        />
                        <x-ui-text-field
                            label="Wrap High"
                            model="inputs.wrap_high"
                            type="number"
                            :action="$actionValue"
                            visible="true"
                            placeHolder="Ending serial number (e.g., 1000000000)"
                        />
                    </div>

                    <x-ui-text-field
                        label="Last Count"
                        model="inputs.last_cnt"
                        type="number"
                        :action="$actionValue"
                        visible="true"
                        placeHolder="Enter last used serial number (e.g., 0)"
                    />
                    <x-ui-text-field
                        label="Step Count"
                        model="inputs.step_cnt"
                        type="number"
                        :action="$actionValue"
                        visible="true"
                        placeHolder="Increment for serial number (e.g., 1)"
                    />
                    <div class="row">
                        <x-ui-text-field
                            label="Description"
                            model="inputs.descr"
                            type="text"
                            :action="$actionValue"
                            required="false"
                            visible="false"
                            placeHolder="Enter a description (optional)"
                        />
                    </div>
                    <x-ui-text-field label="Description" model="inputs.descr" type="textarea" :action="$actionValue"
                    placeHolder="Enter Description (e.g., Application's information)" visible="true" />
                </x-ui-card>
            </div>
        </x-ui-tab-view-content>

        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
