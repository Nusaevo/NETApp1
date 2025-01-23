<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
    <x-ui-page-card
        title="{{ $this->trans($actionValue) }} {!! $menuName !!} {{ $this->object->tr_id ? ' (Nota #' . $this->object->tr_id . ')' : '' }}"
        status="{{ $this->trans($status) }}">

        @if ($actionValue === 'Create')
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @else
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @endif
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="General" role="tabpanel" aria-labelledby="general-tab">
                <div class="row mt-4">
                    <div class="col-md-8">
                        <x-ui-card title="Main Information">
                            <x-ui-padding>
                                <div class="row">
                                    <x-ui-option model="inputs.vehicle_type" :options="['0' => 'MOTOR', '1' => 'MOBIL']" type="radio"
                                        layout="horizontal" :action="$actionValue" :enabled="$isPanelEnabled" />
                                    <x-ui-option model="inputs.tax_invoice" label="Faktur Pajak" :options="['isTaxInvoice' => 'Ya']"
                                        type="checkbox" layout="horizontal" :action="$actionValue" :enabled="$isPanelEnabled"
                                        onChanged="onTaxInvoiceChanged" :checked="$inputs['tax_invoice']" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field label="{{ $this->trans('tr_id') }}" model="inputs.tr_id"
                                        type="code" :action="$actionValue" required="false"
                                        clickEvent="getTransactionCode" buttonName="Nomor" enabled="true" />
                                    <x-ui-text-field label="Tanggal Transaksi" model="inputs.tr_date" type="date"
                                        :action="$actionValue" required="true" :enabled="$isPanelEnabled" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field-search type="int" label="{{ $this->trans('custommer') }}"
                                        clickEvent="" model="inputs.partner_id" :selectedValue="$inputs['partner_id']" :options="$partners"
                                        required="true" :action="$actionValue" :enabled="$isPanelEnabled"
                                        onChanged="updatedInputsPartnerId" />
                                    <x-ui-dropdown-select label="{{ $this->trans('send_to') }}" model="inputs.send_to"
                                        :options="$SOSend" type="text" :action="$actionValue" required="false" />
                                    <x-ui-text-field-search type="text" label="{{ $this->trans('tax_payer') }}"
                                        clickEvent="" model="inputs.tax_payer" :selectedValue="$inputs['tax_payer']" :options="$npwpOptions"
                                        required="false" :action="$actionValue" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field label="{{ $this->trans('payment_terms') }}"
                                        model="inputs.payment_terms" type="text" :action="$actionValue"
                                        required="false" />
                                    <x-ui-dropdown-select label="{{ $this->trans('tax') }}" model="inputs.tax"
                                        :options="$SOTax" required="true" :action="$actionValue"
                                        onChanged="onSOTaxChange" />
                                </div>

                            </x-ui-padding>
                        </x-ui-card>
                    </div>
                    <div class="col-md-4">
                        <x-ui-card title="Detail Information">
                            <x-ui-text-field label="{{ $this->trans('due_date') }}" model="inputs.due_date"
                                type="date" :action="$actionValue" required="true" :enabled="$isPanelEnabled" />
                            <x-ui-text-field label="{{ $this->trans('cust_reff') }}" model="inputs.cust_reff"
                                type="text" :action="$actionValue" required="false" />
                        </x-ui-card>
                    </div>
                    <x-ui-footer>
                        <div>
                            <x-ui-button clickEvent="Save" button-name="Save Header" loading="true" :action="$actionValue"
                                cssClass="btn-primary" iconPath="save.svg" />
                        </div>

                    </x-ui-footer>
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
                                <x-ui-text-field label="{{ $this->trans('PPN') }}" model="total_tax"
                                    type="text" :action="$actionValue" required="false" enabled="false" />
                                <x-ui-text-field label="{{ $this->trans('DPP') }}" model="total_dpp" type="text"
                                    :action="$actionValue" required="false" enabled="false"/>
                                <x-ui-text-field label="{{ $this->trans('total_amount') }}" model="total_amount"
                                    type="text" :action="$actionValue" required="false" enabled="false" />
                            </div>
                            <div class="row">
                                <div class="col-md-2">
                                    <x-ui-text-field label="{{ $this->trans('version') }}" model="versionNumber"
                                        type="text" :action="$actionValue" required="false" enabled="false" />
                                </div>
                                <div class="col-md-10">
                                    <x-ui-button clickEvent="createDelivery" button-name="Buat Surat Jalan"
                                        loading="true" :action="$actionValue" cssClass="btn-primary" />
                                    <x-ui-button clickEvent="printDelivery" button-name="Cetak Surat Jalan"
                                        loading="true" :action="$actionValue" cssClass="btn-primary" />
                                    <x-ui-button clickEvent="printInvoice" button-name="Cetak Nota Jual"
                                        loading="true" :action="$actionValue" cssClass="btn-primary" />
                                    <x-ui-button clickEvent="Save" button-name="Save" loading="true"
                                        :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
                                </div>
                            </div>
                        </x-ui-padding>
                    </x-ui-card>
                </div>
            </div>
        </x-ui-tab-view-content>
    </x-ui-page-card>
</div>
