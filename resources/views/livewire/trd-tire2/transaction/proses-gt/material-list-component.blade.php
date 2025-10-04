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
                            <x-ui-text-field model="input_details.{{ $key }}.price" label="" :action="$actionValue"
                                enabled="false" type="number"/>
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.qty" label="" enabled="true" :action="$actionValue"
                                onChanged="updateItemAmount({{ $key }})" type="number" required="true" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.disc_pct" label="" :action="$actionValue"
                                enabled="true" onChanged="updateItemAmount({{ $key }})" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.amt_idr" label="" :action="$actionValue"
                                type="text" enabled="false" type="number"/>
                        </td>
                        <td style="text-align: center;">
                            <x-ui-button :clickEvent="'deleteItem(' . $key . ')'" button-name="" loading="true" :action="$actionValue" cssClass="btn-danger text-danger" iconPath="delete.svg" />
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
