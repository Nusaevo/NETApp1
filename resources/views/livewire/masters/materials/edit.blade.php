<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <div>
            <x-ui-button click-event="{{ route('materials.index') }}" type="Back" button-name="Back"/>
        </div>
    </div>

    <x-ui-page-card title="{{ $action }} Material" status="{{ $status }}">

        <x-ui-tab-view id="myTab" tabs="general,detail"> </x-ui-tab-view>

        <form wire:submit.prevent="{{ $action }}" class="form w-100">
            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-expandable-card id="UserCard" title="Material General Info" :isOpen="true">

                        <x-ui-text-field label="Name" model="inputs.name" type="text" :action="$action" required="true" placeHolder="Enter Name"/>
                        <x-ui-text-field label="Type Code" model="inputs.type_code" type="text" :action="$action" required="true" placeHolder="Enter Type Code" span="Full"/>
                        <x-ui-text-field label="Class Code" model="inputs.class_code" type="text" :action="$action" required="true" placeHolder="Enter Class Code" span="Full"/>
                        <x-ui-text-field label="Carat" model="inputs.jwl_carat" type="text" :action="$action" required="true" placeHolder="Carat" span="Half"/>
                        <x-ui-text-field label="Base Material" model="inputs.jwl_base_matl" type="text" :action="$action" required="true" placeHolder="Enter Base Material" span="Half"/>
                        <x-ui-text-field label="Base Material" model="inputs.jwl_base_matl" type="text" :action="$action" required="true" placeHolder="Enter Base Material" span="Half"/>
                        <x-ui-text-field label="Category" model="inputs.jwl_category" type="text" :action="$action" required="true" placeHolder="Enter Category" span="Half"/>
                        <x-ui-text-field label="Weight (g)" model="inputs.jwl_wgt_gold" type="number" :action="$action" required="true" placeHolder="Enter Weight (g)" span="Half"/>
                        <x-ui-text-field label="Supplier ID" model="inputs.jwl_supplier_id" type="number" :action="$action" required="false" placeHolder="Enter Supplier ID" span="Half"/>
                        <x-ui-text-field label="Sides Carat" model="inputs.jwl_sides_carat" type="number" :action="$action" required="false" placeHolder="Enter Sides Carat" span="Half"/>
                        <x-ui-text-field label="Sides Count" model="inputs.jwl_sides_cnt" type="number" :action="$action" required="false" placeHolder="Enter Sides Count" span="Half"/>
                        <x-ui-text-field label="Sides Material" model="inputs.jwl_sides_matl" type="text" :action="$action" required="false" placeHolder="Enter Sides Material" span="Half"/>
                        <x-ui-text-field label="Selling Price (USD)" model="inputs.jwl_selling_price_usd" type="number" :action="$action" required="false" placeHolder="Enter Selling Price (USD)" span="Half"/>
                        <x-ui-text-field label="Selling Price (IDR)" model="inputs.jwl_selling_price_idr" type="number" :action="$action" required="false" placeHolder="Enter Selling Price (IDR)" span="Half"/>
                        <x-ui-text-field label="Sides Calculation Method" model="inputs.jwl_sides_calc_method" type="text" :action="$action" required="false" placeHolder="Enter Sides Calculation Method" span="Half"/>
                        <x-ui-text-field label="Material Price" model="inputs.jwl_matl_price" type="number" :action="$action" required="false" placeHolder="Enter Material Price" span="Half"/>
                        <x-ui-text-field label="Selling Price" model="inputs.jwl_selling_price" type="number" :action="$action" required="false" placeHolder="Enter Selling Price" span="Half"/>
                        <x-ui-text-field label="Description" model="inputs.descr" type="textarea" :action="$action" required="false" placeHolder="Enter Description" span="Full"/>
                        <x-ui-text-field label="Class Code" model="inputs.class_code" type="text" :action="$action" required="true" placeHolder="Enter Class Code" span="Full"/>
                        <x-ui-text-field label="Class Code" model="inputs.class_code" type="text" :action="$action" required="true" placeHolder="Enter Class Code" span="Full"/>
                    </x-ui-expandable-card>
                </div>
                <div class="tab-pane fade" id="detail" role="tabpanel" aria-labelledby="detail-tab">
                    <x-ui-expandable-card id="detail" title="Material Detail" :isOpen="true">
                        <x-ui-text-field label="Base Material ID" model="inputs.base_matl_id" type="number" :action="$action" required="true" placeholder="Enter Base Material ID" />
                        <x-ui-text-field label="Sequence" model="inputs.seq" type="number" :action="$action" required="true" placeholder="Enter Sequence" />
                        <x-ui-text-field label="Sides Carat" model="inputs.jwl_sides_carat" type="number" :action="$action" required="false" placeholder="Enter Sides Carat" span="Half"/>
                        <x-ui-text-field label="Sides Count" model="inputs.jwl_sides_cnt" type="number" :action="$action" required="false" placeholder="Enter Sides Count" span="Half"/>
                        <x-ui-text-field label="Sides Material" model="inputs.jwl_sides_matl" type="text" :action="$action" required="false" placeholder="Enter Sides Material" span="Half"/>
                        <x-ui-text-field label="Sides Parcel" model="inputs.jwl_sides_parcel" type="text" :action="$action" required="false" placeholder="Enter Sides Parcel" span="Half"/>
                        <x-ui-text-field label="Sides Price" model="inputs.jwl_sides_price" type="number" :action="$action" required="false" placeholder="Enter Sides Price" span="Half"/>
                        <x-ui-text-field label="Sides Amount" model="inputs.jwl_sides_amt" type="number" :action="$action" required="false" placeholder="Enter Sides Amount" span="Half"/>

                    </x-ui-expandable-card>
                </div>
            </x-ui-tab-view-content>
        </form>
        <div class="card-footer d-flex justify-content-end">
            <div>
                <x-ui-button click-event="{{ $action }}" button-name="Save" loading="true" :action="$action" cssClass="btn-primary" iconPath="images/save-icon.png" />
            </div>
        </div>

    </x-ui-page-card>
</div>
