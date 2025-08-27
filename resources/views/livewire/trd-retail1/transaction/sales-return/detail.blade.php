<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
  <x-ui-page-card isForm="true"
        title="{{ $this->trans($actionValue) }} {!! $menuName !!} "
        status="{{ $this->trans($status) }}">

        <div class="row mt-4">
            <div class="col-md-12">
                <x-ui-card title="Return Info">
                    <div class="row">
                        <x-ui-text-field label="Return Date" model="inputs.tr_date" type="date" :action="$actionValue"
                            required="true" :enabled="$isPanelEnabled" />
                        <x-ui-dropdown-search
                            label="Customer"
                            model="inputs.partner_id"
                            query="SELECT id, code, name FROM partners WHERE deleted_at IS NULL AND grp='C'"
                            connection="Default"
                            optionValue="id"
                            optionLabel="code,name"
                            placeHolder="Type to search customers..."
                            :selectedValue="$inputs['partner_id']"
                            required="true"
                            :action="$actionValue"
                            :enabled="$isPanelEnabled"
                            type="int" />
                        <x-ui-text-field label="Status" model="inputs.status_code_text" type="text"
                            :action="$actionValue" required="false" enabled="false" />
                    </div>
                </x-ui-card>
            </div>

            <div class="col-md-12">
                <x-ui-card title="Barang Retur"
                    description="Barang yang diretur akan mengembalikan stock">
                    <div>
                        <div class="row">
                            <x-ui-dropdown-select label="Gudang Barang Retur" model="wh_code" :options="$warehouseOptions" />
                            <x-ui-text-field label="Klik di sini dan scan barcode retur" model="barcode" type="barcode"
                                required="false" placeHolder="" span="Half" style="flex-grow: 1;" onChanged="scanBarcode" />
                        </div>

                        <x-ui-table id="ReturnTable">
                            <x-slot name="headers">
                                <th style="width: 50px; text-align: center;">No</th>
                                <th style="width: 100px; text-align: center;">Code</th>
                                <th style="width: 100px; text-align: center;">UOM</th>
                                <th style="width: 80px; text-align: center;">Image</th>
                                <th style="width: 100px; text-align: center;">Harga Satuan</th>
                                <th style="width: 80px; text-align: center;">Qty Return</th>
                                <th style="width: 120px; text-align: center;">Amount</th>
                                <th style="width: 70px; text-align: center;">Actions</th>
                            </x-slot>

                            <x-slot name="rows">
                                @foreach ($input_details as $key => $input_detail)
                                    <tr wire:key="return-list{{ $input_detail['id'] ?? $key }}">
                                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                                        <td>
                                            <x-ui-dropdown-search
                                                model="input_details.{{ $key }}.matl_id"
                                                query="SELECT id, code, name FROM materials WHERE status_code='A' AND deleted_at IS NULL"
                                                connection="Default"
                                                optionValue="id"
                                                optionLabel="code,name"
                                                placeHolder="Search materials..."
                                                :selectedValue="$input_details[$key]['matl_id'] ?? ''"
                                                required="true"
                                                :action="$actionValue"
                                                enabled="true"
                                                onChanged="onMaterialChanged({{ $key }}, $event.target.value)"
                                                type="int" />
                                        </td>
                                        <td style="text-align: center;">
                                            <x-ui-dropdown-select model="input_details.{{ $key }}.matl_uom"
                                                :options="$uomOptions" />
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
                                                onChanged="updateReturnItemAmount({{ $key }})" />
                                        </td>

                                        <td style="text-align: center;">
                                            <x-ui-text-field model="input_details.{{ $key }}.qty"
                                                label="" enabled="true" type="number" required="true"
                                                onChanged="updateReturnItemAmount({{ $key }})" />
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

                                <!-- Total Return Row -->
                                <tr style="font-weight: bold; background-color: #f8f9fa;">
                                    <td colspan="6" style="text-align: right;">Total Retur</td>
                                    <td style="text-align: center;">{{ rupiah($total_return_amount) }}</td>
                                    <td></td>
                                </tr>
                            </x-slot>

                            <x-slot name="button">
                                <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg"
                                    button-name="Add Return Item" />
                                <x-ui-button clickEvent="openItemDialogBox" cssClass="btn btn-primary"
                                    iconPath="add.svg" button-name="Add Multiple Return Items" />
                            </x-slot>
                        </x-ui-table>

                        <!-- Dialog untuk memilih return item -->
                            <x-ui-dialog-box id="itemDialogBox" title="Search Return Item" width="600px" height="1000px"
                                onOpened="openItemDialogBox" onClosed="closeItemDialogBox">
                                <x-slot name="body">
                                    <div class="row">
                                        <x-ui-text-field type="text" label="Search Code/Nama" model="searchTerm"
                                            required="true" :action="$actionValue" enabled="true" clickEvent=""
                                            buttonName="" />
                                        <x-ui-dropdown-search
                                            label="Category"
                                            model="filterCategory"
                                            query="SELECT str1, str2 FROM config_consts WHERE const_group='MMATL_CATEGL1' AND deleted_at IS NULL"
                                            connection="Default"
                                            optionValue="str1"
                                            optionLabel="str2"
                                            placeHolder="Select category..."
                                            type="string" />
                                    </div>
                                    <div class="row">
                                        <x-ui-dropdown-search
                                            label="Brand"
                                            model="filterBrand"
                                            query="SELECT str1, str2 FROM config_consts WHERE const_group='MMATL_BRAND' AND deleted_at IS NULL"
                                            connection="Default"
                                            optionValue="str1"
                                            optionLabel="str2"
                                            placeHolder="Select brand..."
                                            type="string" />
                                        <x-ui-dropdown-search
                                            label="Type"
                                            model="filterType"
                                            query="SELECT str1, str2 FROM config_consts WHERE const_group='MMATL_TYPE' AND deleted_at IS NULL"
                                            connection="Default"
                                            optionValue="str1"
                                            optionLabel="str2"
                                            placeHolder="Select type..."
                                            type="string" />
                                    </div>

                                    <x-ui-button clickEvent="searchMaterials" cssClass="btn btn-primary"
                                        button-name="Search" />
                                    <x-ui-table id="materialsTable" padding="0px" margin="0px" height="400px">
                                        <x-slot name="headers">
                                            <th class="min-w-100px">Code</th>
                                            <th class="min-w-100px">Image</th>
                                            <th class="min-w-100px">Name</th>
                                            <th class="min-w-100px">Buying Price</th>
                                            <th class="min-w-100px">Selling Price</th>
                                        </x-slot>

                                        <x-slot name="rows">
                                            @if (empty($materialList))
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">No Data Found</td>
                                                </tr>
                                            @else
                                                @foreach ($materialList as $index => $material)
                                                    <tr wire:key="row-{{ $index }}-return-material">
                                                        <td style="text-align: center;">
                                                            <x-ui-option label="" required="false"
                                                                layout="horizontal" enabled="true" type="checkbox"
                                                                visible="true" :options="[$material['id'] => $material['code']]"
                                                                onChanged="selectMaterial({{ $material['id'] }})" />
                                                        </td>
                                                        <td style="text-align: center;">
                                                            @if (isset($material->Attachment) && $material->Attachment->first())
                                                                <img src="{{ $material->Attachment->first()->getUrl() }}"
                                                                    alt="Image" style="width: 50px; height: 50px;">
                                                            @else
                                                                <span>No Image</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $material->name }}</td>
                                                        <td style="text-align: right;">{{ rupiah($material->buying_price ?? 0) }}</td>
                                                        <td style="text-align: right;">{{ rupiah($material->selling_price ?? 0) }}</td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </x-slot>

                                        <x-slot name="footer">
                                            <x-ui-button clickEvent="confirmSelection" button-name="Confirm Selection"
                                                loading="true" :action="$actionValue" cssClass="btn-primary" />
                                        </x-slot>
                                    </x-ui-table>
                        </x-slot>
                    </x-ui-dialog-box>
                </div>
            </x-ui-card>
        </div>

        <div class="col-md-12">
            <x-ui-card title="Barang Pengganti"
                description="">
                <div>
                    <div class="row">
                        <x-ui-dropdown-select label="Gudang Barang Pengganti" model="exchange_wh_code" :options="$warehouseOptions" />
                        <x-ui-text-field label="Klik di sini dan scan barcode pengganti" model="exchangeBarcode" type="barcode"
                            required="false" placeHolder="" span="Half" style="flex-grow: 1;" onChanged="scanExchangeBarcode" />
                    </div>
                </div>

                <x-ui-table id="ExchangeTable">
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
                        @foreach ($exchange_details as $key => $exchange_detail)
                            <tr wire:key="exchange-list{{ $exchange_detail['id'] ?? $key }}">
                                <td style="text-align: center;">{{ $loop->iteration }}</td>
                                <td>
                                    <x-ui-dropdown-search
                                        model="exchange_details.{{ $key }}.matl_id"
                                        query="SELECT id, code, name FROM materials WHERE status_code='A' AND deleted_at IS NULL"
                                        connection="Default"
                                        optionValue="id"
                                        optionLabel="code,name"
                                        placeHolder="Search materials..."
                                        :selectedValue="$exchange_details[$key]['matl_id'] ?? ''"
                                        required="true"
                                        :action="$actionValue"
                                        enabled="true"
                                        onChanged="onExchangeMaterialChanged({{ $key }}, $event.target.value)"
                                        type="int" />
                                </td>
                                <td style="text-align: center;">
                                    <x-ui-dropdown-select model="exchange_details.{{ $key }}.matl_uom"
                                        :options="$uomOptions" />
                                </td>
                                <td style="text-align: center;">
                                    @if (!empty($exchange_details[$key]['image_url']))
                                        <x-ui-image src="{{ $exchange_details[$key]['image_url'] }}"
                                            alt="Material Image" width="50px" height="50px" />
                                    @else
                                        <span>No Image</span>
                                    @endif
                                </td>

                                <td style="text-align: center;">
                                    <x-ui-text-field model="exchange_details.{{ $key }}.price"
                                        label="" type="number" :action="$actionValue" enabled="true"
                                        onChanged="updateExchangeItemAmount({{ $key }})" />
                                </td>

                                <td style="text-align: center;">
                                    <x-ui-text-field model="exchange_details.{{ $key }}.qty"
                                        label="" enabled="true" type="number" required="true"
                                        onChanged="updateExchangeItemAmount({{ $key }})" />
                                </td>
                                <td style="text-align: center;">
                                    <x-ui-text-field model="exchange_details.{{ $key }}.amt"
                                        label="" type="number" enabled="false" />
                                </td>
                                <td style="text-align: center;">
                                    <x-ui-button :clickEvent="'deleteExchangeItem(' . $key . ')'" button-name="" loading="true"
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
                        <x-ui-button clickEvent="addExchangeItem" cssClass="btn btn-primary" iconPath="add.svg"
                            button-name="Add Exchange Item" />
                        <x-ui-button clickEvent="openExchangeItemDialogBox" cssClass="btn btn-primary"
                            iconPath="add.svg" button-name="Add Multiple Exchange Items" />
                    </x-slot>
                </x-ui-table>

                <!-- Dialog untuk memilih exchange item -->
                <x-ui-dialog-box id="exchangeItemDialogBox" title="Search Exchange Item" width="600px" height="1000px"
                    onOpened="openExchangeItemDialogBox" onClosed="closeExchangeItemDialogBox">
                    <x-slot name="body">
                        <div class="row">
                            <x-ui-text-field type="text" label="Search Code/Nama" model="exchangeSearchTerm"
                                required="true" :action="$actionValue" enabled="true" clickEvent=""
                                buttonName="" />
                        </div>

                        <x-ui-button clickEvent="searchExchangeMaterials" cssClass="btn btn-primary"
                            button-name="Search" />
                        <x-ui-table id="exchangeMaterialsTable" padding="0px" margin="0px" height="400px">
                            <x-slot name="headers">
                                <th class="min-w-100px">Code</th>
                                <th class="min-w-100px">Image</th>
                                <th class="min-w-100px">Name</th>
                                <th class="min-w-100px">Buying Price</th>
                                <th class="min-w-100px">Selling Price</th>
                            </x-slot>

                            <x-slot name="rows">
                                @if (empty($exchangeMaterialList))
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No Data Found</td>
                                    </tr>
                                @else
                                    @foreach ($exchangeMaterialList as $index => $material)
                                        <tr wire:key="exchange-row-{{ $index }}-material">
                                            <td style="text-align: center;">
                                                <x-ui-option label="" required="false"
                                                    layout="horizontal" enabled="true" type="checkbox"
                                                    visible="true" :options="[$material['id'] => $material['code']]"
                                                    onChanged="selectExchangeMaterial({{ $material['id'] }})" />
                                            </td>
                                            <td style="text-align: center;">
                                                @if (isset($material->Attachment) && $material->Attachment->first())
                                                    <img src="{{ $material->Attachment->first()->getUrl() }}"
                                                        alt="Image" style="width: 50px; height: 50px;">
                                                @else
                                                    <span>No Image</span>
                                                @endif
                                            </td>
                                            <td>{{ $material->name }}</td>
                                            <td style="text-align: right;">{{ rupiah($material->buying_price ?? 0) }}</td>
                                            <td style="text-align: right;">{{ rupiah($material->selling_price ?? 0) }}</td>
                                        </tr>
                                    @endforeach
                                @endif
                            </x-slot>

                            <x-slot name="footer">
                                <x-ui-button clickEvent="confirmExchangeSelection" button-name="Confirm Exchange Selection"
                                    loading="true" :action="$actionValue" cssClass="btn-primary" />
                            </x-slot>
                        </x-ui-table>
                    </x-slot>
                </x-ui-dialog-box>
            </x-ui-card>
        </div>

        <div class="col-md-12">
            <x-ui-card title="Info Pembayaran">
                <div class="col-md-6">
                    <div class="row">
                        <x-ui-dropdown-select label="Payment Term" clickEvent=""
                            model="inputs.payment_term_id" :options="$payments" :action="$actionValue" />
                    </div>
                </div>
            </x-ui-card>
        </div>
    </div>        <x-ui-footer>
            @include('layout.customs.transaction-form-footer')
            <div>
                @if ($actionValue === 'Edit')
                    <x-ui-button :action="$actionValue"
                        clickEvent="SaveAndPrint"
                        cssClass="btn-primary" loading="true" button-name="Print Return"
                        iconPath="print.svg" />
                @endif
                @include('layout.customs.buttons.save')
            </div>
        </x-ui-footer>
    </x-ui-page-card>
</div>
