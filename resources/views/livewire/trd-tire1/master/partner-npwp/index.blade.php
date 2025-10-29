<div>
    <x-ui-page-card title="{!! $menuName !!}" status="{{ $status }}">
        <div class="row mb-4">
            <div class="col-md-8">
                <x-ui-dropdown-search
                    label="Pilih Partner"
                    model="selectedPartnerId"
                    optionValue="id"
                    :query="$ddPartner['query']"
                    :optionLabel="$ddPartner['optionLabel']"
                    :placeHolder="$ddPartner['placeHolder']"
                    :selectedValue="$selectedPartnerId"
                    required="false"
                    action="Edit"
                    enabled="true"
                    type="int"
                    onChanged="onPartnerChanged" />
            </div>
        </div>
            <div class="card">
                <div class="card-body">
                    <x-ui-list-table id="Table" title="NPWP List">
                        <x-slot name="body">
                            @foreach ($inputDetails as $key => $input_detail)
                                <tr wire:key="list{{ $key }}">
                                    <x-ui-list-body>
                                        <x-slot name="rows">
                                            <div class="row">
                                                <x-ui-text-field label="NPWP/NIK"
                                                    model="inputDetails.{{ $key }}.npwp" type="text"
                                                    :action="$actionValue" required="true" capslockMode="true" />
                                                <x-ui-text-field label="Nama WP"
                                                    model="inputDetails.{{ $key }}.wp_name" type="text"
                                                    :action="$actionValue" required="true" capslockMode="true" />
                                            </div>
                                            <div class="row">
                                                <x-ui-text-field label="Alamat WP"
                                                    model="inputDetails.{{ $key }}.wp_location" type="textarea"
                                                    :action="$actionValue" required="true" />
                                            </div>
                                        </x-slot>
                                        <x-slot name="button">
                                            <x-ui-button
                                                clickEvent="deleteItem({{ $key }})"
                                                cssClass="btn btn-danger"
                                                iconPath="delete.svg"
                                                button-name=""
                                                :action="$actionValue" />
                                        </x-slot>
                                    </x-ui-list-body>
                                </tr>
                            @endforeach
                        </x-slot>
                        <x-slot name="footerButton">
                            <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg" button-name="Add"
                                :action="$actionValue" />
                        </x-slot>
                    </x-ui-list-table>
                </div>
                <div class="card-footer" style="text-align: end">
                    <x-ui-button clickEvent="saveNpwp" button-name="Save" loading="true" :action="$actionValue"
                        cssClass="btn-primary" iconPath="save.svg" />
                </div>
            </div>
    </x-ui-page-card>
</div>
