<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
    <x-ui-page-card isForm="true"
        title="{{ $this->trans($actionValue) }} {!! $menuName !!} {{ $this->object->tr_id ? ' (#' . $this->object->tr_id . ')' : '' }}"
        status="{{ $this->trans($status) }}">

        @if ($actionValue === 'Create')
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @else
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @endif
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="General" role="tabpanel" aria-labelledby="general-tab">
                <div class="row mt-4">
                    <div class="col-md-12">
                        <x-ui-card title="Main Information">
                            <x-ui-padding>
                                <div class="row">
                                    <x-ui-dropdown-select label="{{ $this->trans('Tipe transaksi') }}"
                                        model="inputs.tr_type" :options="$warehousesType" required="true" :action="$actionValue"
                                        onChanged="onTypeChanged($event.target.value)" />
                                    <x-ui-dropdown-select label="{{ $this->trans('Gudang') }}"
                                        model="inputs.wh_code" :options="$warehouses" required="true" :action="$actionValue"
                                        onChanged="onWarehouseChanged($event.target.value)" enabled="false" />
                                    <x-ui-text-field label="Tanggal Terima Barang" model="inputs.tr_date" type="date"
                                        :action="$actionValue" required="true" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field label="Nomor Transaksi" model="inputs.tr_id" :action="$actionValue"
                                        required="false" enabled="false" />
                                    <x-ui-text-field label="{{ $this->trans('note') }}" model="inputs.remark"
                                        type="textarea" :action="$actionValue" required="false" />
                                </div>
                            </x-ui-padding>
                        </x-ui-card>
                        <br>
                        <x-ui-card>
                            <x-ui-table id="Table">
                                <!-- Define table headers -->
                                <x-slot name="headers">
                                    <th style="width: 50px; text-align: center;">No</th>
                                    <th style="width: 200px; text-align: center;">Material</th>
                                    <th style="width: 100px; text-align: center;">Stock Saat Ini</th>
                                    <th style="width: 100px; text-align: center;">Penambahan (+)</th>
                                    <th style="width: 100px; text-align: center;">Pengurangan (-)</th>
                                    <th style="width: 100px; text-align: center;">Stock Akhir</th>
                                    <th style="width: 70px; text-align: center;">Actions</th>
                                </x-slot>
                                <!-- Define table rows -->
                                <x-slot name="rows">
                                    @foreach ($input_details as $key => $input_detail)
                                        <tr wire:key="list{{ $input_detail['id'] ?? $key }}">
                                            <td style="text-align: center;">{{ $loop->iteration }}</td>
                                            <td>
                                               <x-ui-dropdown-search
                                                    model="input_details.{{ $key }}.matl_id"
                                                    query="SELECT id, code, name FROM materials WHERE status_code='A' AND deleted_at IS NULL"
                                                    connection="Default"
                                                    optionValue="id"
                                                    optionLabel="{code},{name}"
                                                    placeHolder="Search materials..."
                                                    :selectedValue="$input_details[$key]['matl_id'] ?? ''"
                                                    required="true"
                                                    :action="$actionValue"
                                                    enabled="true"
                                                    onChanged="onMaterialChanged({{ $key }}, $event.target.value)"
                                                    type="int" />
                                            </td>
                                            <td style="text-align: center;">
                                                <x-ui-text-field model="input_details.{{ $key }}.current_stock"
                                                    label="" :action="$actionValue"
                                                    type="text" required="false" enabled="false" />
                                            </td>
                                            <td style="text-align: center;">
                                                <x-ui-text-field model="input_details.{{ $key }}.qty_add"
                                                    label="" :enabled="$isPanelEnabled" :action="$actionValue"
                                                    onChanged="updateItemAmount({{ $key }})" type="number"
                                                    placeHolder="0" min="0" />
                                            </td>
                                            <td style="text-align: center;">
                                                <x-ui-text-field model="input_details.{{ $key }}.qty_subtract"
                                                    label="" :enabled="$isPanelEnabled" :action="$actionValue"
                                                    onChanged="updateItemAmount({{ $key }})" type="number"
                                                    placeHolder="0" min="0" />
                                            </td>
                                            <td style="text-align: center;">
                                                <x-ui-text-field model="input_details.{{ $key }}.final_stock"
                                                    label="" :action="$actionValue"
                                                    type="text" required="false" enabled="false" />
                                            </td>
                                            <td style="text-align: center;">
                                                @if (isset($input_detail['is_editable']) && $input_detail['is_editable'])
                                                    <x-ui-button :clickEvent="'deleteItem(' . $key . ')'" button-name="" loading="true"
                                                        :action="$actionValue" cssClass="btn-danger text-danger"
                                                        iconPath="delete.svg" :enabled="true" />
                                                @else
                                                    <!-- Misal, jika tidak editable, tombol delete bisa disembunyikan atau non-aktif -->
                                                    <x-ui-button :clickEvent="'deleteItem(' . $key . ')'" button-name="" loading="true"
                                                        :action="$actionValue" cssClass="btn-danger text-danger"
                                                        iconPath="delete.svg" :enabled="$isEdit" />
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </x-slot>
                                <x-slot name="button">
                                    <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg"
                                        button-name="Add"  :action="$actionValue"/>
                                    <x-ui-button clickEvent="openItemDialogBox" cssClass="btn btn-primary" iconPath="add.svg"
                                        button-name="Add Multiple Items" :action="$actionValue"/>
                                </x-slot>
                            </x-ui-table>

                            <!-- Add Multiple Items Dialog -->
                            <x-ui-dialog-box id="itemDialogBox" title="Search Materials for Adjustment" width="800px" height="500px"
                                onOpened="openItemDialogBox" onClosed="closeItemDialogBox">
                                <x-slot name="body">
                                    <div class="row mb-3">
                                        <x-ui-text-field type="text" label="Search Code/Name" model="searchTerm"
                                            required="false" :action="$actionValue" enabled="true"
                                            placeHolder="Type material code or name..." />
                                        <x-ui-dropdown-search
                                            label="Category"
                                            model="filterCategory"
                                            query="SELECT str1, str2 FROM config_consts WHERE const_group='MMATL_CATEGL1' AND deleted_at IS NULL"
                                            connection="Default"
                                            optionValue="str1"
                                            optionLabel="{str2}"
                                            placeHolder="Select category..."
                                            type="string" />
                                    </div>
                                    <div class="row mb-3">
                                        <x-ui-dropdown-search
                                            label="Brand"
                                            model="filterBrand"
                                            query="SELECT str1, str2 FROM config_consts WHERE const_group='MMATL_BRAND' AND deleted_at IS NULL"
                                            connection="Default"
                                            optionValue="str1"
                                            optionLabel="{str2}"
                                            placeHolder="Select brand..."
                                            type="string" />
                                        <x-ui-dropdown-search
                                            label="Type"
                                            model="filterType"
                                            query="SELECT str1, str2 FROM config_consts WHERE const_group='MMATL_TYPE' AND deleted_at IS NULL"
                                            connection="Default"
                                            optionValue="str1"
                                            optionLabel="{str2}"
                                            placeHolder="Select type..."
                                            type="string" />
                                    </div>

                                    <x-ui-button clickEvent="searchMaterials" cssClass="btn btn-primary mb-3" button-name="Search Materials" />

                                    <x-ui-table id="materialsTable" padding="0px" margin="0px" height="300px">
                                        <x-slot name="headers">
                                            <th style="width: 50px;">Select</th>
                                            <th style="width: 80px;">Image</th>
                                            <th style="width: 100px;">Code</th>
                                            <th>Name</th>
                                            <th style="width: 100px;">Current Stock</th>
                                            <th style="width: 120px;">Category</th>
                                        </x-slot>

                                        <x-slot name="rows">
                                            @if (empty($materialList))
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">No materials found. Click "Search Materials" to load data.</td>
                                                </tr>
                                            @else
                                                @foreach ($materialList as $index => $material)
                                                    <tr wire:key="material-row-{{ $material['id'] ?? $index }}">
                                                        <td style="text-align: center;">
                                                            <x-ui-option label="" required="false" layout="horizontal"
                                                                enabled="true" type="checkbox" visible="true"
                                                                :options="[$material['id'] => '']"
                                                                onChanged="selectMaterial({{ $material['id'] }})" />
                                                        </td>
                                                        <td style="text-align: center;">
                                                            @if (isset($material['image_url']) && !empty($material['image_url']))
                                                                <img src="{{ $material['image_url'] }}" alt="Material Image"
                                                                    style="width: 40px; height: 40px; object-fit: cover;">
                                                            @else
                                                                <span class="text-muted">No Image</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $material['code'] ?? '-' }}</td>
                                                        <td>{{ $material['name'] ?? '-' }}</td>
                                                        <td style="text-align: center;">{{ $material['current_stock'] ?? 0 }}</td>
                                                        <td>{{ $material['category'] ?? '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </x-slot>

                                        <x-slot name="footer">
                                            <x-ui-button clickEvent="confirmSelection" button-name="Add Selected Materials"
                                                loading="true" :action="$actionValue" cssClass="btn-success" />
                                        </x-slot>
                                    </x-ui-table>
                                </x-slot>
                            </x-ui-dialog-box>
                        </x-ui-card>
                    </div>
                    <x-ui-footer>
                        <div>
                            @include('layout.customs.buttons.save')

                        </div>
                    </x-ui-footer>
                </div>
                <br>
                {{-- <div class="col-md-12">
                    <x-ui-card title="Order Items">
                        @livewire($currentRoute . '.material-list-component', ['action' => $action, 'objectId' => $objectId, 'wh_code' => $inputs['wh_code'], 'tr_type' => $inputs['tr_type']])
                    </x-ui-card>
                </div> --}}

            </div>
        </x-ui-tab-view-content>
    </x-ui-page-card>
</div>
