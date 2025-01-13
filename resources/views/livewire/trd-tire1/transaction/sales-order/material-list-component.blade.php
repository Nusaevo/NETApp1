<div>
    <x-ui-card>
        <div>
            <x-ui-list-table id="Table" title="Material List">
                <x-slot name="body">
                    @foreach ($input_details as $key => $input_detail)
                        <tr wire:key="list{{ $key }}">
                            <x-ui-list-body>
                                {{-- <x-slot name="image">
                                    <img src="{{ $input_detail['image_path'] ?? 'https://via.placeholder.com/300' }}"
                                        alt="Material Photo" style="width: 200px; height: 200px;">
                                </x-slot> --}}
                                <x-slot name="rows">
                                    <div class="row">
                                        {{-- <x-ui-text-field-search type="int" label='custommer' clickEvent=""
                                            model="inputs.partner_id" :selectedValue="$inputs['partner_id']" :options="$partners" required="true"
                                            :action="$actionValue" onChanged="onPartnerChanged" :enabled="$isPanelEnabled" /> --}}
                                        {{-- <x-ui-text-field-search type="int" label='kode' clickEvent=""
                                            model="inputs.material_id" :selectedValue="$inputs['material_id']" :options="$materials" required="true"
                                            :action="$actionValue" onChanged="onMaterialChanged" :enabled="$isPanelEnabled" /> --}}
                                        <x-ui-text-field model="input_details.{{ $key }}.matl_code"
                                            label="{{ $this->trans('code') }}" enabled="true" />
                                        <x-ui-text-field model="input_details.{{ $key }}.quantity"
                                            label="{{ $this->trans('quantity') }}" enabled="true" />
                                        <x-ui-text-field model="input_details.{{ $key }}.uom"
                                            label="{{ $this->trans('uom') }}" enabled="true" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field model="input_details.{{ $key }}.harga_satuan"
                                            label="{{ $this->trans('harga_satuan') }}" enabled="true" />
                                        <x-ui-text-field model="input_details.{{ $key }}.disc"
                                            label="{{ $this->trans('disc') }}" enabled="true" />
                                        <x-ui-text-field model="input_details.{{ $key }}.amount"
                                            label="{{ $this->trans('amount') }}" enabled="true" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('description') }}"
                                            model="input_details.{{ $key }}.description" type="textarea"
                                            required="true" :action="$actionValue" />
                                    </div>
                                </x-slot>
                                <x-slot name="button">
                                    <x-ui-link-text type="close" :clickEvent="'deleteItem(' . $key . ')'" class="btn btn-link"
                                        name="x" />
                                </x-slot>
                            </x-ui-list-body>
                        </tr>
                    @endforeach
                </x-slot>
                <x-slot name="footerButton">
                    <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg" button-name="Add" />
                </x-slot>
            </x-ui-list-table>
        </div>
    </x-ui-card>
    <x-ui-footer>
        <div>
            <x-ui-button clickEvent="Save" button-name="Save Item" loading="true" :action="$actionValue"
                cssClass="btn-primary" iconPath="save.svg" />
        </div>
    </x-ui-footer>
</div>
