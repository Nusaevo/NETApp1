<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
    <x-ui-page-card
        title="{{ $this->trans($actionValue) }} {!! $menuName !!} {{ $this->object->code ? ' (Nota #' . $this->object->code . ')' : '' }}"
        status="{{ $this->trans($status) }}">

        @if ($actionValue === 'Create')
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @else
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @endif
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="General" role="tabpanel" aria-labelledby="general-tab">
                <div class="row mt-4">
                    <div class="col-md-12">
                        <x-ui-card title="Main Information">
                            <x-ui-padding>
                                <div class="row">
                                    <x-ui-text-field label="Kode Program" model="inputs.code" type="text"
                                        :action="$actionValue" required="true" />
                                    <x-ui-text-field label="Nama Program" model="inputs.descrs" type="text"
                                        :action="$actionValue" required="true" />
                                    <x-ui-text-field label="Periode Awal" model="inputs.beg_date" type="date"
                                        :action="$actionValue" required="true" />
                                    <x-ui-text-field label="Periode AKhir" model="inputs.end_date" type="date"
                                        :action="$actionValue" required="true" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field-search type="int" label="Nama Barang" clickEvent=""
                                        model="inputs.matl_id" :options="$materials" required="true" :action="$actionValue"
                                        :enabled="true" />
                                    <x-ui-text-field label="Group" model="inputs.grp" type="text" :action="$actionValue"
                                        required="true" />
                                    <x-ui-text-field label="qty" model="inputs.qty" type="text" :action="$actionValue"
                                        required="true" />
                                    <x-ui-text-field label="Reward" model="inputs.reward" type="text"
                                        :action="$actionValue" required="true" />
                                </div>
                            </x-ui-padding>
                        </x-ui-card>
                    </div>
                    <x-ui-footer>
                        <div>
                            <x-ui-button clickEvent="Save" button-name="Save" loading="true" :action="$actionValue"
                                cssClass="btn-primary" iconPath="save.svg" />
                        </div>
                    </x-ui-footer>
                </div>
                <br>
        </x-ui-tab-view-content>
    </x-ui-page-card>
</div>
