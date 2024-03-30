<div>
    <div>
        <x-ui-button click-event="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card title="{{ $actionValue }} Sales Order" status="{{ $status }}">
        @if ($actionValue === 'Create')
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @else
            <x-ui-tab-view id="myTab" tabs="General, Sales Return"> </x-ui-tab-view>
        @endif
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="General" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card>
                    <x-ui-padding>
                        <x-ui-text-field label="Tgl Transaksi" model="inputs.tr_date" type="date" :action="$actionValue" required="true" span="Half" />
                        <x-ui-text-field-search label="Supplier" click-event="" model="inputs.partner_id" :options="$suppliers" required="true" :action="$actionValue" span="Half" />
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
                            {{-- <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#MaterialDialogBox">
                                    Tambah
                            </button> --}}
                            <x-ui-button click-event="Add" button-name="Tambah" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="add.svg" />
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
                                        <x-ui-text-field model="input_details.{{ $key }}.matl_code" label='Product Code' type="text" :action="$actionValue" placeHolder="" enabled="false" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.barcode" label='Label Code' type="text" :action="$actionValue" placeHolder="" enabled="false" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.matl_descr" label='Description' type="text" :action="$actionValue" placeHolder="Description" enabled="false" span="Full" />
                                        <x-ui-text-field model="input_details.{{ $key }}.selling_price" label='Selling Price' type="text" :action="$actionValue" placeHolder="Selling Price" enabled="false" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.price" label='Buying Price' type="number" :onChanged="'changePrice('. $key .', $event.target.value)'" :action="$actionValue" required="true" placeHolder="" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.qty" label='Qty' type="number" :onChanged="'changeQty('. $key .', $event.target.value)'" :action="$actionValue" required="true" placeHolder="" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.amt" label='Amount' type="text" :action="$actionValue" enabled="false" placeHolder="" span="Half" />
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
            <div class="tab-pane fade show" id="PurchaseReturn" role="tabpanel" aria-labelledby="PurchaseReturn-tab">
                <x-ui-card>
                    @include('layout.customs.buttons.create', ['route' => 'TrdJewel1.Procurement.PurchaseReturn.Detail', 'objectId' => $object->id])

                    <div class="table-responsive">
                        @livewire('trd-jewel1.procurement.purchase-order.purchase-return-data-table', ['returnIds' => $returnIds])
                    </div>
                </x-ui-card>
            </div>
        </x-ui-tab-view-content>
        <x-ui-footer>
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

