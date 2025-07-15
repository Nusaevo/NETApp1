<div>
    <div>
        <x-ui-button clickEvent="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card isForm="true" title="{{ $this->trans($actionValue) }} {!! $menuName !!}" status="">
        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card>
                    <x-ui-text-field label="{{ $this->trans('date') }}" model="inputs.log_date" type="date" :action="$actionValue" required="true" span="Full" enabled="{{ $actionValue === 'Edit' ? 'false' : '' }}"/>
                    <div class="row">
                        <x-ui-text-field label="{{ $this->trans('currency_rate') }}" model="inputs.curr_rate" type="number" :action="$actionValue" required="true" placeHolder="USD to IDR" span="Half" onChanged="currencyChanged" />
                        <x-ui-dropdown-select label="{{ $this->trans('currency') }}" clickEvent="" model="inputs.curr_id" :options="$currencies" required="true" :action="$actionValue" enabled="false" span="Half" />
                    </div>
                    <div class="row">
                        <x-ui-text-field label="{{ $this->trans('gold_price_base') }}" model="inputs.goldprice_basecurr" type="number" :action="$actionValue" required="true" placeHolder="{{ $this->trans('placeholder_gold_price_base') }}" span="Half" onChanged="currencyChanged" />
                        <x-ui-text-field label="{{ $this->trans('gold_price_currency') }}" model="inputs.goldprice_curr" type="number" :action="$actionValue" required="true" placeHolder="{{ $this->trans('placeholder_gold_price_currency') }}" span="Half" enabled="false" currency="USD" decimalPlaces="2"/>
                    </div>
                </x-ui-card>
            </div>
        </x-ui-tab-view-content>
        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>

