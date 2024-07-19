@php
use App\Models\TrdJewel1\Master\Material;
@endphp

<x-ui-page-card title="{{ $actionValue }} {!! $menuName !!}" status="{{ $status }}">

    @if ($actionValue === 'Create')
    <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>
    @elseif(!$searchMode && $actionValue === 'Edit')
    <x-ui-tab-view id="myTab" tabs="general,transactions"> </x-ui-tab-view>
    @endif
    <x-ui-tab-view-content id="myTabContent" class="tab-content">
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
                                        <x-ui-link-text type="close" :clickEvent="'deleteImage(' . $key . ')'" class="btn btn-link" name="x" :action="$actionValue" />
                                    </div>
                                </div>
                                @empty
                                <div class="photo-box empty">
                                    <p>No Images Captured</p>
                                </div>
                                @endforelse
                            </div>

                            <div class="button-container">
                                <x-image-button :action="$actionValue"></x-image-button>

                                <x-ui-dialog-box id="storageDialogBox" :width="'2000px'" :height="'2000px'">
                                    <x-slot name="body">
                                        @livewire('trd-jewel1.master.gallery.storage-component', ['isDialogBoxComponent' => true])
                                    </x-slot>
                                </x-ui-dialog-box>
                            </div>
                        </div>
                    </div>
                </x-ui-padding>
                <x-ui-padding>
                    @if($searchMode)
                    <x-ui-text-field label="{{ $this->trans('search_product') }}" model="product_code" type="text" :action="$actionValue" enabled="true" placeHolder="" span="HalfWidth" enabled="true" clickEvent="searchProduct" />
                    @endif
                    <x-ui-dropdown-select label="{{ $this->trans('category1') }}" clickEvent="" model="materials.jwl_category1" :options="$materialCategories1" :enabled="$enableCategory1" required="true" :action="$actionValue" span="Half" onChanged="generateMaterialDescriptions" />
                    <x-ui-text-field label="{{ $this->trans('code') }}" model="materials.code" type="code" :action="$actionValue" required="true" enabled="true" placeHolder="" span="Half" />
                    <x-ui-dropdown-select label="{{ $this->trans('category2') }}" clickEvent="" model="materials.jwl_category2" :options="$materialCategories2" required="false" :action="$actionValue" span="Half" onChanged="generateMaterialDescriptions" />
                    <x-ui-text-field label="{{ $this->trans('buying_price') }}" model="materials.jwl_buying_price" type="number" :action="$actionValue" required="true" placeHolder="" span="Half" onChanged="markupPriceChanged" />
                    <x-ui-dropdown-select label="{{ $this->trans('purity') }}" clickEvent="" model="materials.jwl_carat" :options="$materialJewelPurity" required="true" :action="$actionValue" span="Half" />
                    <x-ui-text-field label="{{ $this->trans('markup_price') }}" model="materials.markup" type="number" :action="$actionValue" required="true" placeHolder="" span="Half" onChanged="markupPriceChanged" />
                    <x-ui-text-field label="{{ $this->trans('weight') }}" model="materials.jwl_wgt_gold" type="number" :action="$actionValue" required="true" enabled="true" placeHolder="" span="Half" onChanged="generateMaterialDescriptions" />
                    <x-ui-text-field label="{{ $this->trans('selling_price') }}" model="materials.jwl_selling_price" type="number" :action="$actionValue" required="true" placeHolder="" span="Half" onChanged="sellingPriceChanged" />
                    <x-ui-text-field label="{{ $this->trans('description') }}" model="materials.name" type="text" :action="$actionValue" required="true" enabled="false" placeHolder="{{ $this->trans('placeHolder_description') }}" span="Half" />
                    <x-ui-text-field label="{{ $this->trans('bom_description') }}" model="materials.descr" type="text" :action="$actionValue" required="true" enabled="false" placeHolder="{{ $this->trans('placeHolder_bom_description') }}" span="Half" />
                </x-ui-padding>

                <x-ui-padding>
                    <x-ui-list-table id="Table" title="{{ $this->trans('side_materials') }}">
                        <x-slot name="button">
                            <x-ui-button clickEvent="addBoms" cssClass="btn btn-primary" iconPath="add.svg" button-name="{{ $this->trans('btnAdd') }}" :action="$actionValue" />
                        </x-slot>
                        <x-slot name="body">
                            @foreach($matl_boms as $key => $matl_bom)
                            <tr wire:key="list{{ $key }}">
                                <x-ui-list-body>
                                    <x-slot name="rows">
                                        <x-ui-dropdown-select label="{{ $this->trans('material') }}" clickEvent="" model="matl_boms.{{ $key }}.base_matl_id" :options="$baseMaterials" required="true" :action="$actionValue" span="Half" :onChanged="'baseMaterialChange('. $key .', $event.target.value)'" />
                                        <x-ui-text-field label="{{ $this->trans('quantity') }}" model="matl_boms.{{ $key }}.jwl_sides_cnt" type="number" :action="$actionValue" required="true" placeHolder="" span="Half" onChanged="generateMaterialDescriptionsFromBOMs" />
                                        <x-ui-text-field label="{{ $this->trans('carat') }}" model="matl_boms.{{ $key }}.jwl_sides_carat" type="number" :action="$actionValue" required="true" placeHolder="" span="Half" onChanged="generateMaterialDescriptionsFromBOMs" />
                                        <x-ui-text-field label="{{ $this->trans('price') }}" model="matl_boms.{{ $key }}.jwl_sides_price" type="number" :action="$actionValue" required="true" placeHolder="" span="Half" />
                                        @isset($matl_bom['base_matl_id_note'])
                                        @switch($matl_bom['base_matl_id_note'])
                                        @case(Material::JEWELRY)
                                        <x-ui-dropdown-select label="{{ $this->trans('purity') }}" clickEvent="" model="matl_boms.{{ $key }}.purity" :options="$sideMaterialJewelPurity" required="false" :action="$actionValue" span="Full" />
                                        @break
                                        @case(Material::DIAMOND)
                                        <x-ui-dropdown-select label="{{ $this->trans('clarity') }}" clickEvent="" model="matl_boms.{{ $key }}.clarity" :options="$sideMaterialClarity" required="false" :action="$actionValue" span="Half" />
                                        <x-ui-dropdown-select label="{{ $this->trans('color') }}" clickEvent="" model="matl_boms.{{ $key }}.color" :options="$sideMaterialGiaColors" required="false" :action="$actionValue" span="Half" />
                                        <x-ui-dropdown-select label="{{ $this->trans('cut') }}" clickEvent="" model="matl_boms.{{ $key }}.cut" :options="$sideMaterialCut" required="false" :action="$actionValue" span="Half" />
                                        <x-ui-text-field label="{{ $this->trans('gia_number') }}" model="matl_boms.{{ $key }}.gia_number" type="number" :action="$actionValue" required="false" placeHolder="" span="Half" />
                                        @break
                                        @case(Material::GEMSTONE)
                                        <x-ui-dropdown-select label="{{ $this->trans('color') }}" clickEvent="" model="matl_boms.{{ $key }}.color" :options="$sideMaterialGemColors" required="false" :action="$actionValue" span="Half" />
                                        @break
                                        @case(Material::GOLD)
                                        <x-ui-text-field label="{{ $this->trans('production_year') }}" model="matl_boms.{{ $key }}.production_year" type="number" :action="$actionValue" required="false" placeHolder="" span="Half" />
                                        <x-ui-text-field label="{{ $this->trans('ref_mark') }}" model="matl_boms.{{ $key }}.ref_mark" type="text" :action="$actionValue" required="false" placeHolder="" span="Half" />
                                        @break
                                        @endswitch
                                        @endisset
                                    </x-slot>
                                    <x-slot name="button">
                                        <x-ui-link-text type="close" :clickEvent="'deleteBoms(' . $key . ')'" class="btn btn-link" name="x" :action="$actionValue" />
                                    </x-slot>
                                </x-ui-list-body>
                            </tr>
                            @endforeach
                        </x-slot>
                    </x-ui-list-table>
                </x-ui-padding>
            </x-ui-card>
        </div>
        @if (!$searchMode && $actionValue === 'Edit')
        <div class="tab-pane fade show" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
            <x-ui-card>
                @livewire('trd-jewel1.master.material.transaction-data-table', ['materialID' => $objectIdValue])
            </x-ui-card>
        </div>
        @endif
    </x-ui-tab-view-content>

    <x-ui-footer>
        <x-ui-text-field label="{{ $this->trans('barcode') }}" model="matl_uoms.barcode" type="text" :action="$actionValue" required="false" placeHolder="Enter Barcode" span="Half" enabled="false" />
        {{-- <x-ui-button clickEvent="runExe" cssClass="btn btn-secondary" button-name="Scan Label" :action="$actionValue" /> --}}

        @if (!$searchMode)
        @livewire('component.rfid-scanner', ['duration' => 1000, 'action' => $actionValue])
        @endif

        @if (!$searchMode && $actionValue == 'Edit')
        @if ($status === 'ACTIVE')
        <x-ui-button button-name="Disable" clickEvent="" loading="true" :action="$actionValue" cssClass="btn-danger btn-dialog-box" iconPath="disable.svg" />
        @else
        <x-ui-button button-name="Enable" clickEvent="" loading="true" :action="$actionValue" cssClass="btn-primary btn-dialog-box" iconPath="enable.svg" />
        @endif

        <x-ui-button clickEvent="printBarcode" cssClass="btn btn-secondary" button-name="Print Label" action="Edit" />
        @endif

        @if($searchMode)
        <x-ui-button clickEvent="SaveWithoutNotification" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
        @else
        <x-ui-button clickEvent="Save" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
        @endif

    </x-ui-footer>
</x-ui-page-card>

<script>
    document.addEventListener('livewire:load', function() {
        $(document).on('click', '.btn-dialog-box', function(e) {
            e.preventDefault();
            Swal.fire({
                title: "Apakah Anda Yakin ingin melanjutkannya?"
                , text: ""
                , icon: "question"
                , buttonsStyling: false
                , showConfirmButton: true
                , showCancelButton: true
                , confirmButtonText: "Yes"
                , cancelButtonText: "No"
                , closeOnConfirm: false
                , customClass: {
                    confirmButton: "btn btn-primary"
                    , cancelButton: 'btn btn-secondary'
                }
            }).then(confirm => {
                if (confirm.isConfirmed) {
                    Livewire.emit('changeStatus');
                }
            });
        });
    });

</script>

