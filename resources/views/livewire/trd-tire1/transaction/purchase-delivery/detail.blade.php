<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
    <x-ui-page-card
        title="{{ $this->trans($actionValue) }} {!! $menuName !!} {{ $this->object->tr_code ? ' (Nota #' . $this->object->tr_code . ')' : '' }}"
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
                                    <x-ui-text-field label="Tanggal Terima Barang" model="inputs.tr_date" type="date"
                                        :action="$actionValue" required="true" :enabled="$isPanelEnabled" />
                                    <x-ui-text-field label="{{ $this->trans('Nomor Surat Jalan') }}"
                                        model="inputs.delivery_number" type="text" :action="$actionValue" required="false"
                                        enabled="true" />
                                    <x-ui-text-field label="Tanggal Surat Jalan" model="inputs.tr_date" type="date"
                                        :action="$actionValue" required="true" :enabled="$isPanelEnabled" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field label="{{ $this->trans('note') }}" model="inputs.note"
                                        type="textarea" :action="$actionValue" required="false" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field-search label="{{ $this->trans('Nota Pembelian') }}"
                                        model="inputs.tr_code" type="text" :action="$actionValue" :options="$purchaseOrders"
                                        required="false" onChanged="onPurchaseOrderChanged" :selectedValue="$inputs['tr_code']" />
                                    <!-- Display Partner Name -->
                                    <x-ui-text-field label="{{ $this->trans('custommer') }}" model="inputs.partner_name"
                                        type="text" :action="$actionValue" required="false" readonly="true" />
                                    <!-- Hidden input for partner ID -->
                                    <input type="hidden" wire:model="inputs.partner_id">
                                    {{-- <x-ui-text-field-search type="int" label="{{ $this->trans('custommer') }}"
                                        clickEvent="" model="inputs.partner_id" :selectedValue="$inputs['partner_id']" :options="$partners"
                                        required="true" :action="$actionValue" :enabled="$isPanelEnabled" /> --}}
                                </div>
                            </x-ui-padding>
                        </x-ui-card>
                        {{-- </div>
                    <x-ui-footer>
                        <div>
                            <x-ui-button clickEvent="Save" button-name="Save Header" loading="true" :action="$actionValue"
                                cssClass="btn-primary" iconPath="save.svg" />
                        </div>
                    </x-ui-footer> --}}

                        {{-- <div class="col-md-12">
                        <x-ui-card title="Order Info">
                            <x-ui-text-field label="Date" model="inputs.tr_date" type="date" :action="$actionValue"
                                required="true" :enabled="$isPanelEnabled" />
                            <x-ui-text-field-search type="int" label="Supplier" clickEvent=""
                                model="inputs.partner_id" :selectedValue="$inputs['partner_id']" :options="$suppliers" required="true"
                                :action="$actionValue" :enabled="$isPanelEnabled" />
                            <x-ui-text-field label="Status" model="inputs.status_code_text" type="text"
                                :action="$actionValue" required="false" enabled="false" />
                        </x-ui-card>

                        <x-ui-footer>
                            @if ($actionValue !== 'Create' && (!$object instanceof App\Models\SysConfig1\ConfigUser || auth()->user()->id !== $object->id))
                                @if (isset($permissions['delete']) && $permissions['delete'])
                                    <div style="padding-right: 10px;">
                                        @include('layout.customs.buttons.disable')
                                    </div>
                                @endif

                            @endif
                            <div>
                                <x-ui-button clickEvent="Save" button-name="Save Header" loading="true"
                                    :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
                            </div>

                        </x-ui-footer>

                    </div> --}}
                    </div>
                    <br>
                    <div class="col-md-12">
                        <x-ui-card title="Order Items">
                            <div>
                                <x-ui-card>
                                    <div>
                                        <x-ui-list-table id="Table" title="Material List">
                                            <x-slot name="body">
                                                @foreach ($input_details as $key => $input_detail)
                                                    <tr wire:key="list{{ $input_detail['id'] ?? $key }}">
                                                        <x-ui-list-body>
                                                            {{-- <x-slot name="image">
                                                            <img src="{{ $input_detail['image_path'] ?? 'https://via.placeholder.com/300' }}"
                                                                alt="Material Photo" style="width: 200px; height: 200px;">
                                                        </x-slot> --}}
                                                            <x-slot name="rows">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <x-ui-text-field-search type="int"
                                                                            label='kode' clickEvent=""
                                                                            model="input_details.{{ $key }}.matl_id"
                                                                            :selectedValue="$input_details[$key]['matl_id']" :options="$materials"
                                                                            required="true" :action="$actionValue"
                                                                            onChanged="onMaterialChanged({{ $key }}, $event.target.value)"
                                                                            :enabled="true" />
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <x-ui-text-field
                                                                            model="input_details.{{ $key }}.qty"
                                                                            label="Quantity" enabled="true"
                                                                            class="form-control"
                                                                            model="input_details.{{ $key }}.qty"
                                                                            type="number" />
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <x-ui-text-field label="Quantity Belum Dikirim"
                                                                            model="input_details.{{ $key }}.qty2"
                                                                            required="false" enabled="true" />
                                                                    </div>
                                                                </div>
                                                            </x-slot>

                                                            <x-slot name="button">
                                                                <x-ui-link-text type="close" :clickEvent="'deleteItem(' . $key . ')'"
                                                                    class="btn btn-link" name="x" />
                                                            </x-slot>
                                                        </x-ui-list-body>
                                                    </tr>
                                                @endforeach
                                            </x-slot>
                                            <x-slot name="footerButton">
                                                <x-ui-button clickEvent="addItem" cssClass="btn btn-primary"
                                                    iconPath="add.svg" button-name="Add" />
                                            </x-slot>
                                        </x-ui-list-table>
                                    </div>
                                </x-ui-card>
                                <x-ui-footer>
                                    <div>
                                        <x-ui-button clickEvent="Save" button-name="Save" loading="true"
                                            :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
                                    </div>
                                </x-ui-footer>
                            </div>

                        </x-ui-card>
                    </div>
                </div>
        </x-ui-tab-view-content>
    </x-ui-page-card>
    {{-- @php
    dump($input_details);
    @endphp --}}


    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                window.addEventListener('openMaterialDialog', function() {
                    Livewire.dispatch('resetMaterial');
                    $('#materialDialogBox').modal('show');
                });

                window.addEventListener('closeMaterialDialog', function() {
                    $('#materialDialogBox').modal('hide');
                });
            });
        </script>
    @endpush

</div>
