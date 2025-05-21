<div>
    <x-ui-card>
        <x-ui-table id="Table">
            <!-- Define table headers -->
            <x-slot name="headers">
                <th style="width: 50px; text-align: center;">No</th>
                <th style="width: 150px; text-align: center;">Nomor Nota</th>
                <th style="width: 150px; text-align: center;">Tanggal Jatuh tempo</th>
                <th style="width: 50px; text-align: center;">Total Piutang</th>
                <th style="width: 90px; text-align: center;">Total Bayar</th>
                <th style="width: 70px; text-align: center;">Actions</th>
            </x-slot>

            <!-- Define table rows -->
            <x-slot name="rows">
                @foreach ($input_details as $key => $input_detail)
                    <tr wire:key="list{{ $input_detail['id'] ?? $key }}">
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td>
                            <x-ui-dropdown-select type="int" label="" clickEvent=""
                                model="input_details.{{ $key }}.billhdrtr_code" :options="$codeBill"
                                required="true" :action="$actionValue"
                                onChanged="onCodeChanged({{ $key }}, $event.target.value)"
                                :enabled="true" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.tr_date" label=""
                                :action="$actionValue" enabled="false" type="date" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.amtbill" label="" enabled="true"
                                :action="$actionValue" enabled="false" type="number" required="true" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.amt" label=""
                                :action="$actionValue" enabled="true" />
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
