<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>
    <x-ui-page-card isForm="true"
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
                                    <x-ui-text-field type="text" label="Customer" model="inputs.partner_name"
                                        required="true" :action="$actionValue" enabled="false"
                                        clickEvent="openPartnerDialogBox" buttonName="Search" :buttonEnabled="$isPanelEnabled" />
                                    <x-ui-dialog-box id="partnerDialogBox" title="Cari Customer" width="600px"
                                        height="400px" onOpened="openPartnerDialogBox" onClosed="closePartnerDialogBox">
                                        <x-slot name="body">
                                            <x-ui-text-field type="text" label="Cari Code/Nama Customer"
                                                model="partnerSearchText" required="true" :action="$actionValue"
                                                enabled="true" clickEvent="searchPartners" buttonName="Cari" />
                                            <!-- Table -->
                                            <x-ui-table id="partnersTable" padding="0px" margin="0px">
                                                <x-slot name="headers">
                                                    <th class="min-w-100px">Code</th>
                                                    <th class="min-w-100px">Name</th>
                                                    <th class="min-w-100px">Address</th>
                                                </x-slot>
                                                <x-slot name="rows">
                                                    @if (empty($Customers))
                                                        <tr>
                                                            <td colspan="4" class="text-center text-muted">No Data
                                                                Found</td>
                                                        </tr>
                                                    @else
                                                        @foreach ($Customers as $key => $Customer)
                                                            <tr wire:key="row-{{ $key }}-Customer">
                                                                <td>
                                                                    <x-ui-option label="" required="false"
                                                                        layout="horizontal" enabled="true"
                                                                        type="checkbox" visible="true" :options="[
                                                                            $Customer['id'] => $Customer['code'],
                                                                        ]"
                                                                        onChanged="selectPartner({{ $Customer['id'] }})" />
                                                                </td>
                                                                <td>{{ $Customer['name'] }}</td>
                                                                <td>{{ $Customer['address'] }}</td>
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
                                    </x-ui-dialog-box>
                                    <x-ui-text-field label="Tanggal Transaksi" model="inputs.tr_date" type="date"
                                        :action="$actionValue" required="true" :enabled="$isPanelEnabled" />
                                    <x-ui-text-field label="Nomor Transaksi" model="inputs.tr_code" :action="$actionValue"
                                        required="false" enabled="false" />
                                </div>
                            </x-ui-padding>
                        </x-ui-card>
                    </div>
                </div>
                <x-ui-footer>
                    <div>
                       @include('layout.customs.buttons.save')
                    </div>
                </x-ui-footer>
            </div>
            <br>
            <div class="col-md-12">
                <x-ui-card title="Nota">
                    @livewire($currentRoute . '.debt-list-component', ['action' => $action, 'objectId' => $objectId])
                </x-ui-card>
            </div>
            <div class="col-md-12">
                <x-ui-card title="Pembayaran">
                    @livewire($currentRoute . '.payment-list-component', ['action' => $action, 'objectId' => $objectId])
                </x-ui-card>
            </div>
        </x-ui-tab-view-content>
    </x-ui-page-card>
</div>
