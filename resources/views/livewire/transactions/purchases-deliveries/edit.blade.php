<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <div>
            <x-ui-button click-event="{{ route('purchases_deliveries.index') }}" type="Back" button-name="Back"/>
        </div>
    </div>

    <x-ui-page-card title="{{ $action }} Terima Gudang" status="{{ $status }}">

        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>

        <form wire:submit.prevent="{{ $action }}" class="form w-100">
            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-expandable-card id="UserCard" title="Terima Gudang Info" :isOpen="true">
                        <x-ui-text-field label="Tgl Terima" model="inputs.tr_date" type="date" :action="$action" required="true" />

                        <x-ui-text-field-search label="Supplier"
                        name="Supplier"
                        click-event="refreshSupplier"
                        model="inputs.partner_id"
                        :options="$suppliers"
                        :selectedValue="$inputs['partner_id']"
                        enabled="false"
                        required="true"
                        :action="$action"/>

                        <div class="card-body p-2 mt-10">
                            <h2 class="mb-2 text-center">Barang</h2>

                            <div class="table-responsive mt-5">
                                <table id="tbl" class="table table-striped table-hover gy-7 gs-7">
                                    <thead>
                                        <tr class="fw-bold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                                            <th class="min-w-50px">No</th>
                                            <th class="min-w-300px">Barang</th>
                                            {{-- <th class="min-w-100px">Harga</th> --}}
                                            <th class="min-w-100px">Qty</th>
                                            <th class="min-w-100px">Qty diterima</th>
                                            <th class="min-w-100px">Gudang</th>
                                            {{-- <th class="min-w-15px">Sub Total</th> --}}
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($input_details as $key => $detail)
                                        <tr>
                                            <td class="border">
                                                {{$key+1}}
                                            </td>
                                            <td class="border">
                                                <div >
                                                        <input type="hidden" wire:model.defer='input_details.{{ $key }}.item_unit_id'>
                                                        <div wire:ignore class="input-form ">
                                                            <select  class="form-select itemsearch form-control p-2" id="item-{{$key}}" >
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
                                                 :action="$action" required="true" placeHolder="" />
                                            </td> --}}
                                            <td class="border">
                                                <x-ui-text-field model="input_details.{{ $key }}.order_qty" label='' type="number"
                                                 :action="$action" required="true" placeHolder="" enabled="false" />
                                            </td>
                                            <td class="border">
                                                <x-ui-text-field model="input_details.{{ $key }}.qty" label='' type="number"
                                                 :action="$action" required="true" placeHolder="" />
                                            </td>
                                            <td class="border">
                                                <x-ui-dropdown-select
                                                click-event=""
                                                label=""
                                                :model="'input_details.' . $key . '.warehouse_id'"
                                                :options="$warehouses"
                                                :selectedValue="$detail['warehouse_id']"
                                                required="true"
                                                :action="$action"/>
                                            </td>
                                            {{-- <td class="border">
                                                <x-ui-text-field model="input_details.{{ $key }}.sub_total" label=''  type="text" :action="$action" enabled="false" placeHolder="" />
                                            </td> --}}
                                        </tr>

                                        @endforeach
                                        {{-- <tr>
                                            <td align="right" colspan="4">Total Harga</td>
                                            <td align="left" colspan="4"> {{ rupiah($total_amount) }}</td>
                                        </tr> --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </x-ui-expandable-card>
                </div>
            </x-ui-tab-view-content>
        </form>
        <div class="card-footer d-flex justify-content-end">
            @if ($status !== 'Posted')
                {{-- @if ($action === 'Create')
                    <div style="padding-right: 10px;">
                        <x-ui-button click-event="CreateDraft" button-name="Save as Draft" loading="true" :action="$action" cssClass="btn-secondary" iconPath="images/save-icon.png" />
                    </div>
                @else
                    <div style="padding-right: 10px;">
                        <x-ui-button click-event="EditDraft" button-name="Save as Draft" loading="true" :action="$action" cssClass="btn-secondary" iconPath="images/save-icon.png" />
                    </div>
                @endif --}}
            @endif
            <div>
                <x-ui-button click-event="{{ $action }}" button-name="Save" loading="true" :action="$action" cssClass="btn-primary" iconPath="images/save-icon.png" />
            </div>
        </div>

    </x-ui-page-card>
</div>
