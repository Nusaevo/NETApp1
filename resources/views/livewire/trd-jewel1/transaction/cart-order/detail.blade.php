<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back"/>
    </div>
    <x-ui-page-card title="{{ $this->trans($actionValue) . ' ' . $this->trans('cart_order') }}" status="{{ $this->trans($status) }}">

        @if ($actionValue === 'Create')
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @else
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @endif
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="General" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card>

                    <x-ui-list-table id="Table" title="">
                        <x-slot name="button">
                            <div style="display: flex; justify-content: start; align-items: center; gap: 10px;">
                                <x-ui-button clickEvent="Add" button-name="Scan RFID" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="add.svg" />

                                <button type="button" wire:click="SaveWithoutNotification" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="">
                                    <span style="font-size: 16px;"> {{ $this->trans('btnAdd') }}</span>
                                </button>
                            </div>
                            {{-- <x-ui-button clickEvent="Add" button-name="Tambah" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="add.svg" /> --}}
                        </x-slot>
                        <x-slot name="body">
                            @foreach($input_details as $key => $detail)
                            <tr wire:key="list{{ $key }}">
                                <x-ui-list-body>
                                    <x-slot name="image">
                                        <div class="form-option" style="display: flex; align-items: center; margin-left: 10px;">
                                            <input type="checkbox" wire:model="input_details.{{$key}}.checked" id="option{{ $key }}" style="width: 20px; height: 20px; margin-right: 5px;"/>
                                        </div>
                                        @php
                                        $imagePath = isset($detail['image_path']) && !empty($detail['image_path']) ? $detail['image_path'] : 'https://via.placeholder.com/300';
                                        @endphp
                                        <img src="{{ $imagePath }}" alt="Material Photo" style="width: 200px; height: 200px;">
                                    </x-slot>

                                    <x-slot name="rows">
                                        <x-ui-text-field model="input_details.{{ $key }}.matl_code" label='{{ $this->trans("code") }}' type="text" :action="$actionValue" placeHolder="" enabled="false" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.barcode" label='{{ $this->trans("barcode") }}' type="text" :action="$actionValue" placeHolder="" enabled="false" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.name" label='{{ $this->trans("name") }}' type="text" :action="$actionValue" placeHolder="" enabled="false" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.matl_descr" label='{{ $this->trans("description") }}' type="text" :action="$actionValue" placeHolder="" enabled="false" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.selling_price" label='{{ $this->trans("selling_price") }}' :onChanged="'changePrice('. $key .', $event.target.value)'"  type="number" :action="$actionValue" placeHolder="" enabled="true" span="Full" />
                                        <x-ui-text-field model="input_details.{{ $key }}.qty" label='{{ $this->trans("qty") }}' type="number" enabled="false" :action="$actionValue" required="true" placeHolder="" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.amt" label='{{ $this->trans("amount") }}' type="number" :action="$actionValue" enabled="false" placeHolder="" span="Half" />
                                    </x-slot>
                                    <x-slot name="button">
                                        <a href="#" wire:click="deleteDetails({{ $key }})" class="btn btn-link">
                                            X
                                        </a>
                                    </x-slot>
                                </x-ui-list-body>
                            </tr>
                            @endforeach
                        </x-slot>
                        <x-slot name="footer">
                            <h3>{{ $this->trans('totalPrice') }}: {{ rupiah($total_amount) }}</h3>
                        </x-slot>
                    </x-ui-list-table>
            </x-ui-card>
            </div>
        </x-ui-tab-view-content>
        <x-ui-footer>
            <x-ui-button :action="$actionValue" clickEvent="Save"
                cssClass="btn-primary" loading="true" button-name="Save" iconPath="save.svg" />
            <x-ui-button :action="$actionValue" clickEvent="Checkout"
                cssClass="btn-primary" loading="true" button-name="Checkout" iconPath="add.svg" />
        </x-ui-footer>
    </x-ui-page-card>
@php
    // dump($object->id);
@endphp
</div>

