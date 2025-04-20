{{-- <div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
    <x-ui-page-card isForm="true"
        title="{{ $this->trans($actionValue) }} {!! $menuName !!} {{ $this->object->code ? ' (Nota #' . $this->object->code . ')' : '' }}"
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
                                    <x-ui-text-field label="Kode Program" model="inputs.code" type="text"
                                        :action="$actionValue" required="true" />
                                    <x-ui-text-field label="Nama Program" model="inputs.descrs" type="text"
                                        :action="$actionValue" required="true" />
                                    <x-ui-text-field label="Periode Awal" model="inputs.beg_date" type="date"
                                        :action="$actionValue" required="true" />
                                    <x-ui-text-field label="Periode AKhir" model="inputs.end_date" type="date"
                                        :action="$actionValue" required="true" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field-search type="int" label="Nama Barang" clickEvent=""
                                        model="inputs.matl_id" :options="$materials" required="true" :action="$actionValue"
                                        :enabled="true" />
                                    <x-ui-text-field label="Group" model="inputs.grp" type="text" :action="$actionValue"
                                        required="true" />
                                    <x-ui-text-field label="qty" model="inputs.qty" type="number" :action="$actionValue"
                                        required="true" />
                                    <x-ui-text-field label="Reward" model="inputs.reward" type="number"
                                        :action="$actionValue" required="true" />
                                </div>
                            </x-ui-padding>
                        </x-ui-card>
                    </div>
                    <x-ui-footer>
                        <div>
                            <x-ui-button clickEvent="Save" button-name="Save" loading="true" :action="$actionValue"
                                cssClass="btn-primary" iconPath="save.svg" />
                        </div>
                    </x-ui-footer>
                </div>
                <br>
        </x-ui-tab-view-content>
    </x-ui-page-card>
</div> --}}

<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
    <x-ui-card
        title="{{ $this->trans($actionValue) }} {!! $menuName !!} {{ $this->object->code ? ' (Nota #' . $this->object->code . ')' : '' }}"
        status="{{ $this->trans($status) }}">


        <!-- Tambahkan kolom Warehouse -->
        {{-- <x-ui-dropdown-select
                label="Lokasi"
                model="wh_code"
                :options="$warehouseOptions"
            /> --}}
        <x-ui-table id="Table">
            <x-slot name="headers">
                <th style="width: 50px; text-align: center;">No</th>
                <th style="width: 100px; text-align: center;">Nama Barang</th>
                <th style="width: 100px; text-align: center;">Group</th>
                <th style="width: 80px; text-align: center;">Qty</th>
                <th style="width: 150px; text-align: center;">Reward</th>
                <th style="width: 70px; text-align: center;">Actions</th>
            </x-slot>

            <x-slot name="rows">
                @foreach ($input_details as $key => $input_detail)
                    <tr wire:key="list{{ $input_detail['id'] ?? $key }}">
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td>
                            <x-ui-text-field-search type="int" label="" clickEvent=""
                                model="input_details.{{ $key }}.matl_id" :selectedValue="$input_details[$key]['matl_id']" :options="$materials"
                                required="true" :action="$actionValue" :enabled="true"
                                onChanged="onMaterialChanged({{ $key }}, $event.target.value)" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field label="" model="input_details.{{ $key }}.grp" type="text"
                                :action="$actionValue" required="true" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.qty" label="" type="text"
                                enabled="true" />
                        </td>

                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.reward" label=""
                                type="number" :action="$actionValue" enabled="true" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-button :clickEvent="'deleteItem(' . $key . ')'" button-name="" loading="true" :action="$actionValue"
                                cssClass="btn-danger text-danger" iconPath="delete.svg" />
                        </td>
                    </tr>
                @endforeach
            </x-slot>

            <x-slot name="button">
                <div class="row">
                    <x-ui-text-field label="Kode Program" model="inputs.code" type="text" :action="$actionValue"
                        required="true" />
                    <x-ui-text-field label="Nama Program" model="inputs.descrs" type="text" :action="$actionValue"
                        required="true" />
                    <x-ui-text-field label="Periode Awal" model="inputs.beg_date" type="date" :action="$actionValue"
                        required="true" />
                    <x-ui-text-field label="Periode AKhir" model="inputs.end_date" type="date" :action="$actionValue"
                        required="true" />
                </div>
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
                        :action="$actionValue" enabled="true" clickEvent="" buttonName="" />
                    <!-- Table -->
                    <x-ui-text-field-search label="Category" model="filterCategory" :options="$kategoriOptions" onChanged="" />
                </div>
                <div class="row">
                    <x-ui-text-field-search label="Merk" model="filterBrand" :options="$brandOptions" onChanged="" />
                    <x-ui-text-field-search label="Type" model="filterType" :options="$typeOptions" onChanged="" />
                </div>

                <x-ui-button clickEvent="searchMaterials" cssClass="btn btn-primary" button-name="Search" />
                <div class="row mt-2">
                    <x-ui-text-field label="Group" model="groupInput" type="text" :action="$actionValue" />
                    <x-ui-text-field label="Qty" model="qtyInput" type="number" :action="$actionValue" />
                    <x-ui-text-field label="Reward" model="rewardInput" type="number" :action="$actionValue" />
                </div>
                <x-ui-table id="materialsTable" padding="0px" margin="0px" height="400px">
                    <x-slot name="headers">
                        <th class="min-w-100px">
                            Code
                        </th>
                        <th class="min-w-100px">Merk</th>
                        <th class="min-w-100px">Kategori</th>
                        <th class="min-w-100px">Nama</th>
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
                                    <td>{{ $material->brand }}</td>
                                    <td>{{ $material->category }}</td>
                                    <td>{{ $material->name }}</td>
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
        <x-ui-button :action="$actionValue"
            clickEvent="{{ route('TrdTire1.Master.SalesReward.PrintPdf', [
                'action' => encryptWithSessionKey('Edit'),
                'objectId' => encryptWithSessionKey($inputs['code']), // Pass the correct code
            ]) }}"
            cssClass="btn-primary" type="Route" loading="true" button-name="Cetak" iconPath="print.svg" />
        <x-ui-button clickEvent="Save" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary"
            iconPath="save.svg" />
    </x-ui-footer>
    <br>
</div>
