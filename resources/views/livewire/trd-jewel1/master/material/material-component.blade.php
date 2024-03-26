<x-ui-page-card title="{{ $actionValue }} Master Produk" status="{{ $status }}">
    {{--
    <x-ui-tab-view id="materialTabView" tabs="material"> </x-ui-tab-view> --}}

    <form wire:submit.prevent="{{ $actionValue }}" class="form w-100">
        {{-- <x-ui-tab-view-content id="materialTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="material" role="tabpanel" aria-labelledby="general-tab"> --}}
        <x-ui-expandable-card id="MaterialCard" title="Material General Info" :isOpen="true">
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
                        <x-ui-button click-event="" id="cameraButton" cssClass="btn btn-secondary" iconPath="images/create-icon.png" button-name="Add from Camera" :action="$actionValue" />
                        <x-ui-button click-event="addFromGallery" cssClass="btn btn-secondary" iconPath="images/create-icon.png" button-name="Add from Gallery" :action="$actionValue" />
                    </div>
                </div>
            </div>
            <div id="detail" style="padding-bottom: 200px;">
                <x-ui-text-field label="Material Code" model="materials.code" type="code" :action="$actionValue" required="true" enabled="true" placeHolder="" span="Half" />
                <x-ui-text-field label="Description" model="materials.descr" type="text" :action="$actionValue" required="true" enabled="false" placeHolder="Deskripsi dibuat otomatis dari side materials" span="Half" />
                <x-ui-dropdown-select label="Category" click-event="" model="materials.jwl_category" :options="$materialCategories" required="true" :action="$actionValue" span="Half" />
                <x-ui-dropdown-select label="UOM" click-event="" model="matl_uoms.name" :options="$materialUOMs"  required="true" :action="$actionValue" span="Half" />
                <x-ui-text-field label="Selling Price" model="materials.jwl_selling_price" type="number" :action="$actionValue" required="true" placeHolder="Enter Selling Price" span="Half" />
                <x-ui-text-field label="Buying Price" model="materials.jwl_buying_price" type="number" :action="$actionValue" required="true" placeHolder="Enter Buying Price" span="Half" />
            </div>
            <div>
                <x-ui-list-table id="Table" title="Side Materials">
                    <x-slot name="button">
                        <x-ui-button click-event="addBoms" cssClass="btn btn-success" iconPath="images/create-icon.png" button-name="Tambah" :action="$actionValue" />
                    </x-slot>
                    <x-slot name="body">
                        @foreach($matl_boms as $key => $matl_bom)
                        <tr wire:key="list{{ $key }}">
                            <x-ui-list-body>
                                <x-slot name="rows">
                                    <x-ui-dropdown-select label="Material" click-event="" model="matl_boms.{{ $key }}.base_matl_id" onChanged="generateSpecs(1)" :options="$baseMaterials" required="true" :action="$actionValue" span="Half" />
                                    <x-ui-text-field label="Quantity" model="matl_boms.{{ $key }}.jwl_sides_cnt" type="number" :action="$actionValue" required="true" placeHolder="Enter Quantity" span="Half" />
                                    <x-ui-text-field label="Carat" model="matl_boms.{{ $key }}.jwl_sides_carat" type="number" :action="$actionValue" required="true" placeHolder="Enter Sides Carat" span="Half" />
                                    <x-ui-text-field label="Price" model="matl_boms.{{ $key }}.jwl_sides_price" type="number" :action="$actionValue" required="true" placeHolder="Enter Sides Price" span="Half" />
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
            </div>

        </x-ui-expandable-card>
        {{-- </div>
        </x-ui-tab-view-content> --}}

        <div class="d-flex justify-content-end">
            <x-ui-text-field label="Barcode" model="matl_uoms.barcode" type="text" :action="$actionValue" required="true" placeHolder="Enter Barcode" span="Half" enabled="false" />
            <x-ui-button click-event="runExe" cssClass="btn btn-secondary" button-name="Scan Label" :action="$actionValue" />
            <x-ui-button click-event="printLabel" cssClass="btn btn-secondary" button-name="Print Label" :action="$actionValue" />
            <x-ui-button click-event="Save" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="images/save-icon.png" />
        </div>
    </form>

</x-ui-page-card>
<div id="cameraStream" style="display: none;"></div>

<script>
    function scrollToBottom() {
        var container = document.getElementById('scroll-container');
        container.scrollTop = container.scrollHeight;
    }

    document.addEventListener('livewire:load', function() {
        // Call scrollToBottom function when the page loads
        scrollToBottom();

        Livewire.on('itemAdded', function() {
            // Call scrollToBottom function when a new item is added
            scrollToBottom();
        });
    });
    document.addEventListener('DOMContentLoaded', function() {
        Webcam.set({
            width: 320
            , height: 240
            , dest_width: 640
            , dest_height: 480
            , image_format: 'jpeg'
            , jpeg_quality: 90
        , });

        Webcam.attach('#cameraStream');
        document.getElementById('cameraButton').addEventListener('click', function() {
            captureImageAndEmit();
        });
    });

    function captureImageAndEmit() {
        Webcam.snap(function(dataUri) {
            Livewire.emit('imagesCaptured', dataUri);
        });
    }

</script>

