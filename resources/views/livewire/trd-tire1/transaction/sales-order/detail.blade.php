<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
    <x-ui-page-card isForm="true"
        title="{{ $this->trans($actionValue) }} {!! $menuName !!} {{ isset($this->object->tr_code) && $this->object->tr_code ? ' (Nota #' . $this->object->tr_code . ')' : '' }}"
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
                                    <div class="row">
                                        <x-ui-option model="inputs.sales_type" :options="['I' => 'MOTOR', 'O' => 'MOBIL']" type="radio"
                                            layout="horizontal" :action="$actionValue" :enabled="$isPanelEnabled"
                                            onChanged="salesTypeOnChanged" />
                                        <x-ui-option model="inputs.tax_doc_flag" label="Faktur Pajak" :options="['isTaxInvoice' => 'Ya']"
                                            type="checkbox" layout="horizontal" :action="$actionValue" :enabled="$isPanelEnabled"
                                            :checked="$inputs['tax_doc_flag']" onChanged="onTaxDocFlagChanged" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="Tanggal Transaksi" model="inputs.tr_date" type="date"
                                            :action="$actionValue" required="true" :enabled="$isPanelEnabled"
                                            onChanged="onTrDateChanged" />
                                        <x-ui-text-field label="{{ $this->trans('tr_code') }}" model="inputs.tr_code"
                                            type="code" :action="$actionValue" required="true" clickEvent="trCodeOnClick"
                                            buttonName="Nomor Baru" enabled="false" :buttonEnabled="$isPanelEnabled" />
                                    </div>
                                    <div class="row">
                                        <x-ui-dropdown-search label="Customer" model="inputs.partner_id"
                                            optionValue="id" :query="$ddPartner['query']" :optionLabel="$ddPartner['optionLabel']" :placeHolder="$ddPartner['placeHolder']"
                                            :selectedValue="$inputs['partner_id']" required="true" :action="$actionValue" enabled="true"
                                            type="int" onChanged="onPartnerChanged" />
                                    </div>
                                    <div class="row">
                                        <x-ui-dropdown-select label="{{ $this->trans('ship_to') }}" clickEvent=""
                                            model="inputs.ship_to_name" :selectedValue="$inputs['ship_to_name']" :options="$shipOptions"
                                            required="true" :action="$actionValue" onChanged="onShipToChanged($event.target.value)" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field-search label="{{ $this->trans('tax_payer') }}"
                                            model="inputs.npwp_code" type="string" :selectedValue="$inputs['npwp_code']"
                                            :options="$npwpOptions" required="false" :action="$actionValue"
                                            clickEvent="openNpwpDialogBox" buttonName="+"
                                            onChanged="onTaxPayerChanged" />
                                        <x-ui-dialog-box id="NpwpDialogBox" title="Form Jenis" width="600px"
                                            height="400px" onOpened="openNpwpDialogBox" onClosed="closeNpwpDialogBox">
                                            <x-slot name="body">
                                                <div class="row">
                                                    <x-ui-text-field label="{{ $this->trans('NPWP/NIK') }}"
                                                        model="npwpDetails.npwp" type="text" :action="$actionValue"
                                                        required="true" capslockMode="true" />
                                                    <x-ui-text-field label="{{ $this->trans('Nama WP') }}"
                                                        model="npwpDetails.wp_name" type="text" :action="$actionValue"
                                                        required="true" capslockMode="true" />
                                                </div>
                                                <div class="row">
                                                    <x-ui-text-field label="{{ $this->trans('Alamat WP') }}"
                                                        model="npwpDetails.wp_location" type="textarea"
                                                        :action="$actionValue" required="true" />
                                                </div>
                                            </x-slot>
                                            <x-slot name="footer">
                                                <x-ui-button clickEvent="saveNpwp" button-name="Save" loading="true"
                                                    :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
                                            </x-slot>
                                        </x-ui-dialog-box>
                                    </div>
                                    <div class="row">
                                        <x-ui-dropdown-select label="{{ $this->trans('Pajak') }}"
                                            model="inputs.tax_code" :options="$SOTax" required="true"
                                            :action="$actionValue" onChanged="onSOTaxChange" />
                                        <x-ui-dropdown-select label="{{ $this->trans('Termin Pembayaran') }}"
                                            model="inputs.payment_term_id" :options="$paymentTerms" required="true"
                                            :action="$actionValue" onChanged="onPaymentTermChanged" :enabled="$isPanelEnabled" />
                                        <x-ui-text-field label="{{ $this->trans('due_date') }}"
                                            model="inputs.due_date" type="date" :action="$actionValue" required="true"
                                            :enabled="$isPanelEnabled" />
                                        <x-ui-text-field label="{{ $this->trans('reff_code') }}"
                                            model="inputs.reff_code" type="text" :action="$actionValue"
                                            required="false" />
                                        <x-ui-text-field label="Biaya pengiriman" model="inputs.amt_shipcost"
                                            type="number" :action="$actionValue" required="false" enabled="true" />
                                    </div>
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
                                <th style="width: 150px; text-align: center;">Kode</th>
                                <th style="width: 150px; text-align: center;">Harga Satuan</th>
                                <th style="width: 50px; text-align: center;">Quantity</th>
                                <th style="width: 90px; text-align: center;">Disc (%)</th>
                                <th style="width: 150px; text-align: center;">Amount</th>
                                <th style="width: 70px; text-align: center;">Aksi</th>
                            </x-slot>

                            <!-- Define table rows -->
                            <x-slot name="rows">
                                @foreach ($input_details ?? [] as $key => $input_detail)
                                    <tr wire:key="list{{ $input_detail['id'] ?? $key }}">
                                        <td style="text-align: center;">{{ $loop->iteration }}</td>
                                        <td>
                                            <x-ui-dropdown-search label=""
                                                model="input_details.{{ $key }}.matl_id" :query="$materialQuery"
                                                optionValue="id"
                                                optionLabel="{code};{name};Stok: {qty_oh};Rsv: {qty_fgi}"
                                                placeHolder="Select material..." :selectedValue="$input_details[$key]['matl_id'] ?? ''" required="true"
                                                :action="$actionValue" enabled="true"
                                                onChanged="onMaterialChanged({{ $key }}, $event.target.value)"
                                                type="int" :enabled="$isDeliv ? 'false' : 'true'" />
                                        </td>
                                        <td style="text-align: center;">
                                            <x-ui-text-field model="input_details.{{ $key }}.price"
                                                label="" :action="$actionValue" :enabled="$isDeliv ? 'false' : 'true'" type="number"
                                                onChanged="calcItemAmount({{ $key }})" decimalPlaces="2" />
                                        </td>
                                        <td style="text-align: center;">
                                            <x-ui-text-field model="input_details.{{ $key }}.qty"
                                                label="" :enabled="$isDeliv ? 'false' : 'true'" :action="$actionValue"
                                                onChanged="calcItemAmount({{ $key }})" type="number"
                                                required="true" />
                                        </td>
                                        <td style="text-align: center;">
                                            <x-ui-text-field model="input_details.{{ $key }}.disc_pct"
                                                label="" :action="$actionValue" :enabled="$isDeliv ? 'false' : 'true'"
                                                onChanged="calcItemAmount({{ $key }})" type="number" />
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
                                <x-ui-text-field model="inputs.print_remarks" label="Revision" :action="$actionValue"
                                    enabled="false" type="text" :value="$inputs['print_remarks']['nota'] ?? '0.0'" />
                            </div>
                        </x-ui-padding>
                    </x-ui-card>
                    <br>
                    <x-ui-footer>
                        @if ($actionValue !== 'Create' && isset($object->id))
                            <x-ui-button :action="$actionValue" clickEvent="goToPrintNota" cssClass="btn-primary"
                                loading="true" button-name="Cetak Nota Jual" iconPath="print.svg"
                                enabled="true || $canPrintNotaButton ? 'true' : 'false'" />

                            <x-ui-button :action="$actionValue" clickEvent="goToPrintSuratJalan" cssClass="btn-primary"
                                loading="true" button-name="Cetak Surat Jalan" iconPath="print.svg"
                                enabled="true" />
                        @endif

                        <x-ui-button clickEvent="delete" :action="$actionValue" :enabled="$isDeliv ? 'false' : ($canUpdateAfterPrint ? 'true' : 'false')"
                            type="delete" enableConfirmationDialog="true" :permissions="$permissions" />

                        <x-ui-button clickEvent="Save" :action="$actionValue" type="save" :enabled="$canUpdateAfterPrint || $canSaveButtonEnabled ? 'true' : 'false'" />
                    </x-ui-footer>
                </div>

        </x-ui-tab-view-content>
    </x-ui-page-card>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('printCounterUpdated', (newValue) => {
            const revisionField = document.querySelector('[wire\\:model="inputs.print_remarks"]');
            if (revisionField) {
                revisionField.value = newValue;
            }
        });
    });
    document.addEventListener("DOMContentLoaded", function() {
        const inputs = document.querySelectorAll("input");

        inputs.forEach((input, index) => {
            input.addEventListener("keydown", function(e) {
                if (e.key === "Enter") {
                    e.preventDefault(); // cegah submit
                    let next = inputs[index + 1];
                    if (next) {
                        next.focus(); // pindah ke field berikutnya
                    } else {
                        // kalau sudah di input terakhir, submit form
                        this.form.submit();
                    }
                }
            });
        });
    });
</script>
