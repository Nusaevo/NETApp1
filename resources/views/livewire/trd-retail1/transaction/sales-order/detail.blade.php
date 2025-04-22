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
                                <x-ui-text-field-search type="int" label="{{ $this->trans('customer') }}"
                                    clickEvent="" model="inputs.partner_id" :selectedValue="$inputs['partner_id']" :options="$partners"
                                    required="true" :action="$actionValue" :enabled="$isPanelEnabled" />

                                {{-- <x-ui-dialog-box id="partnerDialogBox" title="Search Supplier" width="600px"
                                    height="400px" onOpened="openPartnerDialogBox" onClosed="closePartnerDialogBox">
                                    <x-slot name="body">
                                        <x-ui-text-field type="text" label="Search Code/Nama Supplier"
                                            model="partnerSearchText" required="true" :action="$actionValue" enabled="true"
                                            clickEvent="searchPartners" buttonName="Search" />
                                        <!-- Table -->
                                        <x-ui-table id="partnersTable" padding="0px" margin="0px">
                                            <x-slot name="headers">
                                                <th class="min-w-100px">Code</th>
                                                <th class="min-w-100px">Name</th>
                                                <th class="min-w-100px">Address</th>
                                            </x-slot>
                                            <x-slot name="rows">
                                                @if (empty($suppliers))
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">No Data Found
                                                        </td>
                                                    </tr>
                                                @else
                                                    @foreach ($suppliers as $key => $supplier)
                                                        <tr wire:key="row-{{ $key }}-supplier">
                                                            <td>
                                                                <x-ui-option label="" required="false"
                                                                    layout="horizontal" enabled="true" type="checkbox"
                                                                    visible="true" :options="[$supplier['id'] => $supplier['code']]"
                                                                    onChanged="selectPartner({{ $supplier['id'] }})" />
                                                            </td>
                                                            <td>{{ $supplier['name'] }}</td>
                                                            <td>{{ $supplier['address'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            </x-slot>
                                            <x-slot name="footer">
                                                <x-ui-button clickEvent="confirmSelection"
                                                    button-name="Confirm Selection" loading="true" :action="$actionValue"
                                                    cssClass="btn-primary" />
                                            </x-slot>
                                        </x-ui-table>
                                    </x-slot>
                                </x-ui-dialog-box> --}}
                                <x-ui-text-field label="Status" model="inputs.status_code_text" type="text"
                                    :action="$actionValue" required="false" enabled="false" />
                            </div>
                        </x-ui-card>
                    </div>
                    <div class="col-md-12">
                        <x-ui-card title="Order Items">
                            <div>
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
                                                        <x-ui-text-field-search type="int" label="" clickEvent=""
                                                            model="input_details.{{ $key }}.matl_id" :selectedValue="$input_details[$key]['matl_id']" :options="$materials"
                                                            required="true" :action="$actionValue"
                                                            onChanged="onMaterialChanged({{ $key }}, $event.target.value)"
                                                            enabled="true" />
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
                                                            type="number" :action="$actionValue" enabled="true" onChanged="updateItemAmount({{ $key }})"/>
                                                    </td>

                                                    <td style="text-align: center;">
                                                        <x-ui-text-field model="input_details.{{ $key }}.qty" label="" enabled="true"
                                                            type="number" required="true" onChanged="updateItemAmount({{ $key }})" />
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <x-ui-text-field model="input_details.{{ $key }}.amt" label=""
                                                            type="number" enabled="false" />
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
                            </div>

                        </x-ui-card>
                        @if ($actionValue === 'Edit')
                        <x-ui-card title="Barang Retur">
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
                        </x-ui-card>
                        @endif
                    </div>
                    <x-ui-footer>
                        @include('layout.customs.transaction-form-footer')
                        <div>
                            <x-ui-button :action="$actionValue" clickEvent="{{ route($this->appCode.'.Transaction.SalesOrder.PrintPdf',
                            ['action' => encryptWithSessionKey('Edit'), 'objectId' => encryptWithSessionKey($object->id)]) }}"
                                cssClass="btn-primary" type="Route" loading="true" button-name="Print" iconPath="print.svg" />
                            <x-ui-button clickEvent="Save" button-name="Save" loading="true" :action="$actionValue"
                                cssClass="btn-primary" iconPath="save.svg" />
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
