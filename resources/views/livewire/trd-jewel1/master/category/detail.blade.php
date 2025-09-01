<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card isForm="true" title="{{ $actionValue }} Material Category" status="{{ $status }}">
        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card>
                    <div class="row">
                        <x-ui-dropdown-select label="Category Group" model="inputs.const_group" :options="[
                            ['label' => 'Material Category 1', 'value' => 'MMATL_CATEGL1'],
                            ['label' => 'Material Category 2', 'value' => 'MMATL_CATEGL2']
                        ]" required="true" :action="$actionValue" enabled="true" visible="true" />

                        <x-ui-text-field label="Urutan (Seq)" model="inputs.seq" type="number" :action="$actionValue"
                            required="true" visible="true" placeHolder="Ex: 1, 2, 3" />
                    </div>

                    <div class="row">
                        <x-ui-text-field label="Value (Code)" model="inputs.str1" type="text" :action="$actionValue"
                            required="true" visible="true" placeHolder="Ex: RG, WG, YG" />

                        <x-ui-text-field label="Label (Name)" model="inputs.str2" type="text" :action="$actionValue"
                            required="true" visible="true" placeHolder="Ex: Rose Gold, White Gold" />
                    </div>

                    <x-ui-text-field label="Catatan" model="inputs.note1" type="textarea" :action="$actionValue"
                        required="false" visible="true" placeHolder="Catatan tambahan (opsional)" />
                </x-ui-card>
            </div>
        </x-ui-tab-view-content>

        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
