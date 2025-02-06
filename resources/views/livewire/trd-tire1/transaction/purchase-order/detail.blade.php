<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
    <x-ui-page-card
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
                                    <x-ui-option model="inputs.sales_type" :options="['0' => 'MOTOR', '1' => 'MOBIL']" type="radio"
                                        layout="horizontal" :action="$actionValue" :enabled="$isPanelEnabled" />
                                    {{-- <x-ui-option model="inputs.tax_invoice" label="Faktur Pajak" :options="['isTaxInvoice' => 'Ya']"
                                    type="checkbox" layout="horizontal" :action="$actionValue" :enabled="$isPanelEnabled"
                                    onChanged="onTaxInvoiceChanged" :checked="$inputs['tax_invoice']" /> --}}
                                </div>
                                <div class="row">
                                    <x-ui-text-field label="{{ $this->trans('tr_code') }}" model="inputs.tr_code"
                                        type="code" :action="$actionValue" required="false"
                                        clickEvent="getTransactionCode" buttonName="Nomor" enabled="true" :buttonEnabled="$isPanelEnabled"/>
                                    <x-ui-text-field label="Tanggal Transaksi" model="inputs.tr_date" type="date"
                                        :action="$actionValue" required="true" :enabled="$isPanelEnabled" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field type="text" label="Supplier" model="inputs.partner_name"
                                        required="true" :action="$actionValue" enabled="false"
                                        clickEvent="openPartnerDialogBox" buttonName="Search" :buttonEnabled="$isPanelEnabled" />

                                    <x-ui-dialog-box id="partnerDialogBox" title="Search Supplier" width="600px"
                                        height="400px" onOpened="openPartnerDialogBox" onClosed="closePartnerDialogBox">
                                        <x-slot name="body">
                                            <x-ui-text-field type="text" label="Search Code/Nama Supplier"
                                                model="partnerSearchText" required="true" :action="$actionValue"
                                                enabled="true" clickEvent="searchPartners" buttonName="Search" />
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
                                                            <td colspan="4" class="text-center text-muted">No Data
                                                                Found</td>
                                                        </tr>
                                                    @else
                                                        @foreach ($suppliers as $key => $supplier)
                                                            <tr wire:key="row-{{ $key }}-supplier">
                                                                <td>
                                                                    <x-ui-option label="" required="false"
                                                                        layout="horizontal" enabled="true"
                                                                        type="checkbox" visible="true" :options="[
                                                                            $supplier['id'] => $supplier['code'],
                                                                        ]"
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
                                                        button-name="Confirm Selection" loading="true"
                                                        :action="$actionValue" cssClass="btn-primary" />
                                                </x-slot>
                                            </x-ui-table>
                                        </x-slot>
                                    </x-ui-dialog-box>
                                    <x-ui-dropdown-select label="{{ $this->trans('tax_flag') }}" model="inputs.tax_flag"
                                        :options="$SOTax" required="true" :action="$actionValue"
                                        onChanged="onSOTaxChange" />
                                </div>
                                <div class="row">
                                    <x-ui-dropdown-select label="{{ $this->trans('payment_terms') }}"
                                        model="inputs.payment_term_id" :options="$paymentTerms" required="true"
                                        :action="$actionValue" />
                                    <x-ui-text-field label="Tanggal Jatuh Tempo" model="inputs.due_date" type="date"
                                        :action="$actionValue" required="true" :enabled="$isPanelEnabled" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field label="{{ $this->trans('note') }}" model="inputs.note"
                                        type="textarea" :action="$actionValue" required="false" />
                                </div>
                            </x-ui-padding>
                        </x-ui-card>
                    </div>
                    <x-ui-footer>
                        <div>
                            <x-ui-button clickEvent="Save" button-name="Save Header" loading="true" :action="$actionValue"
                                cssClass="btn-primary" iconPath="save.svg" />
                        </div>

                    </x-ui-footer>
                    {{-- <div class="col-md-12">
                        <x-ui-card title="Order Info">
                            <x-ui-text-field label="Date" model="inputs.tr_date" type="date" :action="$actionValue"
                                required="true" :enabled="$isPanelEnabled" />
                            <x-ui-text-field-search type="int" label="Supplier" clickEvent=""
                                model="inputs.partner_id" :selectedValue="$inputs['partner_id']" :options="$suppliers" required="true"
                                :action="$actionValue" :enabled="$isPanelEnabled" />
                            <x-ui-text-field label="Status" model="inputs.status_code_text" type="text"
                                :action="$actionValue" required="false" enabled="false" />
                        </x-ui-card>

                        <x-ui-footer>
                            @if ($actionValue !== 'Create' && (!$object instanceof App\Models\SysConfig1\ConfigUser || auth()->user()->id !== $object->id))
                                @if (isset($permissions['delete']) && $permissions['delete'])
                                    <div style="padding-right: 10px;">
                                        @include('layout.customs.buttons.disable')
                                    </div>
                                @endif

                            @endif
                            <div>
                                <x-ui-button clickEvent="Save" button-name="Save Header" loading="true"
                                    :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
                            </div>

                        </x-ui-footer>

                    </div> --}}
                </div>
                <br>
                <div class="col-md-12">
                    <x-ui-card title="Order Items">
                        @livewire($currentRoute . '.material-list-component', ['action' => $action, 'objectId' => $objectId])
                    </x-ui-card>
                </div>
                <div class="col-md-12">
                    <x-ui-card>
                        <x-ui-padding>
                            <div class="row">
                                <x-ui-text-field label="{{ $this->trans('total_discount') }}" model="total_discount"
                                    type="text" :action="$actionValue" required="false" enabled="false" />
                                <x-ui-text-field label="{{ $this->trans('PPN') }}" model="total_tax" type="text"
                                    :action="$actionValue" required="false" enabled="false" />
                                <x-ui-text-field label="{{ $this->trans('DPP') }}" model="total_dpp" type="text"
                                    :action="$actionValue" required="false" enabled="false" />
                                <x-ui-text-field label="{{ $this->trans('total_amount') }}" model="total_amount"
                                    type="text" :action="$actionValue" required="false" enabled="false" />
                            </div>
                        </x-ui-padding>
                    </x-ui-card>
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
