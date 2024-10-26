<div>
@php
use App\Models\TrdRetail1\Master\Material;
@endphp

<x-ui-page-card title="{{ $actionValue }} {!! $menuName !!}" status="{{ $status }}">

    @if ($actionValue === 'Create')
    <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>
    @elseif(!$searchMode && $actionValue !== 'Create')
    <x-ui-tab-view id="myTab" tabs="general,transactions"> </x-ui-tab-view>
    @endif
    <x-ui-tab-view-content id="tabMaterial" class="tab-content">
        <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
            <x-ui-card>
                <x-ui-padding>
                    <div class="material-info-container">
                        <div class="photo-and-button-container">
                            <!-- Photo Container -->
                            <div class="multiple-photo-container">
                                @forelse($capturedImages as $key => $image)
                                <div class="photo-box">
                                    <img src="{{ $image['url'] }}" alt="Captured Image" class="photo-box-image">
                                    <div class="image-close-button">
                                        <x-ui-link-text type="close" :clickEvent="'deleteImage(' . $key . ')'" class="btn btn-link" name="x" :action="$customActionValue" />
                                    </div>
                                </div>
                                @empty
                                <div class="photo-box empty">
                                    <p>No Images Captured</p>
                                </div>
                                @endforelse
                            </div>

                            <div class="button-container">
                                <x-ui-image-button :action="$customActionValue" :hideStorageButton="true"></x-ui-image-button>

                                <x-ui-dialog-box id="storageDialogBox" :width="'2000px'" :height="'2000px'">
                                    <x-slot name="body">
                                        @livewire('trd-jewel1.master.gallery.storage-component', [ 'isDialogBoxComponent' => true])
                                    </x-slot>
                                </x-ui-dialog-box>
                            </div>
                        </div>
                    </div>
                </x-ui-padding>
                <x-ui-padding>

                    <div class="row">
                        <x-ui-dropdown-select label="{{ $this->trans('category1') }}" clickEvent="" model="materials.jwl_category1" :options="$materialCategories1" :enabled="$panelEnabled" required="true" :action="$actionValue" onChanged="onCategory1Changed" />
                        <x-ui-text-field label="{{ $this->trans('code') }}" model="materials.code" type="code" :action="$actionValue" required="true"  :enabled="$panelEnabled" clickEvent="getMatlCode"  buttonName="Get Code"/>
                    </div>
                    <div class="row">
                        <x-ui-dropdown-select label="{{ $this->trans('category2') }}" clickEvent="" model="materials.jwl_category2" :options="$materialCategories2" required="false" :action="$customActionValue" />

                        {{--  <x-ui-dropdown-select label="{{ $this->trans('category2') }}" clickEvent="" model="materials.jwl_category2" :options="$materialCategories2" required="false" :action="$customActionValue" onChanged="generateMaterialDescriptions" />  --}}
                        @if($orderedMaterial)
                        <x-ui-text-field label="{{ $this->trans('buying_price_idr') }}" model="materials.jwl_buying_price_idr" type="number" :action="$actionValue" required="true" onChanged="markupPriceChanged" enabled="false" />
                        @else
                        <x-ui-text-field label="{{ $this->trans('buying_price_usd') }}" model="materials.jwl_buying_price_usd" type="number" :action="$actionValue" required="true" onChanged="markupPriceChanged" />
                        @endif
                    </div>
                    <div class="row">
                        <x-ui-dropdown-select label="{{ $this->trans('purity') }}" clickEvent="" model="materials.jwl_carat" :options="$materialJewelPurity" required="true" :action="$customActionValue" />
                        <x-ui-text-field label="{{ $this->trans('markup_price') }}" model="materials.markup" type="number" :action="$customActionValue" required="true" onChanged="markupPriceChanged"
                            :enabled="$orderedMaterial ? 'false' : 'true'" />
                    </div>
                    <div class="row">
                        {{--  <x-ui-text-field label="{{ $this->trans('weight') }}" model="materials.jwl_wgt_gold" type="number" :action="$customActionValue" required="true" enabled="true" onChanged="generateMaterialDescriptions" />  --}}
                        <x-ui-text-field label="{{ $this->trans('weight') }}" model="materials.jwl_wgt_gold" type="number" :action="$customActionValue" required="true" enabled="true" />

                        @if($orderedMaterial)
                            <x-ui-text-field label="{{ $this->trans('selling_price_idr') }}" model="materials.jwl_selling_price_idr" type="number" :action="$customActionValue" required="true" onChanged="sellingPriceChanged" />
                        @else
                            <x-ui-text-field label="{{ $this->trans('selling_price_usd') }}" model="materials.jwl_selling_price_usd" type="number" :action="$customActionValue" required="true" onChanged="sellingPriceChanged" />
                        @endif
                    </div>
                    @if($orderedMaterial)
                    <div class="row">
                        <x-ui-text-field label="{{ $this->trans('gold_price') }}" model="materials.gold_price" type="number" :action="$customActionValue" />
                        <x-ui-text-field label="{{ $this->trans('jwl_cost') }}" model="materials.jwl_cost" type="number" :action="$customActionValue" />
                    </div>
                    @endif
                    <div class="row">
                        <x-ui-text-field label="{{ $this->trans('description') }} " model="materials.name" type="text" :action="$actionValue" required="false" enabled="false" />
                        <x-ui-text-field label="{{ $this->trans('bom_description') }} " model="materials.descr" type="text" :action="$actionValue" required="false" enabled="false" />
                    </div>
                    <div class="row">
                        <x-ui-text-field label="{{ $this->trans('remark') }} " model="materials.remark" type="textarea" :action="$customActionValue"/>
                    </div>
                </x-ui-padding>
            </x-ui-card>
        </div>
        @if (!$searchMode && $actionValue !== 'Create')
        <div class="tab-pane fade show" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
            <x-ui-card>
                <div wire:ignore>
                @livewire('trd-jewel1.master.material.transaction-data-table', ['materialID' => $objectIdValue])
                </div>
            </x-ui-card>
        </div>
        @endif
    </x-ui-tab-view-content>

    <x-ui-footer>
        <div class="row">
            <x-ui-text-field label="{{ $this->trans('barcode') }}" model="matl_uoms.barcode" type="text" :action="$actionValue" required="false" placeHolder="Enter Barcode" enabled="false" />
        </div>
        {{-- <x-ui-button clickEvent="runExe" cssClass="btn btn-secondary" button-name="Scan Label" :action="$actionValue" /> --}}

        {{--@if (!$searchMode)--}}
        @livewire('component.rfid-scanner', ['duration' => 1000, 'action' => "$customActionValue"])
        {{-- @endif--}}

        @if (!$searchMode && $actionValue == 'Edit')
        @include('layout.customs.buttons.disable')
        @endif

        <x-ui-button clickEvent="printBarcode" cssClass="btn btn-primary" button-name="Print Label" :action="$customActionValue" />
        <x-ui-button clickEvent="Save" button-name="Save" loading="true" :action="$customActionValue" cssClass="btn-primary" iconPath="save.svg" />

        @if($searchMode)
        <x-ui-button clickEvent="addPurchaseOrder" button-name="Add Item" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="add.svg" />
        @endif

    </x-ui-footer>
</x-ui-page-card>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.addEventListener('openStorageDialog', function() {
            $('#storageDialogBox').modal('show');
        });

        window.addEventListener('closeStorageDialog', function() {
            $('#storageDialogBox').modal('hide');
        });
    });

</script>
@endpush
