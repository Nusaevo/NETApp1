<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <div>
            <x-ui-button click-event="{{ route('customers.index') }}" type="Back" button-name="Back"/>
        </div>
    </div>

    <x-ui-page-card title="{{ $action }} Customer" status="{{ $status }}">

        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>

        <form wire:submit.prevent="{{ $action }}" class="form w-100">
            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-expandable-card id="UserCard" title="Customer General Info" :isOpen="true">

                        <x-ui-text-field label="Customer Code" model="inputs.code" type="text" :action="$action" required="true" enabled="false" placeHolder="" />

                        <x-ui-text-field label="Nama" model="inputs.name" type="text" :action="$action" required="true" placeHolder="Enter Name" />

                        <x-ui-text-field label="Address" model="inputs.address" type="textarea" :action="$action" placeHolder="" />
                        <x-ui-text-field label="City" model="inputs.city" type="text" :action="$action" placeHolder="" />
                        <x-ui-text-field label="Country" model="inputs.country" type="text" action="$action" placeHolder="" />
                        <x-ui-text-field label="Postal Code" model="inputs.postal_code" type="text" :action="$action" placeHolder="" />
                        <x-ui-text-field label="Contact Person" model="inputs.contact_person" type="text" :action="$action" placeHolder="" />
                    </x-ui-expandable-card>
                </div>
            </x-ui-tab-view-content>
        </form>
        @include('layout.customs.form-footer')
    </x-ui-page-card>
</div>
