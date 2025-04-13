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
                    <div class="col-md-12">
                        <x-ui-card title="Order Info">
                            <div class="row">
                                <x-ui-text-field label="Date" model="inputs.tr_date" type="date" :action="$actionValue"
                                    required="true" :enabled="$isPanelEnabled" />
                                <x-ui-text-field-search type="int" label="{{ $this->trans('partner') }}"
                                    clickEvent="" model="inputs.partner_id" :selectedValue="$inputs['partner_id']" :options="$partners"
                                    required="true" :action="$actionValue" :enabled="$isPanelEnabled" />
                                {{--
                                <x-ui-text-field type="text" label="Customer" model="inputs.partner_name"
                                    required="true" :action="$actionValue" enabled="false" clickEvent="openPartnerDialogBox"
                                    buttonName="Search" :buttonEnabled="$isPanelEnabled" />

                                <x-ui-dialog-box id="partnerDialogBox" title="Search Customer" width="600px"
                                    height="400px" onOpened="openPartnerDialogBox" onClosed="closePartnerDialogBox">
                                    <x-slot name="body">
                                        <x-ui-text-field type="text" label="Search Code/Nama Customer"
                                            model="partnerSearchText" required="true" :action="$actionValue" enabled="true"
                                            clickEvent="searchPartners" buttonName="Search" />
                                        <!-- Table -->
                                        <x-ui-table id="partnersTable" padding="0px" margin="0px">
                                            <x-slot name="headers">
                                                <th class="min-w-100px">Code</th>
                                                <th class="min-w-100px">Name</th>
                                                <th class="min-w-100px">Address</th>
                                            </x-slot>
                                            <x-slot name="rows">
                                                @if (empty($suppliers))
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">No Data Found
                                                        </td>
                                                    </tr>
                                                @else
                                                    @foreach ($suppliers as $key => $supplier)
                                                        <tr wire:key="row-{{ $key }}-supplier">
                                                            <td>
                                                                <x-ui-option label="" required="false"
                                                                    layout="horizontal" enabled="true" type="checkbox"
                                                                    visible="true" :options="[$supplier['id'] => $supplier['code']]"
                                                                    onChanged="selectPartner({{ $supplier['id'] }})" />
                                                            </td>
                                                            <td>{{ $supplier['name'] }}</td>
                                                            <td>{{ $supplier['address'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                @endif
                                            </x-slot>
                                            <x-slot name="footer">
                                                <x-ui-button clickEvent="confirmSelection"
                                                    button-name="Confirm Selection" loading="true" :action="$actionValue"
                                                    cssClass="btn-primary" />
                                            </x-slot>
                                        </x-ui-table>
                                    </x-slot>
                                </x-ui-dialog-box> --}}
                                <x-ui-text-field label="Status" model="inputs.status_code_text" type="text"
                                    :action="$actionValue" required="false" enabled="false" />
                            </div>
                        </x-ui-card>


                    </div>
                    <div class="col-md-12">
                        <x-ui-card title="Order Items">
                            @livewire($currentRoute . '.material-list-component', ['action' => $action, 'objectId' => $objectId])
                        </x-ui-card>
                    </div>
                    <div class="col-md-12">
                        <x-ui-card title="Return Items">
                            @livewire($currentRoute . '.return-list-component', ['action' => $action, 'objectId' => $objectId])
                        </x-ui-card>
                    </div>
                </div>
            </div>
        </x-ui-tab-view-content>

        <x-ui-footer>

            @include('layout.customs.transaction-form-footer')
            <div>
                @php
                    $printPdfRoute = preg_replace('/\.[^.]+$/', '.PrintPdf', $baseRoute);
                @endphp
                <x-ui-button :action="$actionValue"
                    clickEvent="{{ route($printPdfRoute, [
                        'action' => encryptWithSessionKey('Edit'),
                        'objectId' => encryptWithSessionKey($object->id),
                    ]) }}"
                    cssClass="btn-primary" type="Route" loading="true" button-name="Print" iconPath="print.svg" />
                <x-ui-button clickEvent="Save" button-name="Save" loading="true" :action="$actionValue"
                    cssClass="btn-primary" iconPath="save.svg" />
            </div>

        </x-ui-footer>
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

</div>
