<div>

    <x-ui-card>
            <x-ui-list-table id="Table" title="Kontak List">
                <x-slot name="body">
                    @foreach ($input_details as $key => $input_detail)
                        <tr wire:key="list{{ $key }}">
                            <x-ui-list-body>
                                {{-- <x-slot name="image">
                                        <img src="{{ $item['image_path'] ?? 'https://via.placeholder.com/300' }}" alt="Material" style="width: 200px; height: 200px;">
                                    </x-slot> --}}
                                <x-slot name="rows">
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('contact_name') }}"
                                            model="input_details.{{ $key }}.contact_name" type="text" :action="$actionValue"
                                            required="true" capslockMode="true"/>
                                        <x-ui-text-field label="{{ $this->trans('position') }}" model="input_details.{{ $key }}.position"
                                            type="text" :action="$actionValue" required="true" capslockMode="true"/>
                                        <x-ui-text-field label="{{ $this->trans('date_of_birth') }}"
                                            model="input_details.{{ $key }}.date_of_birth" type="text" :action="$actionValue"
                                            required="false"/>
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('phone1') }}" model="input_details.{{ $key }}.phone1"
                                            type="text" :action="$actionValue" required="true" />
                                        <x-ui-text-field label="{{ $this->trans('phone2') }}" model="input_details.{{ $key }}.phone2"
                                            type="text" :action="$actionValue" required="false" />
                                        <x-ui-text-field label="{{ $this->trans('email') }}" model="input_details.{{ $key }}.email"
                                            type="text" :action="$actionValue" required="false" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('contact_address') }}"
                                            model="input_details.{{ $key }}.contact_address" type="text" :action="$actionValue"
                                            required="false" />
                                        <x-ui-text-field label="{{ $this->trans('contact_note') }}"
                                            model="input_details.{{ $key }}.contact_note" type="text" :action="$actionValue" required="false" />
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
                    <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg"
                        button-name="Add" :action="$actionValue"/>
                </x-slot>


            </x-ui-list-table>
    </x-ui-card>
    <x-ui-footer>
        <div>
            <x-ui-button clickEvent="SaveContact" button-name="Save Contact" loading="true" :action="$actionValue"
                cssClass="btn-primary" iconPath="save.svg" />
        </div>
    </x-ui-footer>
</div>
