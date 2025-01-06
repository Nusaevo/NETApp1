<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
    <x-ui-page-card title="{{ $this->trans($actionValue) }} {!! $menuName !!} {{ $this->object->tr_id ? ' (Nota #' . $this->object->tr_id . ')' : '' }}" status="{{ $this->trans($status) }}">

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
                            <x-ui-text-field label="{{ $this->trans('date') }}" model="inputs.tr_date" type="date" :action="$actionValue" required="true" onChanged="saveCheck" :enabled="$isPanelEnabled" />
                            <x-ui-text-field-search type="int" label="{{ $this->trans('partner') }}" clickEvent="" model="inputs.partner_id" :selectedValue="$inputs['partner_id']" :options="$suppliers" required="true" :action="$actionValue" onChanged="saveCheck" :enabled="$isPanelEnabled" />
                        </div>
                        {{-- <x-ui-dropdown-select label="{{ $this->trans('warehouse') }}" clickEvent="" model="inputs.wh_code" :options="$warehouses" required="true" :action="$actionValue" />
                        <x-ui-text-field label="Deliv by" model="inputs.deliv_by" type="text" :action="$actionValue" /> --}}
                        {{-- @if ($actionValue === 'Create')
                            <x-ui-checklist label="Buat Nota Terima Supplier otomatis" model="inputs.app_id" :options="['1' => 'Ya']" :action="$actionValue"  />
                        @endif --}}
                    </x-ui-padding>
                    @livewire('trd-retail1.procurement.purchase-order.material-list', ['input_details' => $input_details])
                </x-ui-card>
            </div>
        </x-ui-tab-view-content>
        <x-ui-footer>
            {{-- @if ($actionValue === 'Edit')
            <x-ui-button :action="$actionValue" clickEvent="createReturn"
                cssClass="btn-primary" loading="true" button-name="Create Purchase Return" iconPath="add.svg" />
            @endif --}}
            @include('layout.customs.transaction-form-footer')
        </x-ui-footer>
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

