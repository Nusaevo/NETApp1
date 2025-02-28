<div>
    <x-ui-card>
        <x-ui-table id="Table">
            <!-- Define table headers -->
            <x-slot name="headers">
                <th style="width: 50px; text-align: center;">No</th>
                <th style="width: 150px; text-align: center;">Jenis</th>
                <th style="width: 150px; text-align: center;">Keterangan</th>
                <th style="width: 150px; text-align: center;">Amount</th>
                <th style="width: 70px; text-align: center;">Actions</th>
            </x-slot>

            <!-- Define table rows -->
            <x-slot name="rows">
                @foreach ($input_details as $key => $input_detail)
                    <tr wire:key="list{{ $input_detail['id'] ?? $key }}">
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td>
                            <x-ui-dropdown-select label="{{ $this->trans('tr_type') }}"
                                model="input_details.{{ $key }}.pay_type_code" :options="$PaymentType"
                                required="true" :action="$actionValue" enabled="false"/>
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.price" label=""
                                :action="$actionValue" enabled="false" type="number" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.amt_idr" label=""
                                :action="$actionValue" enabled="false" type="number" />
                        </td>
                        <td style="text-align: center;">
                            {{-- <x-ui-button :clickEvent="'editItem(' . $key . ')'" button-name="Edit" loading="true" :action="$actionValue"
                                cssClass="btn-secondary text-light" /> --}}
                            <x-ui-button :clickEvent="'openPaymentDialog(' . $key . ')'" button-name="Set" loading="true" :action="$actionValue"
                                cssClass="btn-secondary text-light" />
                            <x-ui-button :clickEvent="'deleteItem(' . $key . ')'" button-name="" loading="true" :action="$actionValue"
                                cssClass="btn-danger text-danger" iconPath="delete.svg" />
                        </td>
                    </tr>
                @endforeach
            </x-slot>

            <x-slot name="button">
                <!-- Tombol untuk menambah item -->
                <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg" button-name="Add" />

                <x-ui-dialog-box id="PaymentDialogBox" title="Set Payment" width="600px" height="400px"
                    onOpened="openPaymentDialogBox" onClosed="closePaymentDialogBox">
                    <x-slot name="body">
                        <div class="row col-md-6">
                            @if (isset($activePaymentItemKey))
                                <x-ui-dropdown-select label="{{ $this->trans('tr_type') }}"
                                    model="input_details.{{ $activePaymentItemKey }}.tr_type" :options="$PaymentType"
                                    required="true" :action="$actionValue" />
                            @endif
                        </div>
                    </x-slot>
                    <x-slot name="footer">
                        <x-ui-button clickEvent="confirmPayment" button-name="Save" loading="true" :action="$actionValue"
                            cssClass="btn-primary" />
                    </x-slot>
                </x-ui-dialog-box>
            </x-slot>
        </x-ui-table>
    </x-ui-card>

    <!-- Footer with Save button -->
    <x-ui-footer>
        <x-ui-button clickEvent="SaveItem" button-name="Save Item" loading="true" :action="$actionValue"
            cssClass="btn-primary" iconPath="save.svg" />
    </x-ui-footer>
</div>
