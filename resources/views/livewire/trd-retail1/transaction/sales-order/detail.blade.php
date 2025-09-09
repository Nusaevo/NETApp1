<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
    <x-ui-page-card isForm="true"
        title="{{ $this->trans($actionValue) }} {!! $menuName !!} {{ $this->object->tr_id ? ' (Nota #' . $this->object->tr_id . ')' : '' }}"
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
                        <x-ui-card title="Order Info">
                            <div class="row">
                                <x-ui-text-field label="Date" model="inputs.tr_date" type="date" :action="$actionValue"
                                    required="true" :enabled="$isPanelEnabled" />
                                {{-- <x-ui-text-field type="text" label="Supplier" model="inputs.partner_name"
                                    required="true" :action="$actionValue" enabled="false" clickEvent="openPartnerDialogBox"
                                    buttonName="Search" :buttonEnabled="$isPanelEnabled" /> --}}
                                <x-ui-dropdown-search
                                    label="{{ $this->trans('customer') }}"
                                    model="inputs.partner_id"
                                    query="SELECT id, code, name FROM partners WHERE deleted_at IS NULL AND grp='C'"
                                    connection="Default"
                                    optionValue="id"
                                    optionLabel="{code},{name}"
                                    placeHolder="Type to search customers..."
                                    :selectedValue="$inputs['partner_id']"
                                    required="true"
                                    :action="$actionValue"
                                    :enabled="$isPanelEnabled"
                                    type="int" />

                                {{-- Legacy partner dialog box removed - now using dropdown search component --}}
                                <x-ui-text-field label="Status" model="inputs.status_code_text" type="text"
                                    :action="$actionValue" required="false" enabled="false" />
                            </div>


                        </x-ui-card>
                    </div>

                    <x-ui-card title="Order Items">
                        <div>
                            <!-- Tambahkan kolom Warehouse -->

                            <div class="row">
                                <x-ui-dropdown-select label="Lokasi" model="wh_code" :options="$warehouseOptions" />
                                    <x-ui-text-field label="Klik di sini dan scan barcode" model="barcode" type="barcode" required="false" placeHolder="" span="Half" style="flex-grow: 1;" onChanged="scanBarcode" />

                            </div>

                            <x-ui-table id="Table">
                                <x-slot name="headers">
                                    <th style="width: 50px; text-align: center;">No</th>
                                    <th style="width: 100px; text-align: center;">Code</th>
                                    <th style="width: 100px; text-align: center;">UOM</th>
                                    <th style="width: 80px; text-align: center;">Image</th>
                                    <th style="width: 100px; text-align: center;">Harga Satuan</th>
                                    <th style="width: 80px; text-align: center;">Qty</th>
                                    <th style="width: 120px; text-align: center;">Amount</th>
                                    <th style="width: 70px; text-align: center;">Actions</th>
                                </x-slot>

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
                                                <x-ui-dropdown-select
                                                    wire:key="uom-{{ $key }}-{{ $input_details[$key]['matl_id'] ?? 'no-material' }}"
                                                    model="input_details.{{ $key }}.matl_uom"
                                                    :options="$materialUomOptions[$key] ?? []"
                                                    :selectedValue="$input_details[$key]['matl_uom'] ?? ''"
                                                             :action="$actionValue"
                                                    onChanged="onUomChanged({{ $key }}, $event.target.value)"
                                                />
                                            </td>
                                            <td style="text-align: center;">
                                                @if (!empty($input_details[$key]['image_url']))
                                                    <x-ui-image src="{{ $input_details[$key]['image_url'] }}"
                                                        alt="Material Image" width="50px" height="50px" />
                                                @else
                                                    <span>No Image</span>
                                                @endif
                                            </td>

                                            <td style="text-align: center;">
                                                <x-ui-text-field model="input_details.{{ $key }}.price"
                                                    label="" type="number" :action="$actionValue" enabled="true"
                                                    onChanged="updateItemAmount({{ $key }})" />
                                            </td>

                                            <td style="text-align: center;">
                                                <x-ui-text-field model="input_details.{{ $key }}.qty"
                                                    label="" enabled="true" type="number" required="true"
                                                    onChanged="updateItemAmount({{ $key }})" />
                                            </td>
                                            <td style="text-align: center;">
                                                <x-ui-text-field model="input_details.{{ $key }}.amt"
                                                    label="" type="number" enabled="false" />
                                            </td>
                                            <td style="text-align: center;">
                                                <x-ui-button :clickEvent="'deleteItem(' . $key . ')'" button-name="" loading="true"
                                                    :action="$actionValue" cssClass="btn-danger text-danger"
                                                    iconPath="delete.svg" />
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
                                    <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg"
                                        button-name="Add" />
                                    <x-ui-button clickEvent="openItemDialogBox" cssClass="btn btn-primary"
                                        iconPath="add.svg" button-name="Add Multiple Items" />
                                </x-slot>
                            </x-ui-table>

                            {{-- Material Selection Component --}}
                            @livewire('trd-retail1.component.material-selection', [
                                'dialogId' => 'ItemDialogBox',
                                'title' => 'Search Materials',
                                'width' => '900px',
                                'height' => '650px',
                                'enableFilters' => true,
                                'multiSelect' => true,
                                'eventName' => 'materialsSelected',
                                'additionalParams' => []
                            ])
                        </div>

                        <x-ui-card title="Info Pembayaran">
                            <div class="col-md-6">
                                <div class="row">
                                    <x-ui-dropdown-select label='' clickEvent=""
                                        model="inputs.payment_term_id" :options="$payments" :action="$actionValue" />
                                </div>
                            </div>
                        </x-ui-card>
                    </x-ui-card>

{{--
                    <div class="col-md-12">
                        <livewire:trd-retail1.transaction.sales-order.return-list-component
                        wire:model="return_details"
                        :action-value="$actionValue"
                        :object-id="$objectId"
                    /> --}}

                <x-ui-footer>
                    @include('layout.customs.transaction-form-footer')
                    <div>
                        @if ($actionValue === 'Edit')
                        <x-ui-button :action="$actionValue"
                            clickEvent="SaveAndPrint"
                            cssClass="btn-primary" loading="true" button-name="Print"
                            iconPath="print.svg" />
                        @endif
                        @include('layout.customs.buttons.save')
                    </div>
                </x-ui-footer>
            </div>
</div>
</x-ui-tab-view-content>
{{-- <x-ui-footer> --}}
{{-- @if ($actionValue === 'Edit')
            <x-ui-button :action="$actionValue" clickEvent="createReturn"
                cssClass="btn-primary" loading="true" button-name="Create Purchase Return" iconPath="add.svg" />
            @endif --}}
{{-- </x-ui-footer> --}}


</x-ui-page-card>
{{-- @php
    dump($input_details);
    @endphp --}}

</div>
