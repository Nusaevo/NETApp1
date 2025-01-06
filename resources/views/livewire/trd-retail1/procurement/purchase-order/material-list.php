<div>
    <x-ui-list-table id="Table" title="Material List">
        <x-slot name="button">
            <x-ui-button clickEvent="OpenDialogBox" cssClass="btn btn-primary" iconPath="add.svg" button-name="{{ $this->trans('btnAdd') }}" />
        </x-slot>
        <x-slot name="body">
            @foreach($input_details as $key => $detail)
                <tr wire:key="list{{ $key }}">
                    <x-ui-list-body>
                        <x-slot name="image">
                            <img src="{{ $detail['image_path'] ?? 'https://via.placeholder.com/300' }}" alt="Material Photo" style="width: 200px; height: 200px;">
                        </x-slot>
                        <x-slot name="rows">
                            <div class="row">
                                <x-ui-text-field model="input_details.{{ $key }}.matl_code" label='{{ $this->trans("code") }}' enabled="false" />
                                <x-ui-text-field model="input_details.{{ $key }}.barcode" label='{{ $this->trans("barcode") }}' enabled="false" />
                            </div>
                            <div class="row">
                                <x-ui-text-field model="input_details.{{ $key }}.name" label='{{ $this->trans("name") }}' enabled="false" />
                                <x-ui-text-field model="input_details.{{ $key }}.matl_descr" label='{{ $this->trans("description") }}' enabled="false" />
                            </div>
                            <div class="row">
                                <x-ui-text-field model="input_details.{{ $key }}.selling_price" label='{{ $this->trans("selling_price") }}' enabled="false" />
                            </div>
                            <div class="row">
                                <x-ui-text-field model="input_details.{{ $key }}.qty" label='{{ $this->trans("qty") }}' enabled="false" />
                                <x-ui-text-field model="input_details.{{ $key }}.price" label='{{ $this->trans("price") }}' :onChanged="'changePrice('. $key .', $event.target.value)'" />
                            </div>
                        </x-slot>
                        <x-slot name="button">
                            <x-ui-link-text type="close" :clickEvent="'deleteDetails(' . $key . ')'" class="btn btn-link" name="x" />
                        </x-slot>
                    </x-ui-list-body>
                </tr>
            @endforeach
        </x-slot>
        <x-slot name="footer">
            <h3>{{ $this->trans('totalPrice') }}: {{ $total_amount }}</h3>
        </x-slot>
    </x-ui-list-table>
</div>
