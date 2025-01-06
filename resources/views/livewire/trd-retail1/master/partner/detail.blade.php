
<div>
@php
use App\Models\TrdJewel1\Master\Partner;
@endphp
<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card title="{{ $this->trans($actionValue) }} {!! $menuName !!}" status="{{ $this->trans($status) }}">

        @if ($actionValue === 'Create')
        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>
        @elseif($actionValue !== 'Create')
        <x-ui-tab-view id="myTab" tabs="general,transactions"> </x-ui-tab-view>
        @endif
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card>
                    <div class="row">
                        <x-ui-text-field label="{{ $this->trans('partner_code') }}" model="inputs.code" type="code" :action="$actionValue" required="true" enabled="false" />
                        <x-ui-dropdown-select label="{{ $this->trans('partner_type') }}" clickEvent="" model="inputs.grp" :options="$partnerTypes" required="true" :action="$actionValue" />
                    </div>
                    <x-ui-text-field label="{{ $this->trans('name') }}" model="inputs.name" type="text" :action="$actionValue" required="true" placeHolder="Enter Name" />
                    <x-ui-text-field label="{{ $this->trans('address') }}" model="inputs.address" type="textarea" :action="$actionValue" />
                    <div class="row">
                        <x-ui-text-field label="{{ $this->trans('city') }}" model="inputs.city" type="text" :action="$actionValue" />
                        <x-ui-text-field label="{{ $this->trans('country') }}" model="inputs.country" type="text" :action="$actionValue" />
                        <x-ui-text-field label="{{ $this->trans('postal_code') }}" model="inputs.postal_code" type="text" :action="$actionValue" />
                    </div>
                    <div class="row">
                        <x-ui-text-field label="{{ $this->trans('contact_person') }}" model="inputs.contact_person" type="text" :action="$actionValue" span="HalfWidth" />
                        @if(in_array($inputs['grp'], [Partner::CUSTOMER]))
                        <x-ui-text-field label="{{ $this->trans('ring_size') }}" model="inputs.ring_size" type="text" :action="$actionValue" />
                        <x-ui-text-field label="{{ $this->trans('partner_ring_size') }}" model="inputs.partner_ring_size" type="text" :action="$actionValue" />
                        @endif </div>
                </x-ui-card>
            </div>
            <div class="tab-pane fade show" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
                <x-ui-card>
                    <div wire:ignore>
                        @livewire($currentRoute.'.transaction-data-table', [
                            'actionValue' => $actionValue,
                            'objectIdValue' => $objectIdValue
                            ])
                    </div>
                </x-ui-card>
            </div>
        </x-ui-tab-view-content>
        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
</div>
