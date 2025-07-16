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
                                    {{-- <x-ui-dropdown-search label="Custommer" model="inputs.partner_id"
                                        searchModel="App\Models\TrdTire1\Master\Partner"
                                        searchWhereCondition="deleted_at=null&grp=C" optionValue="id"
                                        optionLabel="code,name" placeHolder="Type to search custommer..."
                                        :selectedValue="$inputs['partner_id']" required="true" :action="$actionValue" :enabled="$isPanelEnabled"
                                        type="int" onChanged="onPartnerChange" /> --}}
                                    <x-ui-dropdown-search label="Supplier" model="inputs.partner_id"
                                        query="SELECT id, code, name, address, city FROM partners WHERE deleted_at IS NULL AND grp = 'C'"
                                        optionValue="id" optionLabel="code,name,address,city"
                                        placeHolder="Type to search supplier..." :selectedValue="$inputs['partner_id']" required="true"
                                        :action="$actionValue" :enabled="$isPanelEnabled" type="int"
                                        onChanged="onPartnerChange" />
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
            <div class="col-md-12">
                <x-ui-card title="Saldo Lebih Bayar">
                    <x-ui-table id="AdvanceTable">
                        <x-slot name="headers">
                            <th style="width: 50px; text-align: center;">No</th>
                            <th style="width: 200px; text-align: center;">Keterangan</th>
                            <th style="width: 150px; text-align: center;">Jumlah</th>
                            <th style="width: 150px; text-align: center;">Dipakai</th>
                            <th style="width: 70px; text-align: center;">Actions</th>
                        </x-slot>
                        <x-slot name="rows">
                            @foreach ($input_advance as $key => $advance)
                                <tr wire:key="advance-{{ $key }}">
                                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                                    <td>
                                        <x-ui-dropdown-select :options="$advanceOptions"
                                            model="input_advance.{{ $key }}.partnerbal_id" :action="$actionValue"
                                            enabled="true"
                                            onChanged="onAdvanceChanged({{ $key }}, $event.target.value)" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_advance.{{ $key }}.amtAdvBal"
                                            label="" :action="$actionValue" enabled="false" type="number" />
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_advance.{{ $key }}.amt" label=""
                                            :action="$actionValue" enabled="false" type="number" />
                                    </td>
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-button :clickEvent="'deleteAdvanceItem(' . $key . ')'" button-name="" loading="true" :action="$actionValue"
                                            cssClass="btn-danger text-danger" iconPath="delete.svg" />
                                    </td>
                                </tr>
                            @endforeach
                        </x-slot>
                        <x-slot name="button">
                            <x-ui-button clickEvent="addAdvanceItem" cssClass="btn btn-primary" iconPath="add.svg"
                                button-name="Add" />
                        </x-slot>
                    </x-ui-table>
                </x-ui-card>
            </div>
            <br>
            <div class="col-md-12">
                <x-ui-card title="Pembayaran">
                    <x-ui-table id="Table">
                        <!-- Define table headers -->
                        <x-slot name="headers">
                            <th style="width: 50px; text-align: center;">No</th>
                            <th style="width: 150px; text-align: center;">Tipe Pembayaran</th>
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
                                        <x-ui-text-field model="input_payments.{{ $key }}.amt"
                                            label="" :action="$actionValue" enabled="false" type="number" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-button :clickEvent="'openPaymentDialog(' . $key . ')'" button-name="" loading="true"
                                            :action="$actionValue" cssClass="btn-secondary text-light"
                                            iconPath="edit.svg" />
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
                                <tr wire:key="list{{ $input_detail['billhdr_id'] ?? $key }}">
                                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_details.{{ $key }}.billhdrtr_code"
                                            label="" :action="$actionValue" enabled="false" type="text" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_details.{{ $key }}.due_date"
                                            label="" :action="$actionValue" enabled="false" type="date" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_details.{{ $key }}.outstanding_amt"
                                            label="" :action="$actionValue" enabled="false" type="number" value="{{ (int) $input_detail['outstanding_amt'] }}"/>
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
                            {{-- <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg"
                                button-name="Add" /> --}}
                            <x-ui-button clickEvent="payItem" cssClass="btn btn-primary"
                                button-name="Auto Pelunasan" />
                        </x-slot>
                    </x-ui-table>
                </x-ui-card>
            </div>
            <br>
            <br>
            <x-ui-card title="">
                <x-ui-padding>
                    <div class="row">
                        <x-ui-text-field model="totalPaymentAmount" label="Total Pembayaran" :action="$actionValue"
                            enabled="false" type="number" />
                        <x-ui-text-field model="totalNotaAmount" label="Total Amt Nota" :action="$actionValue"
                            enabled="false" type="number" />
                        <x-ui-text-field model="advanceBalance" label="Lebih Bayar" :action="$actionValue"
                            enabled="false" type="number" />
                    </div>
                </x-ui-padding>
            </x-ui-card>
        </x-ui-tab-view-content>
    </x-ui-page-card>

    <!-- Footer with Save button -->
    <x-ui-footer>
        <x-ui-button clickEvent="deleteTransaction" button-name="Hapus" loading="true" :action="$actionValue"
            cssClass="btn-danger" iconPath="delete.svg" />
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
