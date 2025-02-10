<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
    <x-ui-page-card title="{{ $this->trans($actionValue)}} {!! $menuName !!}  {{ $this->object->tr_id ? ' (Nota #' . $this->object->tr_id . ')' : '' }}" status="{{ $this->trans($status) }}">

        @if ($actionValue === 'Create')
        <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @else
        <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @endif
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="General" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card>
                    <x-ui-padding>
                        <div class="row">
                            <x-ui-text-field label="Tgl Transaksi" model="inputs.tr_date" type="date" :action="$actionValue" required="true" :enabled="$isPanelEnabled" />
                            <x-ui-text-field-search type="int" label='{{ $this->trans("partner") }}' clickEvent="" model="inputs.partner_id" :selectedValue="$inputs['partner_id']"
                                :options="$partners" required="true" :action="$actionValue" :enabled="$isPanelEnabled" />
                        </div>

                    </x-ui-padding>

                    <x-ui-list-table id="Table" title="Barang">
                        <x-slot name="button">
                            <div style="display: flex; justify-content: start; align-items: center; gap: 10px;">
                                <x-ui-button clickEvent="OpenDialogBox" cssClass="btn btn-primary" iconPath="add.svg" button-name="{{ $this->trans('btnAdd') }}" :action="$actionValue" />
                                <x-ui-dialog-box id="catalogue" :width="'2000px'" :height="'2000px'">
                                    <x-slot name="body">
                                        <!-- Search Feature -->
                                        <div class="mb-3 d-flex">
                                            <input type="text" class="form-control" placeholder="Search by No Nota, Material Code, Material Description" wire:model.debounce.300ms="searchTerm">
                                            <x-ui-button :action="$actionValue" clickEvent="searchMaterials" cssClass="btn-primary" loading="true" button-name="Search" iconPath="" />
                                        </div>

                                        <x-ui-table id="CatalogueTable">
                                            <x-slot name="headers">
                                                <th class="min-w-50px">Select</th>
                                                <th class="min-w-100px">No Nota</th>
                                                <th class="min-w-100px">Material Details</th>
                                                <th class="min-w-100px">Selling Price</th>
                                            </x-slot>
                                            <x-slot name="rows">
                                                @if(empty($orderDtls) || count($orderDtls) === 0)
                                                <tr>
                                                    <td colspan="5" class="text-center">Tidak ada data ditemukan</td>
                                                </tr>
                                                @else
                                                @foreach($orderDtls as $index => $orderDtl)
                                                <tr wire:key="orderDtl-{{ $index }}">
                                                    <td>
                                                        <input type="checkbox" wire:model="selectedMaterials" value="{{ $orderDtl['orderDtlId'] }}">
                                                    </td>
                                                    <td>
                                                        {{ $orderDtl['tr_id'] }}
                                                    </td>
                                                    <td>
                                                        Kode Produk: {{ $orderDtl['materialCode'] }} <br>
                                                        Deskripsi Material: {{ $orderDtl['materialName'] }} <br>
                                                        Deskripsi Bahan: {{ $orderDtl['materialDescr'] }}
                                                    </td>
                                                    <td>
                                                        {{ rupiah($orderDtl['price']) }}
                                                    </td>
                                                </tr>
                                                @endforeach
                                                @endif
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
                                        <x-ui-image src="{{ $imagePath }}" alt="Material" width="200px" height="200px" />
                                    </x-slot>

                                    <x-slot name="rows">
                                        <div class="row">
                                            <x-ui-text-field model="input_details.{{ $key }}.dlvhdrtr_id" label='{{ $this->trans("reff_number") }}' type="text" :action="$actionValue" enabled="false" />
                                            <x-ui-text-field model="input_details.{{ $key }}.matl_code" label='{{ $this->trans("code") }}' type="text" :action="$actionValue" enabled="false" />
                                        </div>
                                        <div class="row">
                                            <x-ui-text-field model="input_details.{{ $key }}.name" label='{{ $this->trans("name") }}' type="text" :action="$actionValue" enabled="false" />
                                            <x-ui-text-field model="input_details.{{ $key }}.matl_descr" label='{{ $this->trans("description") }}' type="text" :action="$actionValue" enabled="false" />
                                        </div>
                                        <div class="row">
                                            <x-ui-text-field model="input_details.{{ $key }}.selling_price" label='{{ $this->trans("selling_price") }}' :onChanged="'changePrice('. $key .', $event.target.value)'" type="number" :action="$actionValue" enabled="false" />

                                            <x-ui-text-field model="input_details.{{ $key }}.price" label='{{ $this->trans("price") }}' :onChanged="'changePrice('. $key .', $event.target.value)'" type="number" :action="$actionValue" enabled="true" />
                                            <x-ui-text-field model="input_details.{{ $key }}.qty" label='{{ $this->trans("qty") }}' type="number" enabled="false" :action="$actionValue" required="true" />
                                        </div>
                                    </x-slot>
                                    <x-slot name="button">
                                        <x-ui-link-text type="close" :clickEvent="'deleteDetails(' . $key . ')'" class="btn btn-link" name="x" :action="$actionValue" />

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
        </x-ui-tab-view-content>
        <x-ui-footer>
            @include('layout.customs.transaction-form-footer')
        </x-ui-footer>
    </x-ui-page-card>
    @php
    // dump($object->id);
    @endphp
</div>
@push('scripts')
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
@endpush
