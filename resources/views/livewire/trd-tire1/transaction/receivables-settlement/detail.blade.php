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
                                    <x-ui-text-field type="text" label="Customer" model="inputs.partner_name"
                                        required="true" :action="$actionValue" enabled="false"
                                        clickEvent="openPartnerDialogBox" buttonName="Search" :buttonEnabled="$isPanelEnabled" />
                                    <x-ui-dialog-box id="partnerDialogBox" title="Cari Customer" width="600px"
                                        height="400px" onOpened="openPartnerDialogBox" onClosed="closePartnerDialogBox">
                                        <x-slot name="body">
                                            <x-ui-text-field type="text" label="Cari Code/Nama Customer"
                                                model="partnerSearchText" required="true" :action="$actionValue"
                                                enabled="true" clickEvent="searchPartners" buttonName="Cari" />
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
                                                        @foreach ($suppliers as $key => $Customer)
                                                            <tr wire:key="row-{{ $key }}-Customer">
                                                                <td>
                                                                    <x-ui-option label="" required="false"
                                                                        layout="horizontal" enabled="true"
                                                                        type="checkbox" visible="true" :options="[
                                                                            $Customer['id'] => $Customer['code'],
                                                                        ]"
                                                                        onChanged="selectPartner({{ $Customer['id'] }})" />
                                                                </td>
                                                                <td>{{ $Customer['name'] }}</td>
                                                                <td>{{ $Customer['address'] }}</td>
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
                                    </x-ui-dialog-box>
                                    <x-ui-text-field label="Tanggal Transaksi" model="inputs.tr_date" type="date"
                                        :action="$actionValue" required="true" :enabled="$isPanelEnabled" />
                                    <x-ui-text-field label="Nomor Transaksi" model="inputs.tr_code" :action="$actionValue"
                                        required="false" enabled="false" />
                                </div>
                            </x-ui-padding>
                        </x-ui-card>
                    </div>
                </div>
            </div>
            <br>

            <br>
            <div class="col-md-12">
                <x-ui-card title="Pembayaran">
                    <x-ui-table id="Table">
                        <!-- Define table headers -->
                        <x-slot name="headers">
                            <th style="width: 50px; text-align: center;">No</th>
                            <th style="width: 150px; text-align: center;">Jenis</th>
                            <th style="width: 150px; text-align: center;">Keterangan</th>
                            <th style="width: 150px; text-align: center;">Amount</th>
                            <th style="width: 70px; text-align: center;">Actions</th>
                        </x-slot>

                        <!-- Define table rows -->
                        <x-slot name="rows">
                            @foreach ($input_payments as $key => $input_payment)
                                <tr wire:key="list{{ $input_payment['id'] ?? $key }}">
                                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                                    <td>
                                        <x-ui-dropdown-select model="input_payments.{{ $key }}.pay_type_code"
                                            :options="$PaymentType" required="true" :action="$actionValue" enabled="false" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_payments.{{ $key }}.bank_reff"
                                            label="" :action="$actionValue" enabled="false" type="text" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_payments.{{ $key }}.amt" label=""
                                            :action="$actionValue" enabled="false" type="number" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-button :clickEvent="'openPaymentDialog(' . $key . ')'" button-name="" loading="true" :action="$actionValue"
                                            cssClass="btn-secondary text-light" iconPath="edit.svg" />
                                        <x-ui-button :clickEvent="'deletePaymentItem(' . $key . ')'" button-name="" loading="true"
                                            :action="$actionValue" cssClass="btn-danger text-danger"
                                            iconPath="delete.svg" />
                                    </td>
                                </tr>
                            @endforeach
                        </x-slot>

                        <x-slot name="button">
                            <x-ui-button clickEvent="addPaymentItem" cssClass="btn btn-primary" iconPath="add.svg"
                                button-name="Add" />
                        </x-slot>
                    </x-ui-table>
                </x-ui-card>
            </div>
            <br>
            <div class="col-md-12">
                <x-ui-card title="Nota">
                    <x-ui-table id="Table">
                        <!-- Define table headers -->
                        <x-slot name="headers">
                            <th style="width: 50px; text-align: center;">No</th>
                            <th style="width: 150px; text-align: center;">Nomor Nota</th>
                            <th style="width: 150px; text-align: center;">Tanggal Jatuh tempo</th>
                            <th style="width: 50px; text-align: center;">Total Piutang</th>
                            <th style="width: 90px; text-align: center;">Total Bayar</th>
                            <th style="width: 70px; text-align: center;">Actions</th>
                        </x-slot>

                        <!-- Define table rows -->
                        <x-slot name="rows">
                            @foreach ($input_details as $key => $input_detail)
                                <tr wire:key="list{{ $input_detail['id'] ?? $key }}">
                                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                                    <td>
                                        <x-ui-dropdown-select type="int" label="" clickEvent=""
                                            model="input_details.{{ $key }}.billhdrtr_code"
                                            :options="$codeBill" required="true" :action="$actionValue"
                                            onChanged="onCodeChanged({{ $key }}, $event.target.value)"
                                            :enabled="true" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_details.{{ $key }}.tr_date"
                                            label="" :action="$actionValue" enabled="false" type="date" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_details.{{ $key }}.amtbill"
                                            label="" enabled="true" :action="$actionValue" enabled="false"
                                            type="number" required="true" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_details.{{ $key }}.amt" label=""
                                            :action="$actionValue" enabled="true" type="number" />
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
                            <x-ui-button clickEvent="payItem" cssClass="btn btn-primary" button-name="Bayar" />
                        </x-slot>
                    </x-ui-table>
                </x-ui-card>
            </div>
            <br>
            <div class="col-md-12">
                <x-ui-card title="Advance">
                    <x-ui-table id="AdvanceTable">
                        <x-slot name="headers">
                            <th style="width: 50px; text-align: center;">No</th>
                            <th style="width: 150px; text-align: center;">Pemakaian Advance</th>
                            <th style="width: 150px; text-align: center;">Amt</th>
                            <th style="width: 150px; text-align: center;">Di Pakai</th>
                            <th style="width: 70px; text-align: center;">Actions</th>
                        </x-slot>
                        <x-slot name="rows">
                            @foreach ($input_advance as $key => $advance)
                                <tr wire:key="advance-{{ $key }}">
                                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                                    <td>
                                        <x-ui-dropdown-select label="" :options="$advanceOptions"
                                            model="input_advance.{{ $key }}.partnerbal_id" :action="$actionValue" enabled="true"
                                            onChanged="onAdvanceChanged({{ $key }}, $event.target.value)" />
                                    </td>
                                    <td>
                                        <x-ui-text-field model="input_advance.{{ $key }}.amt" label="" :action="$actionValue" enabled="true" type="number" />
                                    </td>
                                    <td>
                                        <x-ui-button :clickEvent="'deleteAdvanceItem(' . $key . ')'" button-name="" loading="true"
                                            :action="$actionValue" cssClass="btn-danger text-danger" iconPath="delete.svg" />
                                    </td>
                                </tr>
                            @endforeach
                        </x-slot>
                        <x-slot name="button">
                            <x-ui-button clickEvent="addAdvanceItem" cssClass="btn btn-primary" iconPath="add.svg" button-name="Add" />
                        </x-slot>
                    </x-ui-table>
                </x-ui-card>
            </div>
            <br>
            <div class="col-md-12">
                <x-ui-table id="AdvanceSummaryTable">
                    <x-slot name="headers">
                        <th style="width: 150px; text-align: center;">Total Pembayaran</th>
                        <th style="width: 150px; text-align: center;">Total Amt Nota</th>
                        <th style="width: 150px; text-align: center;">lebih bayar</th>
                    </x-slot>
                    <x-slot name="rows">
                        <tr>
                            <td style="text-align: center;">
                                <x-ui-text-field model="totalPaymentAmount" label="" :action="$actionValue"
                                    enabled="false" type="number" />
                            </td>
                            <td style="text-align: center;">
                                <x-ui-text-field model="totalNotaAmount" label="" :action="$actionValue"
                                    enabled="false" type="number" />
                            </td>
                            <td style="text-align: center;">
                                <x-ui-text-field model="advanceBalance" label="" :action="$actionValue"
                                    enabled="false" type="number" />
                            </td>
                        </tr>
                    </x-slot>
                </x-ui-table>
            </div>
        </x-ui-tab-view-content>
    </x-ui-page-card>

    <!-- Footer with Save button -->
    <x-ui-footer>
        <x-ui-button clickEvent="SaveAll" button-name="Save" loading="true" :action="$actionValue"
            cssClass="btn-primary" iconPath="save.svg" />
    </x-ui-footer>
    <br>

    <x-ui-dialog-box id="PaymentDialogBox" title="Set Payment" width="600px" height="400px"
        onOpened="openPaymentDialogBox" onClosed="closePaymentDialogBox">
        <x-slot name="body">
            <div class="row">
                <div class="col-md-6">
                    @if (isset($activePaymentItemKey))
                        <x-ui-dropdown-select label="{{ $this->trans('Tipe Pembayaran') }}"
                            model="input_payments.{{ $activePaymentItemKey }}.pay_type_code" :options="$PaymentType"
                            required="true" :action="$actionValue" onChanged="onPaymentTypeChange" />
                    @endif
                </div>
            </div>
            <!-- Row untuk tipe CASH (Tunai) -->
            @if ($isCash === 'true')
                <div class="row" title="Tunai">
                    <div class="col-md-3">
                        <x-ui-text-field model="input_payments.{{ $activePaymentItemKey }}.amt_tunai"
                            label="Total Uang" :action="$actionValue" type="number" :enabled="$isCash" />
                    </div>
                </div>
            @endif
            <!-- Row untuk tipe GIRO -->
            @if ($isGiro === 'true')
                <div class="row" title="bank">
                    <div class="col-md-3">
                        <x-ui-text-field model="input_payments.{{ $activePaymentItemKey }}.amt_giro" label="Nilai"
                            :action="$actionValue" type="number" :enabled="$isGiro" />
                    </div>
                    <div class="col-md-3">
                        <x-ui-dropdown-select model="input_payments.{{ $activePaymentItemKey }}.bank_reff_giro"
                            label="Bank" :options="$partnerOptions" :action="$actionValue" :enabled="$isGiro" />
                    </div>
                    <div class="col-md-3">
                        <x-ui-text-field model="input_payments.{{ $activePaymentItemKey }}.bank_reff_no_giro"
                            label="Nomor" :action="$actionValue" type="text" :enabled="$isGiro" />
                    </div>
                    <div class="col-md-3">
                        <x-ui-text-field model="input_payments.{{ $activePaymentItemKey }}.bank_duedt_giro"
                            label="Tanggal" :action="$actionValue" type="date" :enabled="$isGiro" />
                    </div>
                </div>
            @endif

            <!-- Row untuk tipe TRD (Transfer) -->
            @if ($isTrf === 'true')
                <div class="row" title="Transfer">
                    <div class="col-md-3">
                        <x-ui-text-field model="input_payments.{{ $activePaymentItemKey }}.amt_trf"
                            label="Total Transfer" :action="$actionValue" type="number" :enabled="$isTrf" />
                    </div>
                    <div class="col-md-3">
                        <x-ui-text-field model="input_payments.{{ $activePaymentItemKey }}.bank_reff_transfer"
                            label="Bank Penerima" :action="$actionValue" type="text" :enabled="$isTrf" />
                    </div>
                    <div class="col-md-3">
                        <x-ui-text-field model="input_payments.{{ $activePaymentItemKey }}.bank_reff_no_transfer"
                            label="Nomor Reff" :action="$actionValue" type="text" :enabled="$isTrf" />
                    </div>
                    <div class="col-md-3">
                        <x-ui-text-field model="input_payments.{{ $activePaymentItemKey }}.bank_duedt_transfer"
                            label="Tanggal Transfer" :action="$actionValue" type="date" :enabled="$isTrf" />
                    </div>
                </div>
            @endif
            <!-- Row untuk tipe ADV (Advance) -->
            @if ($isAdv === 'true')
                <div class="row" title="Advance">
                    <div class="col-md-3">
                        <x-ui-text-field model="input_payments.{{ $activePaymentItemKey }}.bank_note" label="Advance"
                            :action="$actionValue" type="text" :enabled="$isAdv" />
                    </div>
                    <div class="col-md-3">
                        <x-ui-text-field model="input_payments.{{ $activePaymentItemKey }}.amt_advance"
                            label="Amount" :action="$actionValue" type="number" :enabled="$isAdv" />
                    </div>
                </div>
            @endif
        </x-slot>
        <x-slot name="footer">
            <x-ui-button clickEvent="confirmPayment" button-name="Save" loading="true" :action="$actionValue"
                cssClass="btn-primary" />
        </x-slot>
    </x-ui-dialog-box>
</div>

<script>
    $this - > dispatch('disable-onbeforeunload');
</script>
