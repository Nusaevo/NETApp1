<div>
    <x-ui-card>
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
                @foreach ($input_details as $key => $input_detail)
                    <tr wire:key="list{{ $input_detail['id'] ?? $key }}">
                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                        <td>
                            <x-ui-dropdown-select label="{{ $this->trans('tr_type') }}"
                                model="input_details.{{ $key }}.pay_type_code" :options="$PaymentType"
                                required="true" :action="$actionValue" enabled="false" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.bank_reff" label=""
                                :action="$actionValue" enabled="false" type="text" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-text-field model="input_details.{{ $key }}.amt" label=""
                                :action="$actionValue" enabled="false" type="number" />
                        </td>
                        <td style="text-align: center;">
                            <x-ui-button :clickEvent="'openPaymentDialog(' . $key . ')'" button-name="Set" loading="true" :action="$actionValue"
                                cssClass="btn-secondary text-light" />
                            <x-ui-button :clickEvent="'deleteItem(' . $key . ')'" button-name="" loading="true" :action="$actionValue"
                                cssClass="btn-danger text-danger" iconPath="delete.svg" />
                        </td>
                    </tr>
                @endforeach
            </x-slot>

            <x-slot name="button">
                <!-- Tombol untuk menambah item -->
                <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg" button-name="Add" />

                <x-ui-dialog-box id="PaymentDialogBox" title="Set Payment" width="600px" height="400px"
                    onOpened="openPaymentDialogBox" onClosed="closePaymentDialogBox">
                    <x-slot name="body">
                        <div class="row">
                            <div class="col-md-6">
                                @if (isset($activePaymentItemKey))
                                    <!-- Tambahkan wire:change untuk trigger perubahan tipe -->
                                    <x-ui-dropdown-select label="{{ $this->trans('pay_type_code') }}"
                                        model="input_payments.{{ $activePaymentItemKey }}.pay_type_code"
                                        :options="$PaymentType" required="true" :action="$actionValue"
                                        onChanged="onPaymentTypeChange" />
                                    {{-- @dump($input_payments[$activePaymentItemKey]['pay_type_code']) --}}
                                @endif
                            </div>
                        </div>
                        <!-- Row untuk tipe CASH (Tunai) -->
                        @if ($isCash === 'true')
                            <div class="row" title="Tunai">
                                <div class="col-md-3">
                                    <x-ui-text-field model="input_payments.{{ $activePaymentItemKey }}.bank_code_tunai"
                                        label="Tunai" :action="$actionValue" type="text" :enabled="$isCash" />
                                </div>
                                <div class="col-md-3">
                                    <x-ui-text-field model="input_payments.{{ $activePaymentItemKey }}.amt_tunai"
                                        label="Amount" :action="$actionValue" type="number" :enabled="$isCash" />
                                </div>
                            </div>
                        @endif
                        <!-- Row untuk tipe GIRO -->
                        @if ($isGiro === 'true')
                            <div class="row" title="bank">
                                <div class="col-md-3">
                                    <x-ui-text-field model="input_payments.{{ $activePaymentItemKey }}.amt_giro"
                                        label="Nilai Giro" :action="$actionValue" type="number" :enabled="$isGiro" />
                                </div>
                                <div class="col-md-3">
                                    <x-ui-text-field model="input_payments.{{ $activePaymentItemKey }}.bank_reff_giro"
                                        label="Bank" :action="$actionValue" type="text" :enabled="$isGiro" />
                                </div>
                                <div class="col-md-3">
                                    <x-ui-text-field
                                        model="input_payments.{{ $activePaymentItemKey }}.bank_reff_no_giro"
                                        label="Nomor Giro" :action="$actionValue" type="text" :enabled="$isGiro" />
                                </div>
                                <div class="col-md-3">
                                    <x-ui-text-field model="input_payments.{{ $activePaymentItemKey }}.bank_date_giro"
                                        label="Tanggal Jatuh Tempo" :action="$actionValue" type="date"
                                        :enabled="$isGiro" />
                                </div>
                            </div>
                        @endif

                        <!-- Row untuk tipe TRD (Transfer) -->
                        @if ($isTrf === 'true')
                            <div class="row" title="Transfer">
                                <div class="col-md-3">
                                    <x-ui-text-field model="input_payments.{{ $activePaymentItemKey }}.amt_trf"
                                        label="Transfer" :action="$actionValue" type="number" :enabled="$isTrf" />
                                </div>
                                <div class="col-md-3">
                                    <x-ui-text-field
                                        model="input_payments.{{ $activePaymentItemKey }}.bank_reff_transfer"
                                        label="Bank Penerima" :action="$actionValue" type="text" :enabled="$isTrf" />
                                </div>
                                <div class="col-md-3">
                                    <x-ui-text-field
                                        model="input_payments.{{ $activePaymentItemKey }}.bank_reff_no_transfer"
                                        label="Nomor Reff" :action="$actionValue" type="number" :enabled="$isTrf" />
                                </div>
                                <div class="col-md-3">
                                    <x-ui-text-field
                                        model="input_payments.{{ $activePaymentItemKey }}.bank_date_transfer"
                                        label="Tanggal Transfer" :action="$actionValue" type="date" :enabled="$isTrf" />
                                </div>
                            </div>
                        @endif
                        <!-- Row untuk tipe ADV (Advance) -->
                        @if ($isAdv === 'true')
                            <div class="row" title="Advance">
                                <div class="col-md-3">
                                    <x-ui-text-field
                                        model="input_payments.{{ $activePaymentItemKey }}.bank_code_advance"
                                        label="Advance" :action="$actionValue" type="text" :enabled="$isAdv" />
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

            </x-slot>
        </x-ui-table>
    </x-ui-card>

    <!-- Footer with Save button -->
    <x-ui-footer>
        <x-ui-button clickEvent="SaveItem" button-name="Save Item" loading="true" :action="$actionValue"
            cssClass="btn-primary" iconPath="save.svg" />
    </x-ui-footer>
</div>
