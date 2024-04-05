<x-ui-page-card title="{{ $actionValue }} Master Produk" status="{{ $status }}">
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
                            <x-ui-button click-event="" id="cameraButton" cssClass="btn btn-secondary" iconPath="add.svg" button-name="Add from Camera" :action="$actionValue" />
                            <x-ui-button click-event="addFromGallery" cssClass="btn btn-secondary" iconPath="add.svg" button-name="Add from Gallery" :action="$actionValue" />
                        </div>
                    </div>
                </div>
                <x-ui-text-field label="Material Code" model="materials.code" type="code" :action="$actionValue" required="true" enabled="true" placeHolder="" span="Half" />
                <x-ui-text-field label="Description" model="materials.descr" type="text" :action="$actionValue" required="true" enabled="false" placeHolder="Deskripsi dibuat otomatis dari side materials" span="Half" />
                <x-ui-dropdown-select label="Category" click-event="" model="materials.jwl_category" :options="$materialCategories" required="true" :action="$actionValue" span="Half" />
                <x-ui-dropdown-select label="UOM" click-event="" model="matl_uoms.name" :options="$materialUOMs" required="true" :action="$actionValue" span="Half" />
                <x-ui-text-field label="Selling Price" model="materials.jwl_selling_price" type="number" :action="$actionValue" required="true" placeHolder="Enter Selling Price" span="Half" />
                <x-ui-text-field label="Buying Price" model="materials.jwl_buying_price" type="number" :action="$actionValue" required="true" placeHolder="Enter Buying Price" span="Half" />
            </x-ui-padding>
            <x-ui-padding>
            <x-ui-list-table id="Table" title="Side Materials">
                <x-slot name="button">
                    <x-ui-button click-event="addBoms" cssClass="btn btn-primary" iconPath="add.svg" button-name="Tambah" :action="$actionValue" />
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
            </x-ui-padding>
        </x-ui-card>
        <x-ui-footer>
            <x-ui-text-field label="Barcode" model="matl_uoms.barcode" type="text" :action="$actionValue" required="true" placeHolder="Enter Barcode" span="Half" enabled="false" />
            <x-ui-button click-event="runExe" cssClass="btn btn-secondary" button-name="Scan Label" :action="$actionValue" />
            <x-ui-button click-event="printLabel" cssClass="btn btn-secondary" button-name="Print Label" :action="$actionValue" />
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
        // Show loader while initializing the webcam
        var loaderContainer = document.getElementById('loader-container');
        loaderContainer.style.display = 'block';

        Webcam.set({
            width: 320,
            height: 240,
            dest_width: 640,
            dest_height: 480,
            image_format: 'jpeg',
            jpeg_quality: 90,
        });

        Webcam.attach('#cameraStream');

        // Hide loader once the webcam is initialized
        Webcam.on('live', function() {
            loaderContainer.style.display = 'none';
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

