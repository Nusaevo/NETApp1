<div>
    @php
        use App\Models\TrdTire1\Master\Partner;
    @endphp

    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />

        <x-ui-page-card title="{{ $this->trans($actionValue) }} {!! $menuName !!}"
            status="{{ $this->trans($status) }}">
            @if ($actionValue === 'Create')
                <x-ui-tab-view id="myTab" tabs="general"></x-ui-tab-view>
            @else
                <x-ui-tab-view id="myTab" tabs="general,transactions"></x-ui-tab-view>
            @endif

            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <div class="row mt-4">
                        <!-- Main Form Section -->
                        <div class="col-md-8">
                            <x-ui-card title="Main Information">
                                <x-ui-padding>
                                    <div class="row">
                                        <x-ui-dropdown-select label="{{ $this->trans('category') }}" model="inputs.grp"
                                            :options="$PartnerType" required="true" :action="$actionValue" />
                                        <x-ui-text-field label="{{ $this->trans('partner_code') }}" model="inputs.code"
                                            type="text" :action="$actionValue" enabled="false" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('name') }}" model="inputs.name"
                                            type="text" :action="$actionValue" required="true" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('address') }}" model="inputs.address"
                                            type="textarea" required="true" :action="$actionValue" />
                                    </div>
                                </x-ui-padding>
                            </x-ui-card>

                            <x-ui-card title="Detail Information">
                                <x-ui-padding>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('country') }}" model="inputs.country"
                                            type="text" :action="$actionValue" required="true" />
                                        <x-ui-text-field label="{{ $this->trans('province') }}" model="inputs.province"
                                            type="text" :action="$actionValue" required="true" />
                                        <x-ui-text-field label="{{ $this->trans('city') }}" model="inputs.city"
                                            type="text" :action="$actionValue" required="true" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('postal_code') }}"
                                            model="inputs.postal_code" type="text" :action="$actionValue" />
                                        <x-ui-text-field label="{{ $this->trans('nib') }}" model="inputs.nib"
                                            type="text" :action="$actionValue" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('phone') }}" model="inputs.phone"
                                            type="text" :action="$actionValue" />
                                        <x-ui-text-field label="{{ $this->trans('email') }}" model="inputs.email"
                                            type="text" :action="$actionValue" />
                                    </div>
                                </x-ui-padding>
                            </x-ui-card>
                            {{-- <x-ui-card title="NPWP">
                                <x-ui-padding>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('NPWP/NIK') }}" model="inputs.country"
                                            type="text" :action="$actionValue" required="false" />
                                        <x-ui-text-field label="{{ $this->trans('Nama WP') }}" model="inputs.country"
                                            type="text" :action="$actionValue" required="false" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('Alamat WP') }}" model="inputs.country"
                                            type="text" :action="$actionValue" required="false" />
                                    </div>
                                </x-ui-padding>
                            </x-ui-card> --}}
                            {{-- <x-ui-card title="Kontak">
                                <x-ui-padding>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('Nama Kontak') }}"
                                            model="inputs.country" type="text" :action="$actionValue" required="false" />
                                        <x-ui-text-field label="{{ $this->trans('Jabatan') }}" model="inputs.country"
                                            type="text" :action="$actionValue" required="false" />
                                        <x-ui-text-field label="{{ $this->trans('Tanggal Lahir') }}"
                                            model="inputs.country" type="text" :action="$actionValue" required="false" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('Phone 1') }}" model="inputs.country"
                                            type="text" :action="$actionValue" required="false" />
                                        <x-ui-text-field label="{{ $this->trans('Phone 2') }}" model="inputs.country"
                                            type="text" :action="$actionValue" required="false" />
                                        <x-ui-text-field label="{{ $this->trans('Email') }}" model="inputs.country"
                                            type="text" :action="$actionValue" required="false" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('Alamat Kontak') }}"
                                            model="inputs.country" type="text" :action="$actionValue"
                                            required="false" />
                                        <x-ui-text-field label="{{ $this->trans('Catatan Kontak') }}"
                                            model="inputs.country" type="text" :action="$actionValue"
                                            required="false" />
                                    </div>
                                </x-ui-padding>
                            </x-ui-card> --}}
                        </div>

                        <!-- Right Section -->
                        <div class="col-md-4">
                            <x-ui-card title="Point">
                                <x-ui-option label="Multiple Options Checklist" model="inputs.partner_chars"
                                    :options="['IRC' => 'Poin IRC', 'GT' => 'Poin GT', 'ZN' => 'Poin ZN']"
                                    required="false" layout="horizontal" enabled="true" type="checkbox"
                                    visible="true" />
                            </x-ui-card>
                            <x-ui-card title="Description">
                                <x-ui-padding>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('note_partner') }}"
                                            model="inputs.note" type="textarea" :action="$actionValue" />
                                    </div>
                                </x-ui-padding>
                            </x-ui-card>
                            {{-- <x-ui-card title="Bank">
                                <x-ui-padding>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('Bank Account') }}"
                                            model="inputs.point_irc" type="text" :action="$actionValue" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('Nama Bank') }}"
                                            model="inputs.point_gt" type="text" :action="$actionValue" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('Lokasi') }}" model="inputs.point_zn"
                                            type="text" :action="$actionValue" />
                                    </div>
                                </x-ui-padding>
                            </x-ui-card> --}}
                        </div>
                    </div>
                </div>

                @if ($actionValue !== 'Create')
                    <div class="tab-pane fade show" id="transactions" role="tabpanel"
                        aria-labelledby="transactions-tab">
                        <x-ui-card>
                            <div wire:ignore>
                                @livewire($currentRoute . '.transaction-data-table', [
                                    'actionValue' => $actionValue,
                                    'objectIdValue' => $objectIdValue,
                                ])
                            </div>
                        </x-ui-card>
                    </div>
                @endif
            </x-ui-tab-view-content>

            {{-- <div class="d-flex justify-content-start align-items-center">
                <x-ui-button clickEvent="Save" button-name="+ NPWP" loading="true" cssClass="btn-primary" />
                <x-ui-button clickEvent="Save" button-name="+ Kontak" loading="true" cssClass="btn-primary" />
                <x-ui-button clickEvent="Save" button-name="+ Bank" loading="true" cssClass="btn-primary" />
                <x-ui-footer :actionValue="$actionValue" :permissions="$permissions" />
            </div> --}}
            @include('layout.customs.form-footer')
        </x-ui-page-card>
    </div>
</div>
