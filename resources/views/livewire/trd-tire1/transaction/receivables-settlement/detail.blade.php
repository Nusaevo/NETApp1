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
                                    <x-ui-dropdown-search label="Customer" model="inputs.partner_id"
                                        query="SELECT id, code, name, address, city FROM partners WHERE deleted_at IS NULL AND grp = 'C'"
                                        optionValue="id" optionLabel="{code};{name};{address};{city}"
                                        placeHolder="Type to search customer..." :selectedValue="$inputs['partner_id']" required="true"
                                        :action="$actionValue" :enabled="$isPanelEnabled" type="int"
                                        onChanged="onPartnerChanged" />
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
            @if (!empty($input_advance) && is_array($input_advance) && count($input_advance) > 0)
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
                                    {{-- <td>
                                        <x-ui-dropdown-select :options="$advanceOptions"
                                            model="input_advance.{{ $key }}.partnerbal_id" :action="$actionValue"
                                            enabled="false"
                                            onChanged="onAdvanceChanged({{ $key }}, $event.target.value)" />
                                    </td> --}}
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_advance.{{ $key }}.descr"
                                            label="" :action="$actionValue" enabled="false" type="text" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_advance.{{ $key }}.amtAdvBal"
                                            label="" :action="$actionValue" enabled="false" type="number" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_advance.{{ $key }}.amt" label=""
                                            :action="$actionValue" enabled="false" type="number" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-button :clickEvent="'deleteAdvanceItem(' . $key . ')'" button-name="" loading="true" :action="$actionValue"
                                            cssClass="btn-danger text-danger" iconPath="delete.svg" />
                                    </td>
                                </tr>
                            @endforeach
                        </x-slot>
                        {{-- <x-slot name="button">
                            <x-ui-button clickEvent="addAdvanceItem" cssClass="btn btn-primary" iconPath="add.svg"
                                button-name="Add" />
                        </x-slot> --}}
                    </x-ui-table>
                </x-ui-card>
            </div>
            @endif
            <br>
            <div class="col-md-12">
                <x-ui-card title="Pembayaran">
                    <x-ui-table id="Table">
                        <!-- Define table headers -->
                        <x-slot name="headers">
                            <th style="width: 25px; text-align: center;">No</th>
                            <th style="width: 150px; text-align: center;">Rekening Tujuan</th>
                            <th style="width: 150px; text-align: center;">Amount</th>
                            <th style="width: 150px; text-align: center;">Keterangan (Bank+Nomor Giro)</th>
                            <th style="width: 150px; text-align: center;">Tanggal</th>
                            <th style="width: 40px; text-align: center;"></th>
                        </x-slot>
                        <!-- Define table rows -->
                        <x-slot name="rows">
                            @foreach ($input_payments as $key => $input_payment)
                                <tr wire:key="list{{ $input_payment['id'] ?? $key }}">
                                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                                    <td>
                                        <x-ui-dropdown-select model="input_payments.{{ $key }}.bank_code"
                                            :options="$partnerOptions" required="true" :action="$actionValue" enabled="true"
                                            onChanged="onBankCodeChanged({{ $key }}, $event.target.value)" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_payments.{{ $key }}.amt" label=""
                                            :action="$actionValue" enabled="true" type="number" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_payments.{{ $key }}.bank_reff"
                                            label="" :action="$actionValue" enabled="true" type="text" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_payments.{{ $key }}.bank_duedt"
                                            label="" :action="$actionValue" enabled="true" type="date" />
                                    </td>
                                    <td style="text-align: center;">
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
                            <th style="width: 25px; text-align: center;"></th>
                            <th style="width: 150px; text-align: center;">Nomor Nota</th>
                            <th style="width: 150px; text-align: center;">Tanggal Jatuh tempo</th>
                            <th style="width: 50px; text-align: center;">Total Piutang</th>
                            <th style="width: 30px; text-align: center;">Adjustment</th>
                            <th style="width: 90px; text-align: center;">Total Bayar</th>
                            <th style="width: 40px; text-align: center;">Dilunaskan</th>
                            <th style="width: 40px; text-align: center;">Aksi</th>
                        </x-slot>

                        <!-- Define table rows -->
                        <x-slot name="rows">
                            @foreach ($input_details as $key => $input_detail)
                                <tr wire:key="detail-{{ $input_detail['billhdr_id'] ?? $key }}">
                                    <td style="text-align: center;">
                                        <input type="checkbox"
                                            wire:model="input_details.{{ $key }}.is_selected"
                                            wire:change="onNotaSelectionChanged"
                                            {{ $actionValue === 'Edit' ? 'disabled' : '' }}
                                            style="width: 18px; height: 18px; cursor: {{ $actionValue === 'Edit' ? 'not-allowed' : 'pointer' }};">
                                    </td>
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
                                            label="" :action="$actionValue" enabled="false" type="number" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_details.{{ $key }}.amt_adjustment"
                                            label="" :action="$actionValue" enabled="false" type="number" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_details.{{ $key }}.amt" label=""
                                            :action="$actionValue" enabled="true" type="number" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-toggle-switch model="input_details.{{ $key }}.is_lunas"
                                            onChanged="toggleLunas({{ $key }})" :action="$actionValue"
                                            enabled="true" :showLabel="false" :label="$input_detail['is_lunas'] ? 'Lunas' : 'Belum Lunas'" />
                                    </td>
                                    <td style="text-align: center;">
                                        @if($actionValue === 'Create')
                                        <x-ui-button :clickEvent="'deleteNotaItem(' . $key . ')'" button-name="" loading="true"
                                            :action="$actionValue" cssClass="btn-danger text-danger"
                                            iconPath="delete.svg" />
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </x-slot>

                        <x-slot name="button">
                            {{-- Dropdown search untuk nota dari partner --}}
                            @if(!empty($inputs['partner_id']))
                            <div wire:key="nota-dropdown-{{ count($input_details) }}">
                                <x-ui-dropdown-search label="Pilih Nota" model="selectedNotaId"
                                    :query="$notaQuery"
                                    optionValue="id" optionLabel="tr_code,outstanding_amt_formatted,due_date"
                                    placeHolder="Pilih nota untuk ditambahkan..." :selectedValue="$selectedNotaId"
                                    :action="$actionValue" :enabled="$isPanelEnabled" type="int"
                                    onChanged="onNotaSelected" searchOnSpace="true"/>
                            </div>
                            @else
                            <div class="alert alert-info">
                                <small>Pilih customer terlebih dahulu untuk menampilkan daftar nota</small>
                            </div>
                            @endif
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
                        <x-ui-text-field model="totalAmtAdvance" label="Total Saldo Dipakai" :action="$actionValue"
                            enabled="false" type="number" />
                        <x-ui-text-field model="totalAmtSource" label="Total Pembayaran" :action="$actionValue"
                            enabled="false" type="number" />
                        <x-ui-text-field model="totalAmtBilling" label="Total Amt Nota" :action="$actionValue"
                            enabled="false" type="number" />
                        <x-ui-text-field model="overPayment" label="Lebih Bayar" :action="$actionValue" enabled="false"
                            type="number" />
                    </div>
                    <div class="row">
                        <x-ui-text-field model="totalPiutangCustomer" label="Total Piutang Customer Belum Bayar" :action="$actionValue"
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
        <x-ui-button clickEvent="onValidateAndSave" button-name="Save" loading="true" :action="$actionValue"
            cssClass="btn-primary" iconPath="save.svg" />
    </x-ui-footer>
    <br>


</div>

<script>
    $this - > dispatch('disable-onbeforeunload');

    // Reset dropdown after Livewire updates
    document.addEventListener('livewire:updated', () => {
        const selectElement = document.querySelector('[wire\\:model="selectedNotaId"]');
        if (selectElement && $(selectElement).hasClass('select2-hidden-accessible')) {
            // Reset Select2 dropdown value
            $(selectElement).val(null).trigger('change');
        }
    });
</script>
