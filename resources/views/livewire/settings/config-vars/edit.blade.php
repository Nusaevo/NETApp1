<div>
    <x-ui-button click-event="" type="Back" button-name="Back" />
</div>

<x-ui-page-card title="{{ $actionValue }} Var" status="{{ $status }}">
    <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>

    <form wire:submit.prevent="{{ $actionValue }}" class="form w-100">
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-expandable-card id="VarCard" title="Menu" :isOpen="true">
                    <x-ui-text-field label="Var Code" model="inputs.code" type="text" :action="$actionValue" required="true" enabled="false" placeHolder="" visible="true" span="Full" />
                    <x-ui-dropdown-select label="Application" click-event="refreshApplication" model="inputs.app_id" :options="$applications" :selectedValue="$inputs['app_id']" required="true" :action="$actionValue" />
                    <x-ui-text-field label="Var Group" model="inputs.var_group" type="text" :action="$actionValue" required="true" placeHolder="" visible="true" span="Full" />
                    <x-ui-text-field label="Default Value" model="inputs.default_value" type="text" :action="$actionValue" required="true" placeHolder="" visible="true" span="Full" />
                    <x-ui-text-field label="Seq" model="inputs.seq" type="number" :action="$actionValue" required="false" placeHolder="" visible="true" span="Full" />
                    <x-ui-text-field label="Descr" model="inputs.descr" type="textarea" :action="$actionValue" required="false" placeHolder="" visible="true" span="Full" />
                </x-ui-expandable-card>
            </div>
        </x-ui-tab-view-content>
    </form>
    @include('layout.customs.form-footer')
</x-ui-page-card>
