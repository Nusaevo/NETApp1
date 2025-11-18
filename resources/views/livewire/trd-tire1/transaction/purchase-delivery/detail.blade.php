<div>
    <div>
        {{-- <x-ui-button clickEvent="" type="Back" button-name="Back" /> --}}
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
                                    <x-ui-text-field label="{{ $this->trans('tr_code') }}" model="inputs.tr_code"
                                        type="text" :action="$actionValue" required="true" enabled="true"
                                        capslockMode="true" onChanged="onTrCodeChanged"
                                        placeHolder="Edit nomor surat jalan dan tekan enter untuk cari surat jalan lain, kosongkan untuk create" />
                                    <x-ui-text-field label="Tanggal Surat Jalan" model="inputs.reff_date" type="date"
                                        :action="$actionValue" required="true" enabled="true" />
                                    <x-ui-text-field label="Tanggal Terima Barang" model="inputs.tr_date" type="date"
                                        :action="$actionValue" required="true" enabled="true" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field label="{{ $this->trans('note') }}" model="inputs.note"
                                        type="textarea" :action="$actionValue" required="false" />
                                </div>
                                <div class="row">
                                    <x-ui-dropdown-select label="{{ $this->trans('warehouse') }}" model="inputs.wh_code"
                                        :options="$warehouses" required="true" :action="$actionValue" enabled="true"
                                        onChanged="whCodeOnChanged($event.target.value)" />
                                    <x-ui-dropdown-search label="Nota Pembelian" model="inputs.reffhdrtr_code"
                                        optionValue="tr_code" :query="$ddPurchaseOrder['query']" :optionLabel="$ddPurchaseOrder['optionLabel']" :placeHolder="$ddPurchaseOrder['placeHolder']"
                                        :selectedValue="$inputs['reffhdrtr_code'] ?? ''" required="true" :action="$actionValue" enabled="true"
                                        type="int" onChanged="onPurchaseOrderChanged($event.target.value)" />
                                    <!-- Display Partner Name -->
                                    <x-ui-text-field label="{{ $this->trans('supplier') }}" model="inputs.partner_name"
                                        type="text" :action="$actionValue" required="false" readonly="true"
                                        enabled="false" />
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
                                    <th style="width: 150px; text-align: center;">Qty Belum Dikirim</th>
                                    <th style="width: 50px; text-align: center;">Quantity</th>
                                    <th style="width: 70px; text-align: center;">Aksi</th>
                                </x-slot>

                                <!-- Define table rows -->
                                <x-slot name="rows">
                                    @foreach ($input_details as $key => $input_detail)
                                        <tr
                                            wire:key="purchase-delivery-item-{{ $key }}-{{ $input_detail['matl_id'] ?? 'new' }}-{{ $input_detail['reffdtl_id'] ?? 'new' }}">
                                            <td style="text-align: center;">{{ $loop->iteration }}</td>
                                            <td>
                                                <x-ui-dropdown-search model="input_details.{{ $key }}.matl_id"
                                                    query="SELECT id, code, name FROM materials WHERE status_code='A' AND deleted_at IS NULL"
                                                    optionValue="id" optionLabel="{code},{name}"
                                                    placeHolder="Search materials..." :selectedValue="$input_details[$key]['matl_id'] ?? ''" required="true"
                                                    :action="$actionValue" enabled="false"
                                                    onChanged="onMaterialChanged({{ $key }}, $event.target.value)"
                                                    type="int" />
                                            </td>
                                            <td style="text-align: center;">
                                                <x-ui-text-field model="input_details.{{ $key }}.qty_order"
                                                    enabled="true" class="form-control" type="number" enabled="false"
                                                    :action="$actionValue" />
                                            </td>
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

                                <x-slot name="button">
                                    @if ($actionValue === 'Edit' && $hasNewItems)
                                        <x-ui-button clickEvent="addNewItemsFromPurchaseOrder" cssClass="btn btn-primary"
                                            iconPath="add.svg" button-name="Tambah" loading="true"
                                            :action="$actionValue" />
                                    @endif
                                </x-slot>
                            </x-ui-table>
                        </x-ui-card>

                        <!-- Footer with Save button -->
                        <x-ui-footer>
                            <x-ui-button clickEvent="delete" :action="$actionValue" :enabled="$isDeliv ? 'false' : 'true'"
                                type="delete" enableConfirmationDialog="true" :permissions="$permissions" />
                            <x-ui-button clickEvent="Save" :action="$actionValue" type="save" :enabled="true" />

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

                // Update Select2 for reffhdrtr_code after Livewire updates
                Livewire.hook('morph.updated', ({ el, component }) => {
                    setTimeout(function() {
                        const selectElement = document.getElementById('inputs_reffhdrtr_code');
                        if (selectElement && $(selectElement).hasClass('select2-hidden-accessible')) {
                            // Get current value from Livewire component
                            const livewireComponent = Livewire.find(component.id);
                            if (livewireComponent) {
                                const currentValue = livewireComponent.get('inputs.reffhdrtr_code');
                                if (currentValue && currentValue !== '' && currentValue !== '0') {
                                    // Check if value is already set in Select2
                                    const select2Value = $(selectElement).val();
                                    if (select2Value !== currentValue) {
                                        // Fetch display text and update Select2
                                        const endpoint = '/search-dropdown';
                                        const queryParam = selectElement.getAttribute('data-query');
                                        const params = new URLSearchParams();
                                        params.append('connection', selectElement.getAttribute('data-connection') || 'Default');
                                        params.append('query', queryParam);
                                        params.append('option_value', selectElement.getAttribute('data-option-value') || 'id');
                                        params.append('option_label', selectElement.getAttribute('data-option-label') || 'name');
                                        params.append('id', currentValue);
                                        params.append('preserve_existing', 'true');
                                        params.append('bypass_filters', 'true');

                                        fetch(`${endpoint}?${params.toString()}`)
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data && data.results && data.results.length > 0) {
                                                    const item = data.results[0];
                                                    const displayText = item.text;

                                                    // Clear existing options and add new one
                                                    $(selectElement).empty();
                                                    const option = new Option(displayText, item.id, true, true);
                                                    $(selectElement).append(option).trigger('change');
                                                } else {
                                                    // Create placeholder option if not found
                                                    $(selectElement).empty();
                                                    const option = new Option(`ID: ${currentValue} (Not Found)`, currentValue, true, true);
                                                    $(option).addClass('missing-option');
                                                    $(selectElement).append(option).trigger('change');
                                                }
                                            })
                                            .catch(error => {
                                                console.warn('Failed to update Select2 value:', error);
                                            });
                                    }
                                }
                            }
                        }
                    }, 100);
                });
            });
        </script>
    @endpush

</div>
