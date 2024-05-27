<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back"/>
    </div>
    <x-ui-page-card title="{{ $this->trans($actionValue) . ' ' . $this->trans('cart_order') }}" status="{{ $this->trans($status) }}">

        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_catalogue">
            Launch demo modal
        </button>

        @if ($actionValue === 'Create')
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @else
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @endif
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="General" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card>

                    <x-ui-list-table id="Table" title="">
                        <x-slot name="button">
                            <div style="display: flex; justify-content: start; align-items: center; gap: 10px;">
                                <x-ui-button clickEvent="Add" button-name="Scan RFID" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="add.svg" />

                                <button type="button" wire:click="SaveWithoutNotification" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="">
                                    <span style="font-size: 16px;"> {{ $this->trans('btnAdd') }}</span>
                                </button>
                            </div>
                            {{-- <x-ui-button clickEvent="Add" button-name="Tambah" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="add.svg" /> --}}
                        </x-slot>
                        <x-slot name="body">
                            @foreach($input_details as $key => $detail)
                            <tr wire:key="list{{ $key }}">
                                <x-ui-list-body>
                                    <x-slot name="image">
                                        <div class="form-option" style="display: flex; align-items: center; margin-left: 10px;">
                                            <input type="checkbox" wire:model="input_details.{{$key}}.checked" id="option{{ $key }}" style="width: 20px; height: 20px; margin-right: 5px;"/>
                                        </div>
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
                            <h3>{{ $this->trans('totalPrice') }}: {{ rupiah($total_amount) }}</h3>
                        </x-slot>
                    </x-ui-list-table>
            </x-ui-card>
            </div>
        </x-ui-tab-view-content>
        <x-ui-footer>
            <x-ui-button :action="$actionValue" clickEvent="Save"
                cssClass="btn-primary" loading="true" button-name="Save" iconPath="save.svg" />
            <x-ui-button :action="$actionValue" clickEvent="Checkout"
                cssClass="btn-primary" loading="true" button-name="Checkout" iconPath="add.svg" />
        </x-ui-footer>
    </x-ui-page-card>

    <div class="modal bg-body fade" tabindex="-1" id="kt_modal_catalogue" wire:ignore.self>
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content shadow-none">
                <div class="modal-header">
                    <h5 class="modal-title">Katalog Produk</h5>

                    <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                        <i class="ki-duotone ki-cross fs-2x"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>

                <div class="modal-body">
                    <x-ui-expandable-card id="FilterSearchCard" title="Filter" :isOpen="false">
                        <form wire:submit.prevent="search">
                            <div class="card-body">
                                <x-ui-text-field label="Cari Nama Barang" model="inputsearches.name" type="text" action="Edit" placeHolder="" span='Full'/>
                                <x-ui-text-field label="Cari Nama Bahan" model="inputsearches.description" type="text" action="Edit" placeHolder="" span='Full'/>
                                <x-ui-text-field label="Harga Jual" model="inputsearches.selling_price1" type="number" action="Edit" placeHolder="" span='Half'/>
                                <x-ui-text-field label="" model="inputsearches.selling_price2" type="number" action="Edit" placeHolder="" span='Half'/>
                                <x-ui-text-field label="Code Barang" model="inputsearches.code" type="text" action="Edit" placeHolder="" span='Full'/>
                            </div>

                            <div class="card-footer d-flex justify-content-end">
                                <div>
                                    <x-ui-button clickEvent="search" button-name="Search" loading="true" action="search" cssClass="btn-primary" />
                                </div>
                            </div>
                        </form>
                    </x-ui-expandable-card>

                    <div class="table-responsive">
                        <table class="table table-striped gy-7 gs-7">
                            <thead>
                                <tr class="fw-semibold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                                    <th class="min-w-200px">Name</th>
                                    <th class="min-w-400px">Code</th>
                                    <th class="min-w-100px">Desc</th>
                                    <th class="min-w-200px">Sell Price</th>
                                    <th class="min-w-200px">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($materials as $material)
                                    <tr>
                                        <td>{{ $material->name ?? 'N/A' }}</td>
                                        <td>{{ $material->code }}</td>
                                        <td>{{ $material->descr }}</td>
                                        <td>{{ dollar(currencyToNumeric($material->jwl_selling_price)) }} - {{ rupiah(currencyToNumeric($material->jwl_selling_price) * $currencyRate) }}</td>
                                        <td>
                                            <x-ui-button clickEvent="addToCart({{ $material->id }}, '{{ $material->code }}')" button-name="AddToCard" loading="true" action="Edit" cssClass="btn-primary" />
                                        </td>
                                    </tr>
                                @endforeach

                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    {{-- <button type="button" class="btn btn-primary">Save changes</button> --}}
                </div>
            </div>
        </div>
    </div>
</div>

