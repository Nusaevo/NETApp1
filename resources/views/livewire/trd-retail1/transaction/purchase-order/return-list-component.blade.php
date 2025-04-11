<div>
    <x-ui-card title="">
        <x-ui-button clickEvent="openReturnDialogBox" cssClass="btn btn-warning mb-3" iconPath="add.svg" button-name="Add Return Item" />

        <x-ui-table id="ReturnTable">
            <x-slot name="headers">
                <th style="text-align: center;">No</th>
                <th style="text-align: center;">Code</th>
                <th style="text-align: center;">Name</th>
                <th style="text-align: center;">Qty</th>
                <th style="text-align: center;">UOM</th>
                <th style="text-align: center;">Amount</th>
                <th style="text-align: center;">Actions</th>
            </x-slot>
            <x-slot name="rows">
                @forelse ($return_details as $index => $item)
                    <tr wire:key="return-{{ $index }}">
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td style="text-align: center;">{{ $item['matl_code'] }}</td>
                        <td>{{ $item['matl_descr'] }}</td>
                        <td style="text-align: center;">
                            <x-ui-text-field type="number" model="return_details.{{ $index }}.qty" label="" enabled="true" onChanged="" />
                        </td>
                        <td style="text-align: center;">{{ $item['matl_uom'] }}</td>
                        <td style="text-align: center;">{{ rupiah($item['amt'] ?? 0) }}</td>
                        <td style="text-align: center;">
                            <x-ui-button :clickEvent="'deleteReturnItem(' . $index . ')'" button-name="" cssClass="btn-danger text-danger" iconPath="delete.svg" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada item retur</td>
                    </tr>
                @endforelse
            </x-slot>
        </x-ui-table>

        <x-ui-footer class="mt-3">
            <x-ui-button clickEvent="saveReturnItems" button-name="Save Return Items" cssClass="btn-primary" iconPath="save.svg" loading="true" />
        </x-ui-footer>
    </x-ui-card>

    {{-- Dialog Box for Return Material Selection --}}
    <x-ui-dialog-box id="returnDialogBox" title="Select Items to Return" width="800px" height="500px" onOpened="openReturnDialogBox" onClosed="closeReturnDialogBox">
        <x-slot name="body">
            <x-ui-table id="ReturnSelectTable">
                <x-slot name="headers">
                    <th>Select</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Qty</th>
                    <th>UOM</th>
                </x-slot>

                <x-slot name="rows">
                    @if (empty($object_detail))
                        <tr>
                            <td colspan="5" class="text-center text-muted">No Data Found</td>
                        </tr>
                    @else
                        @foreach ($object_detail as $index => $item)
                            <tr wire:key="return-select-{{ $item->id }}">
                                <td style="text-align: center;">
                                    <x-ui-option label="" required="false" layout="horizontal"
                                        enabled="true" type="checkbox" visible="true"
                                        :options="[$item->matl_id => $item->matl_code]"
                                        onChanged="selectReturnMaterial({{ $item->matl_id }})" />
                                </td>
                                <td>{{ $item->matl_code }}</td>
                                <td>{{ $item->matl_descr }}</td>
                                <td style="text-align: center;">{{ $item->qty }}</td>
                                <td style="text-align: center;">{{ $item->matl_uom }}</td>
                            </tr>
                        @endforeach
                    @endif
                </x-slot>

                <x-slot name="footer">
                    <x-ui-button clickEvent="confirmReturnSelection" button-name="Confirm Selection" cssClass="btn-primary" />
                </x-slot>
            </x-ui-table>
        </x-slot>
    </x-ui-dialog-box>
</div>
