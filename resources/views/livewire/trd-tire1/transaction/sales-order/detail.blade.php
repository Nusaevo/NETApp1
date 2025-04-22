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
                                    <x-ui-option model="inputs.sales_type" :options="['0' => 'MOTOR', '1' => 'MOBIL']" type="radio"
                                        layout="horizontal" :action="$actionValue" :enabled="$isPanelEnabled" />
                                    <x-ui-option model="inputs.tax_doc_flag" label="Faktur Pajak" :options="['isTaxInvoice' => 'Ya']"
                                        type="checkbox" layout="horizontal" :action="$actionValue" :enabled="$isPanelEnabled"
                                        :checked="$inputs['tax_doc_flag']" onChanged="onTaxDocFlagChanged" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field label="{{ $this->trans('tr_code') }}" model="inputs.tr_code"
                                        type="code" :action="$actionValue" required="true" clickEvent="getTransactionCode"
                                        buttonName="Nomor" enabled="true" :buttonEnabled="$isPanelEnabled" />
                                    <x-ui-text-field label="Tanggal Transaksi" model="inputs.tr_date" type="date"
                                        :action="$actionValue" required="true" :enabled="$isPanelEnabled" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field type="text" label="Custommer" model="inputs.partner_name"
                                        required="true" :action="$actionValue" enabled="false"
                                        clickEvent="openPartnerDialogBox" buttonName="Search" :buttonEnabled="$isPanelEnabled" />
                                    <x-ui-dialog-box id="partnerDialogBox" title="Search Custommer" width="600px"
                                        height="400px" onOpened="openPartnerDialogBox" onClosed="closePartnerDialogBox">
                                        <x-slot name="body">
                                            <x-ui-text-field type="text" label="Search Code/Nama Custommer"
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
                                                                        type="checkbox" visible="true"
                                                                        :options="[
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
                                    <x-ui-dropdown-select label="{{ $this->trans('ship_to') }}" clickEvent=""
                                        model="inputs.ship_to_name" :selectedValue="$inputs['ship_to_name']" :options="$shipOptions"
                                        required="false" :action="$actionValue" onChanged="onShipToChanged" />
                                    <x-ui-dropdown-select label="{{ $this->trans('tax_payer') }}" clickEvent=""
                                        model="inputs.npwp_code" :selectedValue="$inputs['npwp_code']" :options="$npwpOptions" required="false"
                                        :action="$actionValue" onChanged="onTaxPayerChanged" :enabled="$payer" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field label="{{ $this->trans('Detail Custommer') }}"
                                        model="inputs.textareacustommer" type="textarea" :action="$actionValue"
                                        required="false" enabled="false" />
                                    <x-ui-text-field label="{{ $this->trans('Alamat kirim') }}"
                                        model="inputs.textareasend_to" type="textarea" :action="$actionValue"
                                        required="false" enabled="false" />
                                    <x-ui-text-field label="{{ $this->trans('Detail Wajib pajak') }}"
                                        model="inputs.textarea_npwp" type="textarea" :action="$actionValue"
                                        required="false" enabled="false" />
                                </div>
                                <div class="row">
                                    <x-ui-dropdown-select label="{{ $this->trans('tax_flag') }}"
                                        model="inputs.tax_flag" :options="$SOTax" required="true" :action="$actionValue"
                                        onChanged="onSOTaxChange" />
                                    <x-ui-dropdown-select label="{{ $this->trans('payment_term') }}"
                                        model="inputs.payment_term_id" :options="$paymentTerms" required="true"
                                        :action="$actionValue" onChanged="onPaymentTermChanged" :enabled="$isPanelEnabled" />
                                    <x-ui-text-field label="{{ $this->trans('due_date') }}" model="inputs.due_date"
                                        type="date" :action="$actionValue" required="true" :enabled="$isPanelEnabled" />
                                    <x-ui-text-field label="{{ $this->trans('cust_reff') }}" model="inputs.cust_reff"
                                        type="text" :action="$actionValue" required="false" />
                                </div>
                            </x-ui-padding>
                        </x-ui-card>
                    </div>
                </div>
                <br>
                <div class="col-md-12">
                    <x-ui-card title="Order Items">
                        <x-ui-table id="Table">
                            <!-- Define table headers -->
                            <x-slot name="headers">
                                <th style="width: 50px; text-align: center;">No</th>
                                <th style="width: 150px; text-align: center;">Code</th>
                                <th style="width: 150px; text-align: center;">Harga Satuan</th>
                                <th style="width: 50px; text-align: center;">Quantity</th>
                                <th style="width: 90px; text-align: center;">Disc (%)</th>
                                <th style="width: 150px; text-align: center;">Amount</th>
                                <th style="width: 70px; text-align: center;">Actions</th>
                            </x-slot>

                            <!-- Define table rows -->
                            <x-slot name="rows">
                                @foreach ($input_details as $key => $input_detail)
                                    <tr wire:key="list{{ $input_detail['id'] ?? $key }}">
                                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                                        <td>
                                            <x-ui-dropdown-select type="int" label="" clickEvent=""
                                                model="input_details.{{ $key }}.matl_id" :selectedValue="$input_details[$key]['matl_id']"
                                                :options="$materials" required="true" :action="$actionValue"
                                                onChanged="onMaterialChanged({{ $key }}, $event.target.value)"
                                                :enabled="true" />
                                        </td>
                                        <td style="text-align: center;">
                                            <x-ui-text-field model="input_details.{{ $key }}.price"
                                                label="" :action="$actionValue" enabled="false" type="number" />
                                        </td>
                                        <td style="text-align: center;">
                                            <x-ui-text-field model="input_details.{{ $key }}.qty"
                                                label="" enabled="true" :action="$actionValue"
                                                onChanged="updateItemAmount({{ $key }})" type="number"
                                                required="true" />
                                        </td>
                                        <td style="text-align: center;">
                                            <x-ui-text-field model="input_details.{{ $key }}.disc_pct"
                                                label="" :action="$actionValue" enabled="true"
                                                onChanged="updateItemAmount({{ $key }})" />
                                        </td>
                                        <td style="text-align: center;">
                                            <x-ui-text-field model="input_details.{{ $key }}.amt_idr"
                                                label="" :action="$actionValue" type="text" enabled="false"
                                                type="number" />
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
                                <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg"
                                    button-name="Add" />
                            </x-slot>
                        </x-ui-table>
                    </x-ui-card>
                    <x-ui-footer>
                        <x-ui-button clickEvent="deleteTransaction" button-name="Delete" loading="true" :action="$actionValue"
                            cssClass="btn-danger" iconPath="delete.svg" />
                        <x-ui-button clickEvent="Save" button-name="Save" loading="true" :action="$actionValue"
                            cssClass="btn-primary" iconPath="save.svg" />
                    </x-ui-footer>
                </div>
                <x-ui-table id="SummaryTable">
                    <x-slot name="headers">
                        <th style="width: 150px; text-align: center;">Total Discount</th>
                        <th style="width: 150px; text-align: center;">PPN</th>
                        <th style="width: 150px; text-align: center;">DPP</th>
                        <th style="width: 150px; text-align: center;">Total Amount</th>
                        <th style="width: 150px; text-align: center;">Version</th>
                    </x-slot>
                    <x-slot name="rows">
                        <tr>
                            <td style="text-align: center;">
                                <x-ui-text-field model="total_discount" label="" :action="$actionValue"
                                    enabled="false" type="text" />
                            </td>
                            <td style="text-align: center;">
                                <x-ui-text-field model="total_tax" label="" :action="$actionValue" enabled="false"
                                    type="text" />
                            </td>
                            <td style="text-align: center;">
                                <x-ui-text-field model="total_dpp" label="" :action="$actionValue" enabled="false"
                                    type="text" />
                            </td>
                            <td style="text-align: center;">
                                <x-ui-text-field model="total_amount" label="" :action="$actionValue"
                                    enabled="false" type="text" />
                            </td>
                            <td style="text-align: center;">
                                <x-ui-text-field model="versionNumber" label="" :action="$actionValue"
                                    enabled="false" type="text" />
                            </td>
                        </tr>
                    </x-slot>
                </x-ui-table>
                <x-ui-footer>
                    <div>
                        <x-ui-button :action="$actionValue"
                            clickEvent="{{ route('TrdTire1.Transaction.SalesOrder.PrintPdf', [
                                'action' => encryptWithSessionKey('Edit'),
                                'objectId' => encryptWithSessionKey($object->id),
                            ]) }}"
                            cssClass="btn-primary" type="Route" loading="true" button-name="Cetak Nota Jual"
                            iconPath="print.svg" />
                        <x-ui-button :action="$actionValue"
                            clickEvent="{{ route('TrdTire1.Transaction.SalesDelivery.PrintPdf', [
                                'action' => encryptWithSessionKey('Edit'),
                                'objectId' => encryptWithSessionKey($object->id),
                            ]) }}"
                            cssClass="btn-primary" type="Route" loading="true" button-name="Cetak Surat Jalan"
                            iconPath="print.svg" />
                    </div>
                </x-ui-footer>
        </x-ui-tab-view-content>
    </x-ui-page-card>
</div>
