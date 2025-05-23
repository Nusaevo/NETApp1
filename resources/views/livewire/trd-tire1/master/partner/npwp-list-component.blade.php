<div>

    <x-ui-card>
        <div>
            <x-ui-list-table id="Table" title="Npwp List">
                <x-slot name="body">
                    @foreach ($input_details as $key => $input_detail)
                        <tr wire:key="list{{ $key }}">
                            <x-ui-list-body>
                                {{-- <x-slot name="image">
                                        <img src="{{ $item['image_path'] ?? 'https://via.placeholder.com/300' }}" alt="Material" style="width: 200px; height: 200px;">
                                    </x-slot> --}}
                                <x-slot name="rows">
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('npwp') }}"
                                            model="input_details.{{ $key }}.npwp" type="text"
                                            :action="$actionValue" required="true" capslockMode="true"/>
                                        <x-ui-text-field label="{{ $this->trans('wp_name') }}"
                                            model="input_details.{{ $key }}.wp_name" type="text"
                                            :action="$actionValue" required="true" capslockMode="true"/>
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('wp_location') }}"
                                            model="input_details.{{ $key }}.wp_location" type="textarea"
                                            :action="$actionValue" required="true"/>
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
            <x-ui-button clickEvent="SaveNPWP" button-name="Save" loading="true" :action="$actionValue"
                cssClass="btn-primary" iconPath="save.svg" />
        </div>
    </x-ui-footer>
</div>
