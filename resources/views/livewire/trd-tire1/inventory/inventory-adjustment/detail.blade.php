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
                                        onChanged="onTypeChanged($event.target.value)" :enabled="$isPanelEnabled"/>
                                    <x-ui-text-field label="Tanggal Terima Barang" model="inputs.tr_date" type="date"
                                        :action="$actionValue" required="true" />
                                    <x-ui-text-field label="Nomor Transaksi" model="inputs.tr_code" :action="$actionValue"
                                        required="false" enabled="false" />
                                </div>
                                <div class="row">
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
                                    <th style="width: 150px; text-align: center;">Nama Barang</th>
                                    <th style="width: 50px; text-align: center;">Kode Batch</th>
                                    <th style="width: 50px; text-align: center;">Stock</th>
                                    <th style="width: 50px; text-align: center;">Quantity</th>
                                    <th style="width: 50px; text-align: center;">Stock Akhir</th>
                                    <th style="width: 70px; text-align: center;">Actions</th>
                                </x-slot>
                                <!-- Define table rows -->
                                <x-slot name="rows">
                                    @foreach ($input_details as $key => $input_detail)
                                        <tr wire:key="list{{ $input_detail['id'] ?? $key }}">
                                            <td style="text-align: center;">{{ $loop->iteration }}</td>
                                            <td>
                                                <x-ui-dropdown-select type="int" label=""
                                                    model="input_details.{{ $key }}.matl_id" :selectedValue="$input_detail['matl_id']"
                                                    :options="$filteredMaterials" required="true" :action="$actionValue"
                                                    :enabled="$isPanelEnabled"
                                                    onChanged="onMaterialChanged({{ $key }})" />
                                            </td>
                                            <td style="text-align: center;">
                                                <x-ui-dropdown-select type="int" label="" :options="$filteredBatchCode[$key] ?? []"
                                                    model="input_details.{{ $key }}.batch_code"
                                                    :selectedValue="$input_detail['batch_code'] ?? null"
                                                    :enabled="$isPanelEnabled"
                                                    :action="$actionValue"
                                                    required="true"
                                                    onChanged="onBatchCodeChanged({{ $key }})" />
                                            </td>
                                            <td style="text-align: center;">
                                                <x-ui-text-field model="input_details.{{ $key }}.qty_oh"
                                                    label="" :enabled="$isPanelEnabled" :action="$actionValue" type="text"
                                                    required="true" enabled="false" />
                                            </td>
                                            <td style="text-align: center;">
                                                <x-ui-text-field model="input_details.{{ $key }}.qty"
                                                    label="" :enabled="$isPanelEnabled" :action="$actionValue" type="text"
                                                    required="true" onChanged="onQtyChanged({{ $key }})" />
                                            </td>
                                            <td style="text-align: center;">
                                                <x-ui-text-field model="input_details.{{ $key }}.qty_end"
                                                    label="" :enabled="$isPanelEnabled" :action="$actionValue" type="text"
                                                    required="true" enabled="false" />
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
                                    <div class="row">
                                        <x-ui-dropdown-select label="{{ $this->trans('Gudang') }}"
                                            model="inputs.wh_code" :options="$warehouses" required="true" :action="$actionValue"
                                            :enabled="$isPanelEnabled" onChanged="onWarehouseChanged($event.target.value)" />
                                        <x-ui-dropdown-select label="{{ $this->trans('Gudang Tujuan') }}"
                                            model="inputs.wh_code2" :options="$warehouses" required="true"
                                            :enabled="$isEditWhCode2" />
                                    </div>
                                    <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg"
                                        button-name="Add" />
                                </x-slot>
                            </x-ui-table>
                        </x-ui-card>
                    </div>
                    <x-ui-footer>
                        <div>
                            <x-ui-button clickEvent="deleteTransaction" button-name="Hapus" loading="true"
                                :action="$actionValue" cssClass="btn-danger" iconPath="delete.svg" />

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
<script>
    Livewire.on('toggleWarehouseDropdown', (enabled) => {
        @this.set('isEditWhCode2', enabled);
    });
</script>
