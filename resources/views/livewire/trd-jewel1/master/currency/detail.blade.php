<div>
    <div>
        <x-ui-button click-event="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card title="{{ $this->trans($actionValue) }} {{ $this->trans('currency') }}" status="">
        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <x-ui-card>
                    <x-ui-text-field label="{{ $this->trans('date') }}" model="inputs.log_date" type="date" :action="$actionValue" required="true" span="Full" />
                    <x-ui-dropdown-select label="{{ $this->trans('currency') }}" click-event="" model="inputs.curr_id" :options="$currencies" required="true" :action="$actionValue" enabled="false" />
                    <x-ui-text-field label="{{ $this->trans('currency_rate') }}" model="inputs.curr_rate" type="number" :action="$actionValue" required="true" placeHolder="USD to IDR" />
                    <x-ui-text-field label="{{ $this->trans('gold_price_currency') }}" model="inputs.goldprice_curr" type="number" :action="$actionValue" required="true" placeHolder="in USD" />
                    <x-ui-text-field label="{{ $this->trans('gold_price_base') }}" model="inputs.goldprice_basecurr" type="number" :action="$actionValue" required="true" placeHolder="in IDR" />
                </x-ui-card>
            </div>
        </x-ui-tab-view-content>
        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
