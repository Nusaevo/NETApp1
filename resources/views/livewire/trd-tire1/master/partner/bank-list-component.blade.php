<div>
    <x-ui-card>
        <div>
            <x-ui-list-table id="Table" title="Bank List">
                <x-slot name="body">
                    @foreach ($input_details as $key => $input_detail)
                        <tr wire:key="list{{ $key }}">
                            <x-ui-list-body>
                                {{-- <x-slot name="image">
                                        <img src="{{ $item['image_path'] ?? 'https://via.placeholder.com/300' }}" alt="Material" style="width: 200px; height: 200px;">
                                    </x-slot> --}}
                                <x-slot name="rows">
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('bank_acct') }}"
                                            model="input_details.{{ $key }}.bank_acct" type="text"
                                            :action="$actionValue" required="true" capslockMode="true"/>
                                        <x-ui-text-field label="{{ $this->trans('bank_name') }}"
                                            model="input_details.{{ $key }}.bank_name" type="text"
                                            :action="$actionValue" required="true" capslockMode="true"/>
                                        <x-ui-text-field label="{{ $this->trans('bank_location') }}"
                                            model="input_details.{{ $key }}.bank_location" type="text"
                                            :action="$actionValue" />
                                    </div>
                                </x-slot>
                                <x-slot name="button">
                                    <x-ui-link-text type="close" :clickEvent="'deleteItem(' . $key . ')'" class="btn btn-link"
                                        name="x" :action="$actionValue"/>
                                </x-slot>
                            </x-ui-list-body>
                        </tr>
                    @endforeach
                </x-slot>
                <x-slot name="footerButton">
                    <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg" button-name="Add" :action="$actionValue"/>
                </x-slot>


            </x-ui-list-table>
        </div>
    </x-ui-card>
    <x-ui-footer>
        <div>
            <x-ui-button clickEvent="SaveBank" button-name="Save Bank" loading="true" :action="$actionValue"
                cssClass="btn-primary" iconPath="save.svg" />
        </div>
    </x-ui-footer>
</div>
