<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
    <x-ui-page-card isForm="true"
        title="{{ $this->trans($actionValue) }} {!! $menuName !!} {{ $this->object->tr_code ? ' (Nota #' . $this->object->tr_code . ')' : '' }}"
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
                                    <x-ui-option model="inputs.sales_type" :options="['I' => 'MOTOR', 'O' => 'MOBIL']" type="radio"
                                        layout="horizontal" :action="$actionValue" :enabled="$isPanelEnabled"
                                        onChanged="onSalesTypeChanged" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field label="{{ $this->trans('tr_code') }}" model="inputs.tr_code"
                                        type="code" :action="$actionValue" required="false"
                                        clickEvent="getTransactionCode" buttonName="Nomor Baru" enabled="false"
                                        :buttonEnabled="$isPanelEnabled" />
                                    <x-ui-text-field label="Tanggal Transaksi" model="inputs.tr_date" type="date"
                                        :action="$actionValue" required="true" :enabled="$isPanelEnabled" />
                                    <x-ui-dropdown-select label="{{ $this->trans('payment_terms') }}"
                                        model="inputs.payment_term_id" :options="$paymentTerms" required="true"
                                        :action="$actionValue" onChanged="onPaymentTermChanged" />
                                    <x-ui-text-field label="Tanggal Jatuh Tempo" model="inputs.due_date" type="date"
                                        :action="$actionValue" required="true" :enabled="$isPanelEnabled" />
                                </div>
                                <div class="row">
                                    <x-ui-dropdown-search label="{{ $this->trans('Supplier') }}"
                                        model="inputs.partner_id"
                                        query="SELECT id, code, name, address, city FROM partners WHERE deleted_at IS NULL AND grp = 'V'"
                                        optionValue="id" optionLabel="code,name,address,city"
                                        placeHolder="Type to search suppliers..." :selectedValue="$inputs['partner_id']" required="true"
                                        :action="$actionValue" :enabled="$isPanelEnabled" type="int" />
                                    {{-- @dump($inputs['partner_id']) --}}
                                    <x-ui-dropdown-select label="{{ $this->trans('PPN') }}" model="inputs.tax_code"
                                        :options="$SOTax" required="true" :action="$actionValue"
                                        onChanged="onSOTaxChange" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field label="{{ $this->trans('note') }}" model="inputs.note"
                                        type="textarea" :action="$actionValue" required="false" />
                                </div>
                            </x-ui-padding>
                        </x-ui-card>
                    </div>
                </div>
                <br>
                <div class="col-md-12">
                    <x-ui-card title="Item Barang">
                        <x-ui-table id="Table">
                            <!-- Define table headers -->
                            <x-slot name="headers">
                                <th style="width: 50px; text-align: center;">No</th>
                                <th style="width: 150px; text-align: center;">Nama Barang</th>
                                <th style="width: 150px; text-align: center;">Harga Satuan</th>
                                <th style="width: 50px; text-align: center;">Quantity</th>
                                <th style="width: 90px; text-align: center;">Disc (%)</th>
                                <th style="width: 150px; text-align: center;">Jumlah</th>
                                <th style="width: 70px; text-align: center;">Aksi</th>
                            </x-slot>

                            <!-- Define table rows -->
                            <x-slot name="rows">
                                @foreach ($input_details ?? [] as $key => $input_detail)
                                    <tr wire:key="list{{ $input_detail['id'] ?? $key }}">
                                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                                        <td>
                                            {{-- <x-ui-dropdown-search model="input_details.{{ $key }}.matl_id"
                                                query="SELECT id, code, name FROM materials WHERE status_code='A' AND deleted_at IS NULL"
                                                optionValue="id" optionLabel="code,name"
                                                placeHolder="Search materials..." :selectedValue="$input_details[$key]['matl_id'] ?? ''" required="true"
                                                :action="$actionValue" enabled="true"
                                                onChanged="onMaterialChanged({{ $key }}, $event.target.value)"
                                                type="int" /> --}}
                                            @php
                                                $materialQuery = "SELECT id, code, name FROM materials WHERE status_code='A' AND deleted_at IS NULL";
                                                if (($inputs['sales_type'] ?? '') == 'I') {
                                                    $materialQuery .= " AND (category = 'BAN DALAM MOTOR' OR category = 'BAN LUAR MOTOR')";
                                                } elseif (($inputs['sales_type'] ?? '') == 'O') {
                                                    $materialQuery .= " AND (category = 'BAN DALAM MOBIL' OR category = 'BAN LUAR MOBIL')";
                                                }
                                            @endphp
                                            <x-ui-dropdown-search label=""
                                                model="input_details.{{ $key }}.matl_id"
                                                :query="$materialQuery"
                                                optionValue="id" optionLabel="code,name"
                                                placeHolder="Select material..." :selectedValue="$input_details[$key]['matl_id'] ?? ''" required="true"
                                                :action="$actionValue" enabled="true"
                                                onChanged="onMaterialChanged({{ $key }}, $event.target.value)"
                                                type="int" :enabled="$isDeliv ? 'false' : 'true'" />
                                        </td>
                                        <td style="text-align: center;">
                                            <x-ui-text-field model="input_details.{{ $key }}.price"
                                                label="" :action="$actionValue" :enabled="$isDeliv ? 'false' : 'true'" type="number"
                                                onChanged="updateItemAmount({{ $key }})" decimalPlaces="2" />
                                        </td>
                                        <td style="text-align: center;">
                                            <x-ui-text-field model="input_details.{{ $key }}.qty"
                                                label="" :enabled="$isDeliv ? 'false' : 'true'" :action="$actionValue"
                                                onChanged="updateItemAmount({{ $key }})" type="number"
                                                required="true" />
                                        </td>
                                        <td style="text-align: center;">
                                            <x-ui-text-field model="input_details.{{ $key }}.disc_pct"
                                                label="" :action="$actionValue" :enabled="$isDeliv ? 'false' : 'true'"
                                                onChanged="updateItemAmount({{ $key }})" type="number"
                                                decimalPlaces="2" />
                                        </td>
                                        <td style="text-align: center;">
                                            <x-ui-text-field model="input_details.{{ $key }}.amt"
                                                label="" :action="$actionValue" type="text" enabled="false"
                                                type="number" />
                                        </td>
                                        <td style="text-align: center;">
                                            <x-ui-button :clickEvent="'deleteItem(' . $key . ')'" button-name="" loading="true"
                                                :action="$actionValue" cssClass="btn-danger text-danger"
                                                iconPath="delete.svg" :enabled="$isDeliv ? 'false' : 'true'" />
                                        </td>
                                    </tr>
                                @endforeach
                            </x-slot>
                            <x-slot name="button">
                                <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg"
                                    button-name="Tambah" :enabled="$isDeliv ? 'false' : 'true'" />
                            </x-slot>
                        </x-ui-table>
                    </x-ui-card>
                    <br>
                    <x-ui-card title="">
                        <x-ui-padding>
                            <div class="row">
                                <x-ui-text-field model="total_discount" label="Total Discount" :action="$actionValue"
                                    enabled="false" type="text" :value="$total_discount" />
                                <x-ui-text-field model="total_dpp" label="Total DPP" :action="$actionValue"
                                    enabled="false" type="text" :value="$total_dpp" />
                                <x-ui-text-field model="total_tax" label="Total PPN" :action="$actionValue"
                                    enabled="false" type="text" :value="$total_tax" />
                                <x-ui-text-field model="total_amount" label="Total Amount" :action="$actionValue"
                                    enabled="false" type="text" :value="$total_amount" />
                            </div>
                        </x-ui-padding>
                    </x-ui-card>
                    <br>
                    <x-ui-footer>
                        <x-ui-button clickEvent="deleteTransaction" button-name="Hapus" loading="true"
                            :action="$actionValue" cssClass="btn-danger" iconPath="delete.svg" :enabled="$isDeliv ? 'false' : 'true'" />
                        {{-- @include('layout.customs.buttons.save') --}}
                        <x-ui-button clickEvent="Save" button-name="Simpan" loading="true" :action="$actionValue"
                            cssClass="btn-primary" iconPath="save.svg" :enabled="true" />

                    </x-ui-footer>
                </div>
                {{-- <x-ui-table id="SummaryTable">
                    <x-slot name="headers">
                        <th style="width: 150px; text-align: center;">Total Discount</th>
                        <th style="width: 150px; text-align: center;">DPP</th>
                        <th style="width: 150px; text-align: center;">PPN</th>
                        <th style="width: 150px; text-align: center;">Total Amount</th>
                        <th style="width: 150px; text-align: center;">Versi</th>
                    </x-slot>
                    <x-slot name="rows">
                        <tr>
                            <td style="text-align: center;">
                                <x-ui-text-field model="total_discount" label="" :action="$actionValue"
                                    enabled="false" type="text" :value="$total_discount" placeHolder="Total Discount" />
                            </td>
                            <td style="text-align: center;">
                                <x-ui-text-field model="total_dpp" label="" :action="$actionValue" enabled="false"
                                    type="text" :value="$total_dpp" />
                            </td>
                            <td style="text-align: center;">
                                <x-ui-text-field model="total_tax" label="" :action="$actionValue" enabled="false"
                                    type="text" :value="$total_tax" />
                            </td>
                            <td style="text-align: center;">
                                <x-ui-text-field model="total_amount" label="" :action="$actionValue"
                                    enabled="false" type="text" :value="$total_amount" />
                            </td>
                            <td style="text-align: center;">
                                <x-ui-text-field model="versionNumber" label="" :action="$actionValue"
                                    enabled="false" type="text" />
                            </td>
                        </tr>
                    </x-slot>
                </x-ui-table> --}}
                <br>

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


    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.addEventListener('openMaterialDialog', function() {
                    Livewire.dispatch('resetMaterial');
                    $('#materialDialogBox').modal('show');
                });

                window.addEventListener('closeMaterialDialog', function() {
                    $('#materialDialogBox').modal('hide');
                });
            });
        </script>
    @endpush

</div>
