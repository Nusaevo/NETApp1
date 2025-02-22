<div>
    <x-ui-card>
        <x-ui-table id="Table" title="UOM List">
            <x-slot name="headers">
                <th style="text-align: center; width: 10px;">No</th>
                <th style="text-align: center; width: 50px;">Base UOM</th>
                <th style="text-align: center; width: 50px;">Reff UOM</th>
                <th style="text-align: center; width: 50px;">Reff Factor</th>
                <th style="text-align: center; width: 50px;">Base Factor</th>
                <th style="text-align: center; width: 150px;">Barcode</th>
                <th style="text-align: center; width: 150px;">Selling Price</th>
                <th style="text-align: center; width: 50px;">Actions</th>
            </x-slot>

            <x-slot name="rows">
                @foreach ($input_details as $key => $input_detail)
                    <tr wire:key="list{{ $key }}">
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td>
                            <x-ui-dropdown-select model="input_details.{{ $key }}.matl_uom"
                                :options="$materialUOM" required="true"/>
                        </td>
                        <td>
                            <x-ui-dropdown-select model="input_details.{{ $key }}.reff_uom"
                                :options="$materialUOM" required="true"/>
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.reff_factor"
                                type="number" required="true"/>
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.base_factor"
                                type="number" required="true"/>
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.barcode"
                                type="text" required="false"/>
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.selling_price"
                                type="number" required="false"/>
                        </td>
                        <td style="text-align: center;">
                            <x-ui-button clickEvent="deleteItem({{ $key }})" cssClass="btn-danger"
                                iconPath="delete.svg" button-name=""/>
                        </td>
                    </tr>
                @endforeach
            </x-slot>

            <x-slot name="button">
                <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg"
                    button-name="Add"/>
            </x-slot>
        </x-ui-table>
    </x-ui-card>

    <x-ui-footer>
        <x-ui-button clickEvent="SaveUom" button-name="Save UOM" loading="true"
            cssClass="btn-primary" iconPath="save.svg"/>
    </x-ui-footer>
</div>
