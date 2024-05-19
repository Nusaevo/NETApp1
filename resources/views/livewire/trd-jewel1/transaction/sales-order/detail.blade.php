<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card title="{{ $actionValue }} Sales Order" status="{{ $status }}">
        @if ($actionValue === 'Create')
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @else
            <x-ui-tab-view id="myTab" tabs="General, Purchase Return"> </x-ui-tab-view>
        @endif
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="General" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card>
                    <x-ui-padding>
                        <x-ui-text-field label="Tgl Transaksi" model="inputs.tr_date" type="date" :action="$actionValue" required="true" span="Half" />
                        <x-ui-text-field-search label="Customer" clickEvent="" model="inputs.partner_id" :options="$partners" required="true" :action="$actionValue" span="Half" />
                        <x-ui-text-field-search label="Payment" clickEvent="" model="inputs.payment_term_id" :options="$payments" required="true" :action="$actionValue" span="HalfWidth" />

                        {{-- @if ($actionValue === 'Create')
                            <x-ui-checklist label="Buat Nota Terima Supplier otomatis" model="inputs.app_id" :options="['1' => 'Ya']" :action="$actionValue" span="Full" />
                        @endif --}}
                    </x-ui-padding>

                    <x-ui-dialog-box id="MaterialDialogBox" :visible="$materialDialogVisible" :width="'2000px'" :height="'2000px'">
                        <x-slot name="title">
                        </x-slot>
                        <x-slot name="body">
                            @livewire('trd-jewel1.master.material.material-component', ['actionValue' => $matl_action, 'objectIdValue' => $matl_objectId])
                        </x-slot>
                    </x-ui-dialog-box>

                    <x-ui-list-table id="Table" title="Barang">
                        <x-slot name="button">
                            <div style="display: flex; justify-content: start; align-items: center; gap: 10px;">
                                <x-ui-text-field label="" model="barcode" type="barcode" required="false" placeHolder="Input Kode Manual" span="Half" style="flex-grow: 1;" onChanged="scanManual" />
                                <x-ui-button clickEvent="Add" button-name="Scan RFID" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="add.svg" />
                            </div>
                        </x-slot>
                        <x-slot name="body">
                            @foreach($input_details as $key => $detail)
                            <tr wire:key="list{{ $key }}">
                                <x-ui-list-body>
                                    <x-slot name="image">
                                        @php
                                        $imagePath = isset($detail['image_path']) && !empty($detail['image_path']) ? $detail['image_path'] : 'https://via.placeholder.com/300';
                                        @endphp
                                        <img src="{{ $imagePath }}" alt="Material Photo" style="width: 200px; height: 200px;">
                                    </x-slot>

                                    <x-slot name="rows">
                                        <x-ui-text-field model="input_details.{{ $key }}.matl_code" label='{{ $this->trans("code") }}' type="text" :action="$actionValue" placeHolder="" enabled="false" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.barcode" label='{{ $this->trans("barcode") }}' type="text" :action="$actionValue" placeHolder="" enabled="false" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.name" label='{{ $this->trans("name") }}' type="text" :action="$actionValue" placeHolder="" enabled="false" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.matl_descr" label='{{ $this->trans("description") }}' type="text" :action="$actionValue" placeHolder="" enabled="false" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.selling_price" label='{{ $this->trans("selling_price") }}' :onChanged="'changePrice('. $key .', $event.target.value)'"  type="number" :action="$actionValue" placeHolder="" enabled="true" span="Full" />
                                        <x-ui-text-field model="input_details.{{ $key }}.qty" label='{{ $this->trans("qty") }}' type="number" enabled="false" :action="$actionValue" required="true" placeHolder="" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.amt" label='{{ $this->trans("amount") }}' type="number" :action="$actionValue" enabled="false" placeHolder="" span="Half" />
                                    </x-slot>
                                    <x-slot name="button">
                                        <a href="#" wire:click="deleteDetails({{ $key }})" class="btn btn-link">
                                            X
                                        </a>
                                    </x-slot>
                                </x-ui-list-body>
                            </tr>
                            @endforeach
                        </x-slot>
                        <x-slot name="footer">
                            <h3>Total Price: {{ rupiah($total_amount) }}</h3>
                        </x-slot>
                    </x-ui-list-table>
            </x-ui-card>
            </div>
            {{-- <div class="tab-pane fade show" id="PurchaseReturn" role="tabpanel" aria-labelledby="PurchaseReturn-tab">
                <x-ui-card>
                    @include('layout.customs.buttons.create', ['route' => 'TrdJewel1.Procurement.PurchaseReturn.Detail', 'objectId' => $object->id])

                    <div class="table-responsive">
                        @livewire('trd-jewel1.procurement.purchase-order.purchase-return-data-table', ['returnIds' => $returnIds])
                    </div>
                </x-ui-card>
            </div> --}}
        </x-ui-tab-view-content>
        <x-ui-footer>
            @if ($actionValue === 'Edit')
            <x-ui-button :action="$actionValue" clickEvent="createReturn"
                cssClass="btn-primary" loading="true" button-name="Create Purchase Return" iconPath="add.svg" />
            @endif
            @include('layout.customs.transaction-form-footer')
        </x-ui-footer>
    </x-ui-page-card>
@php
    // dump($object->id);
@endphp
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.livewire.on('closeMaterialDialog', function() {
            $('#MaterialDialogBox').modal('hide');
        });
    });

</script>

