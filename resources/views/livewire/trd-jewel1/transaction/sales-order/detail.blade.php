<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
    <x-ui-page-card title="{{ $this->trans($actionValue) . '  Sales Order'}}{{ $this->object->tr_id ? ' (Nota #' . $this->object->tr_id . ')' : '' }}" status="{{ $this->trans($status) }}">

        @if ($actionValue === 'Create')
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @else
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @endif
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="General" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card>
                    <x-ui-padding>
                        <x-ui-text-field label="Tgl Transaksi" model="inputs.tr_date" type="date" :action="$actionValue" required="true" span="Half" />
                        <x-ui-dropdown-select label='{{ $this->trans("payment") }}' clickEvent="" model="inputs.payment_term_id" :options="$payments" required="true" :action="$actionValue" span="Half" onChanged="SaveCheck"/>
                        <x-ui-text-field-search label='{{ $this->trans("partner") }}' clickEvent="" model="inputs.partner_id" :options="$partners" required="true" :action="$actionValue" span="HalfWidth" onChanged="SaveCheck"/>
                    </x-ui-padding>

                    <x-ui-list-table id="Table" title="Barang">
                        <x-slot name="button">
                            <div style="display: flex; justify-content: start; align-items: center; gap: 10px;">
                                @livewire('component.rfid-scanner', ['duration' => 1000])

                                {{-- <button id="scanButton" class="btn btn-primary" wire:click="tagScanned">Scan</button> --}}
                                <button type="button" wire:click="OpenDialogBox" class="btn btn-primary" >
                                    {{ $this->trans('btnAdd') }}
                                </button>

                                <x-ui-dialog-box id="catalogue" :width="'2000px'" :height="'2000px'">
                                    <x-slot name="body">
                                        <!-- Search Feature -->
                                        <div class="mb-3 d-flex">
                                            <input type="text" class="form-control" placeholder="Search by kode produk, deskripsi material dan deskripsi bahan" wire:model.debounce.300ms="searchTerm">
                                            <x-ui-button :action="$actionValue" clickEvent="searchMaterials" cssClass="btn-primary" loading="true" button-name="Search" iconPath="" />
                                        </div>

                                        <!-- Table -->
                                        <x-ui-table id="CatalogueTable">
                                            <x-slot name="headers">
                                                <th class="min-w-50px">Select</th>
                                                <th class="min-w-100px">Image</th>
                                                <th class="min-w-100px">Detail</th>
                                                <th class="min-w-100px">Selling Price</th>
                                            </x-slot>

                                            <x-slot name="rows">
                                                @foreach($materials as $material)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" wire:model="selectedMaterials" value="{{ $material->id }}">
                                                    </td>
                                                    <td>
                                                        @php
                                                        $imagePath = $material->Attachment->first() ? $material->Attachment->first()->getUrl() : 'https://via.placeholder.com/100';
                                                        @endphp
                                                        <img src="{{ $imagePath }}" alt="Material Image" style="width: 100px; height: 100px; object-fit: cover;">
                                                    </td>
                                                    <td> Kode Produk : {{ $material->code }} <br> Deskripsi Material : {{ $material->name }} <br> Deskripsi Bahan : {{ $material->descr }}</td>
                                                    <td>{{ rupiah(currencyToNumeric($material->jwl_selling_price) * $currencyRate)}}</td>
                                                </tr>
                                                @endforeach
                                            </x-slot>
                                        </x-ui-table>
                                    </x-slot>
                                    <x-slot name="footer">
                                        <x-ui-button :action="$actionValue" clickEvent="addSelectedToCart" cssClass="btn-primary" loading="true" button-name="Add" iconPath="add.svg" />
                                    </x-slot>
                                </x-ui-dialog-box>

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

                    <div class="table-container">
                        @livewire('trd-jewel1.procurement.purchase-order.purchase-return-data-table', ['returnIds' => $returnIds])
                    </div>
                </x-ui-card>
            </div> --}}
        </x-ui-tab-view-content>
        <x-ui-footer>
            {{-- @if ($actionValue === 'Edit')
            <x-ui-button :action="$actionValue" clickEvent="createReturn"
                cssClass="btn-primary" loading="true" button-name="Create Purchase Return" iconPath="add.svg" />
            @endif --}}
            @include('layout.customs.transaction-form-footer')
        </x-ui-footer>
    </x-ui-page-card>
@php
    // dump($object->id);
@endphp
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.addEventListener('openMaterialDialog', function() {
            $('#catalogue').modal('show');
        });

        window.addEventListener('closeMaterialDialog', function() {
            $('#catalogue').modal('hide');
        });
    });
</script>

