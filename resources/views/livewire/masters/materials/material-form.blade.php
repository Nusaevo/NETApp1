<x-ui-page-card title="{{ $actionValue }} Master Produk" status="{{ $status }}">
    <form wire:submit.prevent="{{ $actionValue }}" class="form w-100">
        <x-ui-tab-view id="materialTab" tabs="material"> </x-ui-tab-view>
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="material" role="tabpanel" aria-labelledby="material-tab" wire:ignore.self>
                <x-ui-expandable-card id="UserCard" title="Material General Info" :isOpen="true">
                    <div class="material-info-container">
                        <div class="photo-and-button-container">
                            <!-- Photo Container -->
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

                            <!-- Button Container -->
                            <div class="button-container">
                                <x-ui-button click-event="" id="cameraButton" cssClass="btn btn-secondary" iconPath="images/create-icon.png" button-name="Add from Camera" :action="$actionValue" />
                                <x-ui-button click-event="addFromGallery" cssClass="btn btn-secondary" iconPath="images/create-icon.png" button-name="Add from Gallery" :action="$actionValue" />
                            </div>
                        </div>
                    </div>
                    <div >
                        <x-ui-text-field label="Material Code" model="materials.code" type="code" :action="$actionValue" required="true" enabled="true" placeHolder="" span="Half" />
                        <x-ui-text-field label="Description" model="materials.descr" type="text" :action="$actionValue" required="true" enabled="false" placeHolder="Enter Description" span="Half" />
                        <x-ui-dropdown-select label="Category" click-event="" model="materials.jwl_category" :options="$materialCategories" :selectedValue="$materials['jwl_category']" required="true" :action="$actionValue" span="Half" />
                        <x-ui-dropdown-select label="UOM" click-event="" model="matl_uoms.name" :options="$materialUOMs" :selectedValue="$matl_uoms['name']" required="true" :action="$actionValue" span="Half" />
                        <x-ui-text-field label="Selling Price" model="materials.jwl_selling_price" type="number" :action="$actionValue" required="true" placeHolder="Enter Selling Price" span="Half" />
                        <x-ui-text-field label="Buying Price" model="materials.jwl_buying_price" type="number" :action="$actionValue" required="true" placeHolder="Enter Buying Price" span="Half" />
                    </div>

                    <div style="margin-top: 300px;">
                        <h2 class="mb-2 text-center">Side Materials</h2>

                        <x-ui-button click-event="addBoms" cssClass="btn btn-secondary" iconPath="images/create-icon.png" button-name="Add" :action="$actionValue" />

                        <div class="list-group" style="max-height: 500px; overflow-y: auto;" id="scroll-container">
                            @foreach($matl_boms_array as $key => $detail)
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h5 class="mb-1">No. {{$key+1}}</h5>
                                        <x-ui-dropdown-select label="Material" click-event="" model="matl_boms.{{ $key }}.base_matl_id" onChanged="generateSpecs(1)" :options="$baseMaterials" :selectedValue="$matl_boms[$key]['base_matl_id']" required="true" :action="$actionValue" span="Half" />
                                        <x-ui-text-field label="Quantity" model="matl_boms.{{ $key }}.jwl_sides_cnt" type="number" :action="$actionValue" required="false" placeHolder="Enter Quantity" span="Half" />
                                        <x-ui-text-field label="Carat" model="matl_boms.{{ $key }}.jwl_sides_carat" type="number" :action="$actionValue" required="false" placeHolder="Enter Sides Carat" span="Half" />
                                        <x-ui-text-field label="Price" model="matl_boms.{{ $key }}.jwl_sides_price" type="number" :action="$actionValue" required="false" placeHolder="Enter Sides Price" span="Half" />
                                    </div>
                                </div>
                                <!-- Updated delete button with rounded "X" -->
                                <div class="close-button">
                                    <a href="#" wire:click.prevent="deleteBoms({{ $key }})">
                                        X
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </x-ui-expandable-card>
            </div>
        </x-ui-tab-view-content>

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

</script>

<script>
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

