<div>
  @php
    use App\Models\SrvInsur1\Master\Partner;
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
              <x-ui-dropdown-select label="{{ $this->trans('partner_type') }}" clickEvent="" model="inputs.grp"
                :options="$partnerTypes" required="true" :action="$actionValue" />
              <x-ui-text-field label="{{ $this->trans('partner_code') }}" model="inputs.code" type="code"
                :action="$actionValue" required="true" enabled="false" />
            </div>
            {{-- @if (in_array($inputs['grp'], [Partner::WAJIB PAJAK])) --}}
              <div class="row">
                <x-ui-text-field label="{{ $this->trans('customer') }}" model="inputs.customer"
                  type="text" :action="$actionValue" />
                <x-ui-text-field label="{{ $this->trans('npwp_nik') }}" model="inputs.npwp_nik" type="text"
                  :action="$actionValue" />
              </div>
            {{-- @endif --}}
            <div class="row">
             <x-ui-text-field label="{{ $this->trans('name') }}" model="inputs.name" type="text" :action="$actionValue"
              required="true" placeHolder="Enter Name" />
            </div>
            <div class="row">
            <x-ui-text-field label="{{ $this->trans('address') }}" model="inputs.address" type="textarea"
              :action="$actionValue" required="true" />
            </div>
            <div class="row">
              <x-ui-text-field label="{{ $this->trans('country') }}" model="inputs.country" type="text"
                :action="$actionValue" />
              <x-ui-text-field label="{{ $this->trans('state') }}" model="inputs.state" type="text" :action="$actionValue"
                required="true" />
              <x-ui-text-field label="{{ $this->trans('city') }}" model="inputs.city" type="text" :action="$actionValue"
                required="true" />
              <x-ui-text-field label="{{ $this->trans('postal_code') }}" model="inputs.postal_code" type="text"
                :action="$actionValue" />
            </div>
            <div class="row">
              <x-ui-text-field label="{{ $this->trans('contact_person') }}" model="inputs.contact_person"
                type="text" :action="$actionValue" />
               <x-ui-text-field label="{{ $this->trans('phone') }}" model="inputs.phone" type="phone"
                :action="$actionValue" />
              <x-ui-text-field label="{{ $this->trans('email') }}" model="inputs.email" type="email"
                :action="$actionValue" />
            </div>
            <div class="row">
              <x-ui-text-field label="{{ $this->trans('bank_name') }}" model="inputs.bank_name" type="text"
                :action="$actionValue" />
              <x-ui-text-field label="{{ $this->trans('bank_account') }}" model="inputs.bank_account" type="text"
                :action="$actionValue" />
            </div>
          </x-ui-card>
        </div>
        <div class="tab-pane fade show" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
          <x-ui-card>
            <div wire:ignore>
              {{-- @livewire('trd-retail2.master.partner.transaction-data-table', ['partnerID' => $objectIdValue]) --}}
            </div>
          </x-ui-card>
        </div>
      </x-ui-tab-view-content>
      @include('layout.customs.form-footer')
    </x-ui-page-card>
  </div>
</div>
