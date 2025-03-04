<div>
    <x-ui-card>

        <!-- Tambahkan kolom Warehouse -->
            <x-ui-dropdown-select
                label="Lokasi"
                model="wh_code"
                :options="$warehouseOptions"
            />
        <x-ui-table id="Table">
            <x-slot name="headers">
                <th style="width: 50px; text-align: center;">No</th>
                <th style="width: 100px; text-align: center;">Code</th>
                <th style="width: 100px; text-align: center;">UOM</th>
                <th style="width: 80px; text-align: center;">Image</th>
                <th style="width: 150px; text-align: center;">Harga Satuan</th>
                <th style="width: 80px; text-align: center;">Qty</th>
                <th style="width: 150px; text-align: center;">Amount</th>
                <th style="width: 70px; text-align: center;">Actions</th>
            </x-slot>

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
                            <x-ui-dropdown-select
                                model="input_details.{{ $key }}.matl_uom"
                                :options="$uomOptions"
                                onChanged="onUomChanged({{ $key }}, $event.target.value)"
                            />
                        </td>
                        <td style="text-align: center;">
                            @if (!empty($input_details[$key]['image_url']))
                                <x-ui-image src="{{ $input_details[$key]['image_url'] }}" alt="Material Image"
                                    width="50px" height="50px" />
                            @else
                                <span>No Image</span>
                            @endif
                        </td>

                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.price" label=""
                                type="number" :action="$actionValue" enabled="false" />
                        </td>

                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.qty" label="" enabled="true"
                                type="number" required="true" onChanged="updateItemAmount({{ $key }})" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.amt_idr" label=""
                                type="text" enabled="false" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-button :clickEvent="'deleteItem(' . $key . ')'" button-name="" loading="true" :action="$actionValue"
                                cssClass="btn-danger text-danger" iconPath="delete.svg" />
                        </td>
                    </tr>
                @endforeach

                <!-- Total Row -->
                <tr style="font-weight: bold; background-color: #f8f9fa;">
                    <td colspan="6" style="text-align: right;">Total</td>
                    <td style="text-align: center;">{{ rupiah($total_amount) }}</td>
                    <td></td>
                </tr>
            </x-slot>

            <x-slot name="button">
                <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg" button-name="Add" />
                <x-ui-button clickEvent="openItemDialogBox" cssClass="btn btn-primary" iconPath="add.svg"
                    button-name="Add Multiple Items" />
            </x-slot>
        </x-ui-table>
        <x-ui-dialog-box id="itemDialogBox" title="Search Item" width="600px" height="400px"
            onOpened="openItemDialogBox" onClosed="closeItemDialogBox">
            <x-slot name="body">
                <div class="row">
                    <x-ui-text-field type="text" label="Search Code/Nama" model="searchTerm"
                        required="true" :action="$actionValue" enabled="true" clickEvent="" buttonName="" />
                    <!-- Table -->
                    <x-ui-text-field-search label="Category" model="filterCategory" :options="$kategoriOptions" onChanged="" />
                </div>
                <div class="row">
                    <x-ui-text-field-search label="Brand" model="filterBrand" :options="$brandOptions" onChanged="" />
                    <x-ui-text-field-search label="Type" model="filterType" :options="$typeOptions" onChanged="" />
                </div>


                <x-ui-button clickEvent="searchMaterials" cssClass="btn btn-primary" button-name="Search" />
                <x-ui-table id="materialsTable" padding="0px" margin="0px" height="400px">
                    <x-slot name="headers">
                        <th class="min-w-100px">Code</th>
                        <th class="min-w-100px">Image</th>
                        <th class="min-w-100px">Name</th>
                        <th class="min-w-100px">Warna</th>
                    </x-slot>

                    <x-slot name="rows">
                        @if (empty($materialList))
                            <tr>
                                <td colspan="7" class="text-center text-muted">No Data Found</td>
                            </tr>
                        @else
                            @foreach ($materialList as $index => $material)
                                <tr wire:key="row-{{ $index }}-supplier">
                                    <td style="text-align: center;">
                                        <x-ui-option label="" required="false" layout="horizontal"
                                            enabled="true" type="checkbox" visible="true" :options="[$material['id'] => $material['code']]"
                                            onChanged="selectMaterial({{ $material['id'] }})" />
                                    </td>
                                    <td style="text-align: center;">
                                        @if (isset($material->Attachment) && $material->Attachment->first())
                                            <img src="{{ $material->Attachment->first()->getUrl() }}" alt="Image"
                                                style="width: 50px; height: 50px;">
                                        @else
                                            <span>No Image</span>
                                        @endif
                                    </td>
                                    <td>{{ $material->name }}</td>
                                    <td style="text-align: center;">
                                        {{ $material->specs['color_name'] }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </x-slot>

                    <x-slot name="footer">
                        <x-ui-button clickEvent="confirmSelection" button-name="Confirm Selection" loading="true"
                            :action="$actionValue" cssClass="btn-primary" />
                    </x-slot>
                </x-ui-table>

            </x-slot>
        </x-ui-dialog-box>
    </x-ui-card>

    <x-ui-footer>
        <x-ui-button clickEvent="SaveItem" button-name="Save Item" loading="true" :action="$actionValue"
            cssClass="btn-primary" iconPath="save.svg" />
    </x-ui-footer>
</div>
