<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
    <x-ui-page-card isForm="true" title="{{ $actionValue }} {!! $menuName !!}" status="{{ $status }}">

        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card title="Main Information">

                    <div class="row">
                        <x-ui-text-field label="Appl Code" model="inputs.code" type="code" :action="$actionValue"
                            required="true" enabled="true" visible="true" />
                        <x-ui-text-field label="Nama" model="inputs.name" type="text" :action="$actionValue"
                            required="true" placeHolder="Enter Name (e.g., POS Indo)" visible="true" />

                    </div>
                    <x-ui-text-field label="Description" model="inputs.descr" type="textarea" :action="$actionValue"
                        placeHolder="Enter Description (e.g., Application's information)" visible="true" />
                    <div class="row">
                        <x-ui-text-field label="Version" model="inputs.latest_version" type="text" :action="$actionValue"
                            placeHolder="Enter Version (optional)" visible="true" />
                        <x-ui-text-field label="Database" model="inputs.db_name" type="text" :action="$actionValue"
                            placeHolder="Enter Database" visible="true" required="true" />
                        <x-ui-text-field label="Sequence" model="inputs.seq" type="number" :action="$actionValue"
                            placeHolder="Enter Sequence" visible="true" required="true" />

                    </div>
                </x-ui-card>
            </div>
        </x-ui-tab-view-content>
        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
