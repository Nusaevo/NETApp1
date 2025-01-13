<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
    <x-ui-page-card
        title="{{ $this->trans($actionValue) }} {!! $menuName !!} {{ $this->object->tr_id ? ' (Nota #' . $this->object->tr_id . ')' : '' }}"
        status="{{ $this->trans($status) }}">

        @if ($actionValue === 'Create')
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @else
            <x-ui-tab-view id="myTab" tabs="General"> </x-ui-tab-view>
        @endif
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="General" role="tabpanel" aria-labelledby="general-tab">
                <div class="row mt-4">
                    <div class="col-md-3">
                        <x-ui-card title="Order Info">
                            <x-ui-text-field label="Date" model="inputs.tr_date" type="date"
                                :action="$actionValue" required="true" :enabled="$isPanelEnabled" />
                            <x-ui-text-field-search type="int" label="Supplier" clickEvent=""
                                model="inputs.partner_id" :selectedValue="$inputs['partner_id']" :options="$suppliers" required="true"
                                :action="$actionValue"  :enabled="$isPanelEnabled" />

                            <x-ui-text-field label="Status" model="inputs.status_code_text" type="text"
                                    :action="$actionValue" required="false" enabled="false" />
                        </x-ui-card>

                        <x-ui-footer>
                            @if ($actionValue !== 'Create' && (!$object instanceof App\Models\SysConfig1\ConfigUser || auth()->user()->id !== $object->id))
                            @if(isset($permissions['delete']) && $permissions['delete'])
                            <div style="padding-right: 10px;">
                                @include('layout.customs.buttons.disable')
                            </div>
                            @endif

                            @endif
                            <div>
                                <x-ui-button clickEvent="Save" button-name="Save Header" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
                            </div>

                        </x-ui-footer>

                    </div>
                    <div class="col-md-9">
                        <x-ui-card title="Order Items">
                            @livewire($currentRoute.'.material-list-component', ['action' => $action,
                            'objectId' => $objectId])
                        </x-ui-card>
                    </div>
                </div>
            </div>
        </x-ui-tab-view-content>
        {{-- <x-ui-footer> --}}
        {{-- @if ($actionValue === 'Edit')
            <x-ui-button :action="$actionValue" clickEvent="createReturn"
                cssClass="btn-primary" loading="true" button-name="Create Purchase Return" iconPath="add.svg" />
            @endif --}}
        {{-- </x-ui-footer> --}}
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
