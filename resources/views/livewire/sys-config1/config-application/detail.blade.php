<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card title="{{ $actionValue }} {{ $menuName }}" status="{{ $status }}">
        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>
            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-card>
                        <x-ui-text-field label="Appl Code" model="inputs.code" type="code" :action="$actionValue" required="true" enabled="true" placeHolder="" visible="true" span="Full" />
                        <x-ui-text-field label="Nama" model="inputs.name" type="text" :action="$actionValue" required="true" placeHolder="Enter Name (e.g., POS Indo)" visible="true" span="Full" />
                        <x-ui-text-field label="Description" model="inputs.descr" type="textarea" :action="$actionValue" placeHolder="Enter Description (e.g., Application's information)" visible="true" span="Full" />
                        <x-ui-text-field label="Version" model="inputs.version" type="text" :action="$actionValue" placeHolder="Enter Version (optional)" visible="true" span="Full" />
                    </x-ui-card>
                </div>
            </x-ui-tab-view-content>
        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
