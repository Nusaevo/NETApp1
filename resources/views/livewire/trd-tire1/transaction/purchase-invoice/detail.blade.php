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
                            {{-- <x-ui-padding> --}}
                            <div class="row">
                                <x-ui-text-field label="Tanggal Billing" model="inputs.tr_date" type="date"
                                    :action="$actionValue" required="true" enabled="true" />
                                <x-ui-text-field label="{{ $this->trans('tr_code') }}" model="inputs.tr_code"
                                    type="text" :action="$actionValue" required="true" :enabled="$isPanelEnabled"
                                    capslockMode="true" />
                            </div>
                            <div class="row">
                                {{-- <x-ui-dropdown-select label="{{ $this->trans('warehouse') }}" model="inputs.wh_code"
                                        :options="$warehouses" required="true" :action="$actionValue" :enabled="$isPanelEnabled"
                                        onChanged="whCodeOnChanged($event.target.value)" />
                                    <x-ui-dropdown-select label="{{ $this->trans('reffhdrtr_code') }}"
                                        model="inputs.reffhdrtr_code" :options="$purchaseOrders" required="true"
                                        :action="$actionValue" onChanged="onPurchaseOrderChanged($event.target.value)" /> --}}
                                {{-- @dump($inputs['reffhdrtr_code']) --}}
                                <!-- Display Partner Name -->
                                <x-ui-dropdown-search label="{{ $this->trans('Supplier') }}" model="inputs.partner_id"
                                    query="SELECT id, code, name, address, city FROM partners WHERE deleted_at IS NULL AND grp = 'V'"
                                    optionValue="id" optionLabel="code,name,address,city"
                                    placeHolder="Type to search suppliers..." :selectedValue="$inputs['partner_id']" required="true"
                                    :action="$actionValue" enabled="true" type="int"
                                    onChanged="onPartnerChanged($event.target.value)" />
                                <!-- Hidden input for partner ID -->
                                <input type="hidden" wire:model="inputs.partner_id">
                                {{-- <div class="row">
                                    <x-tes-component :items="$items" :selectedItems="$selectedItems" name="delivery_selection"
                                        :multiple="true" label="Nota Penerimaan Barang (Otomatis dipilih semua)" required="true"
                                        :action="$actionValue" :enabled="$isPanelEnabled" onChanged="onDelivChanged" />
                                </div> --}}
                            </div>
                            {{-- </x-ui-padding> --}}
                        </x-ui-card>
                        <br>
                        <x-ui-card title="Daftar Nota Penerimaan Barang">
                            @if(empty($items))
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                                    <p>Silakan pilih supplier terlebih dahulu untuk melihat nota penerimaan yang tersedia.</p>
                                </div>
                            @else
                                <x-ui-table id="notaPenerimaanTable">
                                    <!-- Define table headers -->
                                    <x-slot name="headers">
                                        <th style="width: 50px; text-align: center;">No</th>
                                        <th style="width: 150px; text-align: center;">Nota Penerimaan</th>
                                        <th style="width: 120px; text-align: center;">Tanggal</th>
                                        {{-- <th style="width: 100px; text-align: center;">Total Qty</th> --}}
                                        <th style="width: 70px; text-align: center;">Aksi</th>
                                    </x-slot>

                                    <!-- Define table rows -->
                                    <x-slot name="rows">
                                        @foreach ($items as $delivId => $delivCode)
                                            <tr wire:key="deliv{{ $delivId }}">
                                                <td style="text-align: center;">{{ $loop->iteration }}</td>
                                                <td style="text-align: center;">{{ $delivCode }}</td>
                                                <td style="text-align: center;">
                                                    @php
                                                        $delivHdr = \App\Models\TrdTire1\Transaction\DelivHdr::find($delivId);
                                                        echo $delivHdr ? date('d/m/Y', strtotime($delivHdr->tr_date)) : '-';
                                                    @endphp
                                                </td>
                                                {{-- <td style="text-align: center;">
                                                    @php
                                                        $delivHdr = \App\Models\TrdTire1\Transaction\DelivHdr::find($delivId);
                                                        echo $delivHdr ? number_format($delivHdr->total_qty) : '0';
                                                    @endphp
                                                </td> --}}
                                                <td style="text-align: center;">
                                                    <x-ui-button :clickEvent="'removeDelivery(' . $delivId . ')'" button-name="" loading="true"
                                                        :action="$actionValue" cssClass="btn-danger text-danger"
                                                        iconPath="delete.svg" />
                                                </td>
                                            </tr>
                                        @endforeach
                                    </x-slot>
                                </x-ui-table>
                            @endif
                        </x-ui-card>
                        <br>
                        <x-ui-card title="Detail Material">
                            @if(empty($input_details))
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-box fa-2x mb-2"></i>
                                    <p>Silakan pilih nota penerimaan untuk melihat detail material.</p>
                                </div>
                            @else
                                <x-ui-table id="materialTable">
                                    <!-- Define table headers -->
                                    <x-slot name="headers">
                                        <th style="width: 50px; text-align: center;">No</th>
                                        <th style="width: 150px; text-align: center;">Material</th>
                                        <th style="width: 80px; text-align: center;">Qty</th>
                                        <th style="width: 100px; text-align: center;">Price</th>
                                        <th style="width: 80px; text-align: center;">Disc (%)</th>
                                        <th style="width: 120px; text-align: center;">Amount</th>
                                    </x-slot>

                                    <!-- Define table rows -->
                                    <x-slot name="rows">
                                        @foreach ($input_details as $key => $input_detail)
                                            <tr wire:key="material{{ $input_detail['id'] ?? $key }}">
                                                <td style="text-align: center;">{{ $loop->iteration }}</td>
                                                <td>
                                                    <x-ui-dropdown-search model="input_details.{{ $key }}.matl_id"
                                                        query="SELECT id, code, name FROM materials WHERE status_code='A' AND deleted_at IS NULL"
                                                        optionValue="id"  optionLabel="{code},{name}"
                                                        placeHolder="Search materials..." :selectedValue="$input_details[$key]['matl_id'] ?? ''" required="true"
                                                        :action="$actionValue" enabled="true"
                                                        onChanged="onMaterialChanged({{ $key }}, $event.target.value)"
                                                        type="int" />
                                                </td>
                                                <td style="text-align: center;">
                                                    <x-ui-text-field model="input_details.{{ $key }}.qty"
                                                        label="" enabled="true" :action="$actionValue" type="number"
                                                        required="true" step="0.01" />
                                                </td>
                                                <td style="text-align: center;">
                                                    <x-ui-text-field model="input_details.{{ $key }}.price"
                                                        label="" enabled="true" :action="$actionValue" type="number"
                                                        required="true" step="0.01" />
                                                </td>
                                                <td style="text-align: center;">
                                                    <x-ui-text-field model="input_details.{{ $key }}.disc_pct"
                                                        label="" enabled="true" :action="$actionValue" type="number"
                                                        step="0.01" />
                                                </td>
                                                <td style="text-align: center;">
                                                    <x-ui-text-field model="input_details.{{ $key }}.amt"
                                                        label="" enabled="false" :action="$actionValue" type="number"
                                                        class="form-control" readonly="true" />
                                                </td>
                                            </tr>
                                        @endforeach
                                    </x-slot>
                                </x-ui-table>
                            @endif
                        </x-ui-card>
                        <br>
                        <x-ui-card title="">
                            {{-- <x-ui-padding> --}}
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
                            {{-- </x-ui-padding> --}}
                        </x-ui-card>

                        <x-ui-footer>
                            <x-ui-button clickEvent="deleteTransaction"
                                :action="$actionValue"
                                :enabled="$isDeliv ? 'false' : 'true'"
                                type="delete" enableConfirmationDialog="true" :permissions="$permissions"/>
                            <x-ui-button clickEvent="Save"
                                :action="$actionValue"
                                type="save"
                                :enabled="true" />
                        </x-ui-footer>
                    </div>
                </div>
            </div>
        </x-ui-tab-view-content>
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
