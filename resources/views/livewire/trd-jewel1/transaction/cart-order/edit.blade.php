<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <div>
            <x-ui-button click-event="" type="Back" button-name="Back" />
        </div>
    </div>

    <x-ui-page-card title="{{ $actionValue }} Puchase Order" status="{{ $status }}">

        {{-- @if ($actionValue !== 'Create')
        <x-ui-tab-view id="myTab" tabs="general,Nota Terima Supplier"> </x-ui-tab-view>
        @else --}}
        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>
        {{-- @endif --}}


            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-expandable-card id="UserCard" title="Puchase Order Info" :isOpen="true">
                        <x-ui-text-field label="Tgl Transaksi" model="inputs.tr_date" type="date" :action="$actionValue" required="true" />

                        <x-ui-text-field-search label="Supplier" name="Supplier" click-event="refreshSupplier" model="inputs.partner_id" :options="$suppliers" required="true" :action="$actionValue" />

                        <x-ui-dropdown-select label="Payment" name="Payment" click-event="refreshSupplier" model="inputs.payment_term_id" :options="$payments"  required="true" :action="$actionValue" />

                        <x-ui-table id="POTable">
                            <x-slot name="title">
                                Detail
                            </x-slot>

                            <x-slot name="button">
                                <x-ui-button click-event="addDetails" cssClass="btn btn-success" iconPath="images/create-icon.png" button-name="Tambah" :action="$actionValue" />
                            </x-slot>

                            <x-slot name="headers">
                                <th class="min-w-50px">No</th>
                                <th class="min-w-300px">Barang</th>
                                {{-- <th class="min-w-100px">Harga</th> --}}
                                <th class="min-w-100px">Qty</th>
                                {{-- <th class="min-w-15px">Sub Total</th> --}}
                                <th class="min-w-15px">Action</th>
                            </x-slot>

                            <x-slot name="rows">
                                @foreach($input_details as $key => $detail)
                                <tr>
                                    <td class="border">
                                        {{$key+1}}
                                    </td>
                                    <td class="border">
                                        <div>
                                            <input type="hidden" wire:model.defer='input_details.{{ $key }}.item_unit_id'>
                                            <div wire:ignore class="input-form ">
                                                <select class="form-select itemsearch form-control p-2" id="item-{{$key}}">
                                                    @if(isset($input_details[$key]['detail_item_name']))
                                                    <option value='input_details.{{ $key }}.item_unit_id'>{{$input_details[$key]['detail_item_name']}}</option>
                                                    @endif
                                                </select>
                                            </div>
                                        </div>
                                    </td>
                                    {{-- <td class="border">
                                        <x-ui-text-field model="input_details.{{ $key }}.price" label='' type="number"
                                    :onChanged="'changePrice('. $key .', $event.target.value)'"
                                    :action="$actionValue" required="true" placeHolder="" />
                                    </td> --}}
                                    <td class="border">
                                        <x-ui-text-field model="input_details.{{ $key }}.qty" label='' type="number" :onChanged="'changeQty('. $key .', $event.target.value)'" :action="$actionValue" required="true" placeHolder="" />
                                    </td>
                                    {{-- <td class="border">
                                        <x-ui-text-field model="input_details.{{ $key }}.sub_total" label='' type="text" :action="$actionValue" enabled="false" placeHolder="" />
                                    </td> --}}

                                    <td>
                                        <x-ui-button button-name="Delete" click-event="deleteDetails({{ $key }})" :action="$actionValue" cssClass="btn-danger" />
                                    </td>
                                </tr>

                                @endforeach
                                {{-- <tr>
                                    <td align="right" colspan="4">Total Harga</td>
                                    <td align="left" colspan="4"> {{ rupiah($total_amount) }}</td>
                                </tr> --}}
                            </x-slot>
                        </x-ui-table>

                    </x-ui-expandable-card>
                </div>
                {{-- @if ($actionValue !== 'Create')
                <div class="tab-pane fade" id="NotaTerimaSupplier" role="tabpanel" aria-labelledby="delivery-tab">
                    <x-ui-expandable-card id="UserPassword" title="Nota Terima Supplier" :isOpen="true">

                        <x-ui-table id="PDTable">

                            <x-slot name="button">
                                <x-ui-button visible="true" enabled="true" click-event="{{ route('purchases_deliveries.detail', ['action' => encryptWithSessionKey('Create'), 'objectId' => encryptWithSessionKey($object->id)]) }}" cssClass="btn btn-success mb-5" type="Route" loading="true" iconPath="images/create-icon.png" button-name="Create" />
                            </x-slot>

                            <x-slot name="headers">
                                <th class="min-w-50px">No Nota</th>
                                <th class="min-w-300px">Tanggal Transaksi</th>
                                <th class="min-w-300px">Supplier</th>
                                <th class="min-w-300px">Penerima</th>
                                <th class="min-w-15px">Action</th>
                            </x-slot>

                            <x-slot name="rows">
                            </x-slot>
                        </x-ui-table>
                    </x-ui-expandable-card>
                </div>
                @endif --}}
            </x-ui-tab-view-content>

        <div class="card-footer d-flex justify-content-end">

            <x-ui-button click-event="Save" button-name="Save Data" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="images/save-icon.png" />

           @if ($actionValue !== 'Create')
               <x-ui-button click-event="Submit" button-name="Submit Nota Terima Barang" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="images/save-icon.png" />
               <x-ui-button click-event="Print" button-name="Print Nota" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="images/save-icon.png" />
           @endif
       </div>


    </x-ui-page-card>
</div>

<script>
    $(document).ready(function() {
        // Function to initialize select2
        function initializeSelect2(element) {
            element.select2({
                placeholder: 'Select Item'
                , ajax: {
                    url: '/search-item'
                    , dataType: 'json'
                    , delay: 250
                    , processResults: function(data) {
                        return {
                            results: $.map(data, function(item) {
                                return {
                                    text: item.name
                                    , id: item.id
                                }
                            })
                        };
                    }
                    , cache: true
                }
            });
            element.on('change', function(e) {
                const itemClass = Array.from(document.querySelectorAll('.itemsearch'));
                var index = itemClass.indexOf(e.target) / 2;
                Livewire.emit('changeItem', e.target.id, e.target.value, index);
            });
        }

        // Initialize select2 for all .itemsearch inputs
        $('.itemsearch').each(function() {
            initializeSelect2($(this));
        });

        // Event listener for reapplying select2
        window.addEventListener('reApplySelect2', event => {
            $('.itemsearch').each(function() {
                initializeSelect2($(this));
            });
        });
    });

</script>
