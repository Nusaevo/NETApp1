@php
use App\Models\TrdJewel1\Master\Material;
@endphp
<x-ui-page-card title="{{ $actionValue }} {{ $this->trans('product') }}" status="{{ $status }}">
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
                                <a href="#" wire:click.prevent="deleteImage({{ $key }})">
                                    X
                                </a>
                            </div>
                        </div>
                        @empty
                        <div class="photo-box empty">
                            <p>No Images Captured</p>
                        </div>
                        @endforelse
                    </div>

                    <div class="button-container">
                        <x-ui-button click-event="" id="cameraButton" cssClass="btn btn-secondary" iconPath="add.svg" button-name="{{ $this->trans('btnCamera') }}" :action="$actionValue" />
                        <x-ui-button click-event="addFromGallery" cssClass="btn btn-secondary" iconPath="add.svg" button-name="{{ $this->trans('btnGallery') }}" :action="$actionValue" />
                    </div>
                </div>
            </div>
        </x-ui-padding>
        <x-ui-padding>
            <x-ui-dropdown-select label="{{ $this->trans('category1') }}" click-event="" model="materials.jwl_category1" :options="$materialCategories1" required="true" :action="$actionValue" span="Half" onChanged="generateMaterialDescriptions" />
            <x-ui-dropdown-select label="{{ $this->trans('category2') }}" click-event="" model="materials.jwl_category2" :options="$materialCategories2" required="false" :action="$actionValue" span="Half" onChanged="generateMaterialDescriptions" />
            <x-ui-text-field label="{{ $this->trans('code') }}" model="materials.code" type="code" :action="$actionValue" required="true" enabled="true" placeHolder="" span="Half" enabled="false" />
            <x-ui-text-field label="{{ $this->trans('buying_price') }}" model="materials.jwl_buying_price" type="number" :action="$actionValue" required="true" placeHolder="" span="Half" onChanged="markupPriceChanged" />
            <x-ui-text-field label="{{ $this->trans('markup_price') }}" model="materials.markup" type="number" :action="$actionValue" required="true" placeHolder="" span="Half" onChanged="markupPriceChanged" />
            <x-ui-text-field label="{{ $this->trans('selling_price') }}" model="materials.jwl_selling_price" type="number" :action="$actionValue" required="true" placeHolder="" span="Half" onChanged="sellingPriceChanged" />
            <x-ui-text-field label="{{ $this->trans('weight') }}" model="materials.jwl_wgt_gold" type="number" :action="$actionValue" required="true" enabled="true" placeHolder="" span="Half" onChanged="generateMaterialDescriptions" />
            <x-ui-dropdown-select label="{{ $this->trans('purity') }}" click-event="" model="materials.jwl_carat" :options="$sideMaterialJewelPurity" required="false" :action="$actionValue" span="Half" />
            <x-ui-text-field label="{{ $this->trans('description') }}" model="materials.name" type="text" :action="$actionValue" required="true" enabled="false" placeHolder="{{ $this->trans('placeHolder_description') }}" span="Full" />
            <x-ui-text-field label="{{ $this->trans('bom_description') }}" model="materials.descr" type="text" :action="$actionValue" required="true" enabled="false" placeHolder="{{ $this->trans('placeHolder_bom_description') }}" span="Full" />

            {{-- <x-ui-dropdown-select label="UOM" click-event="" model="matl_uoms.name" :options="$materialUOMs" required="true" :action="$actionValue" span="Half" /> --}}
        </x-ui-padding>
        <x-ui-padding>
            <x-ui-list-table id="Table" title="Side Materials">
                <x-slot name="button">
                    <x-ui-button click-event="addBoms" cssClass="btn btn-primary" iconPath="add.svg" button-name="{{ $this->trans('btnAdd') }}" :action="$actionValue" />
                </x-slot>
                <x-slot name="body">
                    @foreach($matl_boms as $key => $matl_bom)
                    <tr wire:key="list{{ $key }}">
                        <x-ui-list-body>
                            <x-slot name="rows">
                                <x-ui-dropdown-select label="{{ $this->trans('material') }}" click-event="" model="matl_boms.{{ $key }}.base_matl_id" :options="$baseMaterials" required="true" :action="$actionValue" span="Half" :onChanged="'baseMaterialChange('. $key .', $event.target.value)'" />
                                <x-ui-text-field label="{{ $this->trans('quantity') }}" model="matl_boms.{{ $key }}.jwl_sides_cnt" type="number" :action="$actionValue" required="true" placeHolder="" span="Half" onChanged="generateMaterialDescriptionsFromBOMs" />
                                <x-ui-text-field label="{{ $this->trans('carat') }}" model="matl_boms.{{ $key }}.jwl_sides_carat" type="number" :action="$actionValue" required="true" placeHolder="" span="Half" onChanged="generateMaterialDescriptionsFromBOMs" />
                                <x-ui-text-field label="{{ $this->trans('price') }}" model="matl_boms.{{ $key }}.jwl_sides_price" type="number" :action="$actionValue" required="false" placeHolder="" span="Half" />

                                @isset($matl_bom['base_matl_id_note'])
                                @switch($matl_bom['base_matl_id_note'])
                                @case(Material::JEWELRY)
                                <x-ui-dropdown-select label="{{ $this->trans('purity') }}" click-event="" model="matl_boms.{{ $key }}.purity" :options="$sideMaterialJewelPurity" required="false" :action="$actionValue" span="Full" />
                                @break
                                @case(Material::DIAMOND)
                                {{-- <x-ui-dropdown-select label="{{ $this->trans('shapes') }}" click-event="" model="matl_boms.{{ $key }}.shapes" :options="$sideMaterialShapes" required="false" :action="$actionValue" span="Half" /> --}}
                                <x-ui-dropdown-select label="{{ $this->trans('clarity') }}" click-event="" model="matl_boms.{{ $key }}.clarity" :options="$sideMaterialClarity" required="false" :action="$actionValue" span="Half" />
                                <x-ui-dropdown-select label="{{ $this->trans('color') }}" click-event="" model="matl_boms.{{ $key }}.color" :options="$sideMaterialGiaColors" required="false" :action="$actionValue" span="Half" />
                                <x-ui-dropdown-select label="{{ $this->trans('cut') }}" click-event="" model="matl_boms.{{ $key }}.cut" :options="$sideMaterialCut" required="false" :action="$actionValue" span="Half" />
                                <x-ui-text-field label="{{ $this->trans('gia_number') }}" model="matl_boms.{{ $key }}.gia_number" type="number" :action="$actionValue" required="false" placeHolder="" span="Half" />
                                @break
                                @case(Material::GEMSTONE)
                                {{-- <x-ui-dropdown-select label="{{ $this->trans('gemstone') }}" click-event="" model="matl_boms.{{ $key }}.gemstone" :options="$sideMaterialGemStone" required="false" :action="$actionValue" span="Half" /> --}}
                                <x-ui-dropdown-select label="{{ $this->trans('color') }}" click-event="" model="matl_boms.{{ $key }}.color" :options="$sideMaterialGemColors" required="false" :action="$actionValue" span="Half" />
                                @break
                                @case(Material::GOLD)
                                <x-ui-text-field label="{{ $this->trans('production_year') }}" model="matl_boms.{{ $key }}.production_year" type="number" :action="$actionValue" required="false" placeHolder="" span="Half" />
                                <x-ui-text-field label="{{ $this->trans('ref_mark') }}" model="matl_boms.{{ $key }}.ref_mark" type="text" :action="$actionValue" required="false" placeHolder="" span="Half" />
                                @break
                                @endswitch
                                @endisset

                            </x-slot>
                            <x-slot name="button">
                                <a href="#" wire:click.prevent="deleteBoms({{ $key }})">
                                    X
                                </a>
                            </x-slot>
                        </x-ui-list-body>
                    </tr>
                    @endforeach
                </x-slot>
            </x-ui-list-table>
        </x-ui-padding>
    </x-ui-card>
    <x-ui-footer>
        <x-ui-text-field label="{{ $this->trans('barcode') }}" model="matl_uoms.barcode" type="text" :action="$actionValue" required="true" placeHolder="Enter Barcode" span="Half" enabled="false" />
        <x-ui-button click-event="runExe" cssClass="btn btn-secondary" button-name="Scan Label" :action="$actionValue" />
        <x-ui-button click-event="printBarcode" cssClass="btn btn-secondary" button-name="Print Label" :action="$actionValue" />
        <x-ui-button click-event="Save" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
    </x-ui-footer>
