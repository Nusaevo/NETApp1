{{-- <div>
    <x-ui-card>
        <div>
            <x-ui-list-table id="Table">
                <x-slot name="body">
                    @foreach ($input_details as $key => $input_detail)
                        <tr wire:key="list{{ $input_detail['id'] ?? $key }}">
                            <x-ui-list-body>
                                <x-slot name="rows">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <x-ui-text-field-search type="int" label='kode' clickEvent=""
                                            model="input_details.{{ $key }}.matl_id" :selectedValue="$input_details[$key]['matl_id']"
                                            :options="$materials" required="true" :action="$actionValue"
                                            onChanged="onMaterialChanged({{ $key }}, $event.target.value)"
                                            :enabled="true" />
                                        </div>
                                        <div class="col-md-3">
                                            <x-ui-text-field model="input_details.{{ $key }}.price"
                                            label="Harga Satuan" enabled="true"
                                            onChanged="updateItemAmount({{ $key }})" />
                                        </div>
                                        <div class="col-md-1">
                                            <x-ui-text-field model="input_details.{{ $key }}.qty" label="Qty"
                                            enabled="true" class="form-control"
                                            model="input_details.{{ $key }}.qty"
                                            onChanged="updateItemAmount({{ $key }})" type="number" />
                                        </div>
                                        <div class="col-md-1">
                                            <x-ui-text-field model="input_details.{{ $key }}.disc_pct"
                                            label="{{ $this->trans('disc') }}" enabled="true"
                                            onChanged="updateItemAmount({{ $key }})" />
                                        </div>
                                        <div class="col-md-3">
                                            <x-ui-text-field model="input_details.{{ $key }}.amt_idr"
                                            label="Amount" class="form-control" type="text" enabled="false" />
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
            <x-ui-button clickEvent="SaveItem" button-name="Save Item" loading="true" :action="$actionValue"
                cssClass="btn-primary" iconPath="save.svg" />
        </div>
    </x-ui-footer>
</div> --}}

<div>
    <x-ui-card>
        <x-ui-table id="Table">
            <!-- Define table headers -->
            <x-slot name="headers">
                <th style="width: 50px; text-align: center;">No</th>
                <th style="width: 150px; text-align: center;">Code</th>
                <th style="width: 150px; text-align: center;">Harga Satuan</th>
                <th style="width: 50px; text-align: center;">Quantity</th>
                <th style="width: 90px; text-align: center;">Disc (%)</th>
                <th style="width: 150px; text-align: center;">Amount</th>
                <th style="width: 70px; text-align: center;">Actions</th>
            </x-slot>

            <!-- Define table rows -->
            <x-slot name="rows">
                @foreach ($input_details as $key => $input_detail)
                    <tr wire:key="list{{ $input_detail['id'] ?? $key }}">
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td>
                            <x-ui-text-field-search type="int" label="" clickEvent=""
                                model="input_details.{{ $key }}.matl_id" :selectedValue="$input_details[$key]['matl_id']" :options="$materials"
                                required="true" :action="$actionValue"
                                onChanged="onMaterialChanged({{ $key }}, $event.target.value)"
                                :enabled="true" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.price" label=""
                                :action="$actionValue" enabled="false" type="number"/>
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.qty" label="" enabled="true"
                                :action="$actionValue" onChanged="updateItemAmount({{ $key }})" type="number"
                                required="true" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.disc_pct" label=""
                                :action="$actionValue" enabled="true" onChanged="updateItemAmount({{ $key }})" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.amt_idr" label=""
                                :action="$actionValue" type="text" enabled="false" type="number"/>
                        </td>
                        <td style="text-align: center;">
                            <x-ui-button :clickEvent="'deleteItem(' . $key . ')'" button-name="" loading="true" :action="$actionValue"
                                cssClass="btn-danger text-danger" iconPath="delete.svg" />
                        </td>
                    </tr>
                @endforeach
            </x-slot>

            <x-slot name="button">
                <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg" button-name="Add" />
            </x-slot>
        </x-ui-table>
    </x-ui-card>

    <!-- Footer with Save button -->
    <x-ui-footer>
        <x-ui-button clickEvent="SaveItem" button-name="Save Item" loading="true" :action="$actionValue"
            cssClass="btn-primary" iconPath="save.svg" />
    </x-ui-footer>
</div>
