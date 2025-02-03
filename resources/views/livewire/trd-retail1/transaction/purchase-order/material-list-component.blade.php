<div>
    <x-ui-list-table id="Table">
        <x-slot name="body">
            @foreach ($input_details as $key => $input_details)
                <tr wire:key="list{{ $key }}">   <x-ui-text-field model="input_details.{{ $key }}.matl_code"
                    label='{{ $this->trans('code') }}' enabled="false" />
                    <x-ui-list-body>
                        <x-slot name="image">
                            <img src="{{ $item['image_path'] ?? 'https://via.placeholder.com/300' }}" alt="Material" style="width: 200px; height: 200px;">
                        </x-slot>
                        <x-slot name="rows">
                            <div class="row">
                                <x-ui-text-field model="input_details.{{ $key }}.matl_code"
                                    label='{{ $this->trans('code') }}' enabled="false" />
                                <x-ui-text-field model="input_details.{{ $key }}.barcode"
                                    label='{{ $this->trans('barcode') }}' enabled="false" />
                                <x-ui-text-field model="input_details.{{ $key }}.barcode"
                                    label='{{ $this->trans('barcode') }}' enabled="false" />
                            </div>
                            <div class="row">
                                <x-ui-text-field model="input_details.{{ $key }}.name"
                                    label='{{ $this->trans('name') }}' enabled="false" />
                                <x-ui-text-field model="input_details.{{ $key }}.matl_descr"
                                    label='{{ $this->trans('description') }}' enabled="false" />
                            </div>
                            <div class="row">
                                <x-ui-text-field model="input_details.{{ $key }}.selling_price"
                                    label='{{ $this->trans('selling_price') }}' enabled="false" />
                                <x-ui-text-field model="input_details.{{ $key }}.qty"
                                    label='{{ $this->trans('qty') }}' :onChanged="'changeQuantity(' . $key . ', $event.target.value)'" />
                                <x-ui-text-field model="input_details.{{ $key }}.price"
                                    label='{{ $this->trans('price') }}' :onChanged="'updateItem(' . $key . ', \'price\', $event.target.value)'" />
                            </div>
                        </x-slot>
                        <x-slot name="button">
                            <x-ui-link-text type="close" :clickEvent="'deleteItem(' . $key . ')'" class="btn btn-link" name="x" />
                        </x-slot>
                    </x-ui-list-body>
                </tr>
            @endforeach
        </x-slot>
        <x-slot name="footerButton">
            <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg"
                button-name="Add" />
        </x-slot>


    </x-ui-list-table>
    <x-ui-footer>
        <div>
            <x-ui-button clickEvent="Save" button-name="Save Item" loading="true" :action="$actionValue"
                cssClass="btn-primary" iconPath="save.svg" />
        </div>
    </x-ui-footer>
</div>