</x-ui-page-card>
<div id="cameraStream" style="display: none;"></div>

<script>
    // function scrollToBottom() {
    //     var container = document.getElementById('scroll-container');
    //     container.scrollTop = container.scrollHeight;
    // }

    // document.addEventListener('livewire:load', function() {
    //     // Call scrollToBottom function when the page loads
    //     scrollToBottom();

    //     Livewire.on('itemAdded', function() {
    //         // Call scrollToBottom function when a new item is added
    //         scrollToBottom();
    //     });
    // });
    // document.addEventListener('DOMContentLoaded', function() {
    //     Webcam.set({
    //         width: 320
    //         , height: 240
    //         , dest_width: 640
    //         , dest_height: 480
    //         , image_format: 'jpeg'
    //         , jpeg_quality: 90
    //     , });

    //     Webcam.attach('#cameraStream');
    //     document.getElementById('cameraButton').addEventListener('click', function() {
    //         captureImageAndEmit();
    //     });
    // });

    function captureImageAndEmit() {
        var cameraStream = document.getElementById('cameraStream');
        if (!cameraStream) {
            console.error('Camera stream DOM element not found');
            return;
        }

        var loaderContainer = document.getElementById('loader-container');
        if (loaderContainer) {
            loaderContainer.style.display = 'block';
        }

        Webcam.set({
            width: 320
            , height: 240
            , dest_width: 640
            , dest_height: 480
            , image_format: 'jpeg'
            , jpeg_quality: 90
        });

        Webcam.attach('#cameraStream');

        Webcam.on('live', function() {
            if (loaderContainer) {
                loaderContainer.style.display = 'none';
            }
        });

        Webcam.snap(function(dataUri) {
            Livewire.emit('imagesCaptured', dataUri);
            Webcam.reset();
        });
    }


    document.getElementById('cameraButton').addEventListener('click', function() {
        captureImageAndEmit();
    });

</script>

