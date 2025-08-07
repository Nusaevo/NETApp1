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
                                    <x-ui-dropdown-search label="{{ $this->trans('Supplier') }}"
                                        model="inputs.partner_id"
                                        query="SELECT id, code, name, address, city FROM partners WHERE deleted_at IS NULL AND grp = 'V'"
                                        optionValue="id" optionLabel="code,name,address,city"
                                        placeHolder="Type to search suppliers..." :selectedValue="$inputs['partner_id']" required="true"
                                        :action="$actionValue" enabled="true" type="int"
                                        onChanged="onPartnerChanged($event.target.value)" />
                                    <!-- Hidden input for partner ID -->
                                    <input type="hidden" wire:model="inputs.partner_id">
                                </div>
                            </x-ui-padding>
                        </x-ui-card>
                        <br>
                        <x-ui-card>
                            <x-ui-table id="Table">
                                <!-- Define table headers -->
                                <x-slot name="headers">
                                    <th style="width: 50px; text-align: center;">No</th>
                                    <th style="width: 150px; text-align: center;">Kode</th>
                                    {{-- <th style="width: 150px; text-align: center;">Qty Belum Dikirim</th> --}}
                                    <th style="width: 50px; text-align: center;">Quantity</th>
                                    <th style="width: 70px; text-align: center;">Aksi</th>
                                </x-slot>

                                <!-- Define table rows -->
                                <x-slot name="rows">
                                    @foreach ($input_details as $key => $input_detail)
                                        <tr wire:key="list{{ $input_detail['id'] ?? $key }}">
                                            <td style="text-align: center;">{{ $loop->iteration }}</td>
                                            <td>
                                                <x-ui-dropdown-search model="input_details.{{ $key }}.matl_id"
                                                    query="SELECT id, code, name FROM materials WHERE status_code='A' AND deleted_at IS NULL"
                                                    optionValue="id" optionLabel="code,name"
                                                    placeHolder="Search materials..." :selectedValue="$input_details[$key]['matl_id'] ?? ''" required="true"
                                                    :action="$actionValue" enabled="true"
                                                    onChanged="onMaterialChanged({{ $key }}, $event.target.value)"
                                                    type="int" />
                                            </td>
                                            {{-- <td style="text-align: center;">
                                                <x-ui-text-field model="input_details.{{ $key }}.qty_order"
                                                    enabled="true" class="form-control" type="number" enabled="false"
                                                    :action="$actionValue" />
                                            </td> --}}
                                            <td style="text-align: center;">
                                                <x-ui-text-field model="input_details.{{ $key }}.qty"
                                                    label="" enabled="true" :action="$actionValue" type="number"
                                                    required="true" />
                                            </td>
                                            <td style="text-align: center;">
                                                <x-ui-button :clickEvent="'deleteItem(' . $key . ')'" button-name="" loading="true"
                                                    :action="$actionValue" cssClass="btn-danger text-danger"
                                                    iconPath="delete.svg" />
                                            </td>
                                        </tr>
                                    @endforeach
                                </x-slot>

                                {{-- <x-slot name="button">
                                        <x-ui-button clickEvent="addItem" cssClass="btn btn-primary"
                                            iconPath="add.svg" button-name="Tambah" :enabled="isset($inputs['reffhdrtr_code']) && $inputs['reffhdrtr_code']
                                                ? true
                                                : false" />
                                    </x-slot> --}}
                            </x-ui-table>
                        </x-ui-card>

                        <!-- Footer with Save button -->
                        <x-ui-footer>
                            @include('layout.customs.buttons.delete')
                            @include('layout.customs.buttons.save')

                        </x-ui-footer>
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
