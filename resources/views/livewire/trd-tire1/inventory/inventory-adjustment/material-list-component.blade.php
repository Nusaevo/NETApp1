<div>
    <x-ui-card>
        <x-ui-table id="Table">
            <!-- Define table headers -->
            <x-slot name="headers">
                <th style="width: 50px; text-align: center;">No</th>
                <th style="width: 150px; text-align: center;">Code</th>
                <th style="width: 50px; text-align: center;">Quantity</th>
                <th style="width: 70px; text-align: center;">Actions</th>
            </x-slot>
            <!-- Define table rows -->
            <x-slot name="rows">
                @foreach ($input_details as $key => $input_detail)
                    <tr wire:key="list{{ $input_detail['id'] ?? $key }}">
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td>
                            @if (isset($input_detail['is_editable']) && $input_detail['is_editable'])
                                <x-ui-text-field-search type="int" label="" clickEvent=""
                                    model="input_details.{{ $key }}.matl_id" :selectedValue="$input_detail['matl_id']"
                                    :options="$filteredMaterials" required="true" :action="$actionValue" :enabled="true" />
                            @else
                                @php
                                    $material = $filteredMaterials ? $filteredMaterials->firstWhere('value', $input_detail['matl_id']) : null;
                                @endphp
                                {{ $material['label'] ?? '' }}
                            @endif
                            @dump($input_details)
                        </td>
                        <td style="text-align: center;">
                            @if (isset($input_detail['is_editable']) && $input_detail['is_editable'])
                                <x-ui-text-field model="input_details.{{ $key }}.qty" label=""
                                    :enabled="true" :action="$actionValue"
                                    onChanged="updateItemAmount({{ $key }})"  type="text"
                                    required="true" />
                            @else
                                {{ $input_detail['qty'] }}
                            @endif
                        </td>
                        <td style="text-align: center;">
                            @if (isset($input_detail['is_editable']) && $input_detail['is_editable'])
                                <x-ui-button :clickEvent="'deleteItem(' . $key . ')'" button-name="" loading="true" :action="$actionValue"
                                    cssClass="btn-danger text-danger" iconPath="delete.svg" :enabled="true" />
                            @else
                                <!-- Misal, jika tidak editable, tombol delete bisa disembunyikan atau non-aktif -->
                                <x-ui-button :clickEvent="'deleteItem(' . $key . ')'" button-name="" loading="true" :action="$actionValue"
                                    cssClass="btn-danger text-danger" iconPath="delete.svg" :enabled="$isEdit" />
                            @endif
                        </td>
                    </tr>
                @endforeach
            </x-slot>
            <x-slot name="button">
                <div class="row">
                    <x-ui-dropdown-select label="{{ $this->trans('Gudang') }}" model="inputs.wh_code" :options="$warehouses"
                        required="true" :action="$actionValue" :enabled="$isEdit"
                        onChanged="onWarehouseChanged($event.target.value)" />
                    <x-ui-dropdown-select label="{{ $this->trans('Gudang Tujuan') }}" model="inputs.wh_code2"
                        :options="$warehouses" required="true" :enabled="$isEditWhCode2" />
                </div>
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

<script>
    Livewire.on('toggleWarehouseDropdown', (enabled) => {
        @this.set('isEditWhCode2', enabled);
    });
</script>
