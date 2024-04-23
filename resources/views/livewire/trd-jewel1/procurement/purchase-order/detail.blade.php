<div>
    <div>
        <x-ui-button click-event="" type="Back" button-name="Back"/>
    </div>

    <x-ui-page-card title="{{ $this->trans($actionValue) . ' ' . $this->trans('purchase_order') }}" status="{{ $this->trans($status) }}">
        @if ($actionValue === 'Create')
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @else
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @endif
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="General" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card>
                    <x-ui-padding>
                        <x-ui-text-field label="{{ $this->trans('date') }}" model="inputs.tr_date" type="date" :action="$actionValue" required="true" span="Half" />
                        <x-ui-text-field-search label="{{ $this->trans('partner') }}" click-event="" model="inputs.partner_id" :options="$suppliers" required="true" :action="$actionValue" span="Half" />
                        {{-- <x-ui-dropdown-select label="{{ $this->trans('warehouse') }}" click-event="" model="inputs.wh_code" :options="$warehouses" required="true" :action="$actionValue" span="Half" />
                        <x-ui-text-field label="Deliv by" model="inputs.deliv_by" type="text" :action="$actionValue" span="Half" placeHolder=""/> --}}
                        {{-- @if ($actionValue === 'Create')
                            <x-ui-checklist label="Buat Nota Terima Supplier otomatis" model="inputs.app_id" :options="['1' => 'Ya']" :action="$actionValue" span="Full" />
                        @endif --}}
                    </x-ui-padding>

                    <x-ui-dialog-box id="MaterialDialogBox" :width="'2000px'" :height="'2000px'">
                        <x-slot name="title">
                        </x-slot>
                        <x-slot name="body">
                            @livewire('trd-jewel1.master.material.material-component', ['actionValue' => $matl_action, 'objectIdValue' => $matl_objectId])
                        </x-slot>
                    </x-ui-dialog-box>

                    <x-ui-list-table id="Table" title="">
                        <x-slot name="button">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#MaterialDialogBox">
                                    {{ $this->trans('btnAdd') }}
                            </button>
                            {{-- <x-ui-button click-event="Add" button-name="Tambah" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="add.svg" /> --}}
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
                                        <x-ui-text-field model="input_details.{{ $key }}.matl_descr" label='{{ $this->trans("description") }}' type="text" :action="$actionValue" placeHolder="{{ $this->trans("placeHolder_description") }}" enabled="false" span="Full" />
                                        <x-ui-text-field model="input_details.{{ $key }}.selling_price" label='{{ $this->trans("selling_price") }}' type="text" :action="$actionValue" placeHolder="{{ $this->trans("selling_price") }}" enabled="false" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.price" label='{{ $this->trans("price") }}' type="number" :onChanged="'changePrice('. $key .', $event.target.value)'" enabled="false" :action="$actionValue" required="true" placeHolder="" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.qty" label='{{ $this->trans("qty") }}' type="number" :onChanged="'changeQty('. $key .', $event.target.value)'" enabled="false" :action="$actionValue" required="true" placeHolder="" span="Half" />
                                        <x-ui-text-field model="input_details.{{ $key }}.amt" label='{{ $this->trans("amount") }}' type="text" :action="$actionValue" enabled="false" placeHolder="" span="Half" />
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
            {{-- @if ($actionValue === 'Edit')
            <x-ui-button :action="$actionValue" click-event="createReturn"
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
        window.livewire.on('closeMaterialDialog', function() {
            $('#MaterialDialogBox').modal('hide');
        });
    });

</script>

