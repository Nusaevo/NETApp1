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
                    <x-ui-expandable-card id="UserCard" title="Menu" :isOpen="true">
                        <x-ui-text-field label="Menu Code" model="inputs.code" type="text" :action="$actionValue" required="true" enabled="false" placeHolder="" visible="true" span="Full"/>
                        <x-ui-dropdown-select label="Application"
                        click-event="refreshApplication"
                        model="inputs.appl_id"
                        :options="$applications"
                        :selectedValue="$inputs['appl_id']"
                        required="true"
                        :action="$actionValue" />
                        <x-ui-text-field label="Str1" model="inputs.str1" type="text" :action="$actionValue" required="true" placeHolder="" visible="true" span="Full"/>
                        <x-ui-text-field label="Str2" model="inputs.str2" type="text" :action="$actionValue" required="true" placeHolder="" visible="true" span="Full"/>
                        <x-ui-text-field label="Num1" model="inputs.num1" type="number" :action="$actionValue" required="false" placeHolder="" visible="true" span="Full"/>
                        <x-ui-text-field label="Num2" model="inputs.num2" type="number" :action="$actionValue" required="false" placeHolder="" visible="true" span="Full"/>
                        <x-ui-text-field label="Note1" model="inputs.note1" type="textarea" :action="$actionValue" required="false" placeHolder="" visible="true" span="Full"/>
                    </x-ui-expandable-card>
                </div>
            </x-ui-tab-view-content>
        </form>
        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
