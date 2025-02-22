<div>
    @php
        use App\Models\TrdTire1\Master\Partner;
    @endphp

    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />

        <x-ui-page-card
            title="{{ $this->trans($actionValue) }} {!! $menuName !!} {{ $this->object->code ? ' (' . $this->object->code . ' - ' . $this->object->name . ')' : '' }}"
            status="{{ $this->trans($status) }}">
            @if ($actionValue === 'Create')
                <x-ui-tab-view id="myTab" tabs="general,contact,bank,npwp,alamat_kirim"></x-ui-tab-view>
            @else
                <x-ui-tab-view id="myTab" tabs="general,contact,bank,npwp,alamat_kirim,transactions"></x-ui-tab-view>
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
                                            :options="$PartnerType" required="true" :action="$actionValue"
                                            onChanged="onGrpChanged($event.target.value)" />
                                        <x-ui-text-field label="{{ $this->trans('partner_code') }}" model="inputs.code"
                                            type="text" :action="$actionValue" enabled="false" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('name') }}" model="inputs.name"
                                            type="text" :action="$actionValue" required="true" capslockMode="true" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('address') }}" model="inputs.address"
                                            type="textarea" required="true" :action="$actionValue" capslockMode="true" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('country') }}" model="inputs.country"
                                            type="text" :action="$actionValue" required="true" capslockMode="true" />
                                        <x-ui-text-field label="{{ $this->trans('province') }}" model="inputs.province"
                                            type="text" :action="$actionValue" required="true" capslockMode="true" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('city') }}" model="inputs.city"
                                            type="text" :action="$actionValue" required="true" capslockMode="true" />
                                        <x-ui-text-field label="{{ $this->trans('postal_code') }}"
                                            model="inputs.postal_code" type="text" :action="$actionValue" />
                                    </div>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('nib') }}" model="inputs.nib"
                                            type="text" :action="$actionValue" />
                                        <x-ui-text-field label="{{ $this->trans('phone') }}" model="inputs.phone"
                                            type="text" :action="$actionValue" />
                                        <x-ui-text-field label="{{ $this->trans('email') }}" model="inputs.email"
                                            type="text" :action="$actionValue" />
                                    </div>
                                </x-ui-padding>
                            </x-ui-card>
                        </div>

                        <!-- Right Section -->
                        <div class="col-md-4">
                            <x-ui-card title="Point">
                                <x-ui-option label="Multiple Options Checklist" model="inputs.partner_chars"
                                    :options="['IRC' => 'Poin IRC', 'GT' => 'Poin GT', 'ZN' => 'Poin ZN']" required="false" layout="horizontal" enabled="true"
                                    type="checkbox" visible="true" enabled="true" />
                            </x-ui-card>
                            <x-ui-card title="Credit">
                                <x-ui-padding>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('amt_limit') }}"
                                            model="inputs.amt_limit" type="number" :action="$actionValue" />
                                    </div>
                                </x-ui-padding>
                            </x-ui-card>
                            <x-ui-card title="Description">
                                <x-ui-padding>
                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('note_partner') }}" model="inputs.note"
                                            type="textarea" :action="$actionValue" />
                                    </div>
                                </x-ui-padding>
                            </x-ui-card>
                        </div>
                        <x-ui-footer>
                            @if (
                                $actionValue !== 'Create' &&
                                    (!$object instanceof App\Models\SysConfig1\ConfigUser || auth()->user()->id !== $object->id))
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

                    </div>
                </div>
                <div class="tab-pane fade show" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                    @livewire($currentRoute . '.contact-list-component', ['action' => $action, 'objectId' => $objectId])
                </div>
                <div class="tab-pane fade show" id="npwp" role="tabpanel" aria-labelledby="npwp-tab">
                    @livewire($currentRoute . '.npwp-list-component', ['action' => $action, 'objectId' => $objectId])
                </div>
                <div class="tab-pane fade show" id="bank" role="tabpanel" aria-labelledby="bank-tab">
                    @livewire($currentRoute . '.bank-list-component', ['action' => $action, 'objectId' => $objectId])
                </div>
                <div class="tab-pane fade show" id="alamat_kirim" role="tabpanel"
                    aria-labelledby="alamat_kirim-tab">
                    @livewire($currentRoute . '.shipping-address-component', ['action' => $action, 'objectId' => $objectId])
                </div>
                @if ($actionValue !== 'Create')
                    <div class="tab-pane fade show" id="transactions" role="tabpanel"
                        aria-labelledby="transactions-tab">
                        <x-ui-card>
                            {{-- <div wire:ignore>
                                @livewire($currentRoute . '.transaction-data-table', [
                                    'actionValue' => $actionValue,
                                    'objectIdValue' => $objectIdValue,
                                ])
                            </div> --}}
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
        </x-ui-page-card>
    </div>
</div>
