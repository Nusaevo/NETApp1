<div>
    <div>
        <x-ui-button click-event="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card title="{{ $actionValue }} Sales Return" status="{{ $status }}">
        <x-ui-tab-view id="myTab" tabs="Gemeral"> </x-ui-tab-view>

        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="Gemeral" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card>
                    <x-ui-padding>
                        <x-ui-text-field label="Tgl Terima" model="inputs.tr_date" type="date" :action="$actionValue" required="true" span="Half"/>
                        <x-ui-text-field-search label="Supplier" name="Supplier" click-event="" model="inputs.partner_id" :options="$suppliers" enabled="false" required="true" :action="$actionValue" span="Half"/>
                    </x-ui-padding>
                    <x-ui-list-table id="Table" title="Barang">
                        <x-slot name="body">
                            @foreach($input_details as $key => $detail)
                            <tr wire:key="list{{ $key }}">
                                <x-ui-list-body>
                                    <x-slot name="image">
                                        @php
                                        $imagePath = isset($detail['image_path']) && !empty($detail['image_path']) ? $detail['image_path'] : 'https://via.placeholder.com/300';
                                        @endphp
                                        <img src="{{ $imagePath }}" alt="Material Photo" style="width: 200px; height: 200px;">
                                    </x-slot>
                                    <x-slot name="rows">
                                        <x-ui-text-field model="input_details.{{ $key }}.matl_code" label='Product Code' type="text" :action="$actionValue" placeHolder="" enabled="false" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.barcode" label='Label Code' type="text" :action="$actionValue" placeHolder="" enabled="false" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.matl_descr" label='Description' type="text" :action="$actionValue" placeHolder="Description" enabled="false" span="Full" />
                                        <x-ui-text-field model="input_details.{{ $key }}.selling_price" label='Selling Price' type="text" :action="$actionValue" placeHolder="Selling Price" enabled="false" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.price" label='Buying Price' type="number" :onChanged="'changePrice('. $key .', $event.target.value)'" :action="$actionValue" enabled="false" placeHolder="" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.order_qty" label='Qty' type="number" :onChanged="'changeQty('. $key .', $event.target.value)'" :action="$actionValue" enabled="false" placeHolder="" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.amt" label='Amount' type="text" :action="$actionValue" enabled="false" placeHolder="" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.qty" label='Qty Retur' type="text" :action="$actionValue" enabled="true" required="true" placeHolder="" span="Full" />
                                    </x-slot>
                                </x-ui-list-body>
                            </tr>
                            @endforeach
                        </x-slot>
                        <x-slot name="footer">
                        </x-slot>
                    </x-ui-list-table>
                </x-ui-card>
            </div>
        </x-ui-tab-view-content>

        <x-ui-footer>
            @include('layout.customs.transaction-form-footer')
        </x-ui-footer>

    </x-ui-page-card>
</div>

