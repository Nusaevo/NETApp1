<x-ui-page-card title="{{ $actionValue }} Master Produk" status="{{ $status }}">
    <form wire:submit.prevent="{{ $actionValue }}" class="form w-100">
        <x-ui-tab-view id="materialTab" tabs="material"> </x-ui-tab-view>
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="material" role="tabpanel" aria-labelledby="material-tab" wire:ignore.self>
                <x-ui-expandable-card id="UserCard" title="Material General Info" :isOpen="true">

                    <div class="material-info-container">
                        <div class="photo-and-button-container">
                            <!-- Photo Container -->
                            <div class="multiple-photo-container">
                                @forelse($capturedImages as $image)
                                        <img src="{{ $image }}" alt="Captured Image" class="photo-box">
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


                    <x-ui-text-field label="Material Code" model="materials.code" type="text" :action="$actionValue" required="true" enabled="false" placeHolder="" />
                    {{-- <x-ui-dropdown-select label="Category" model="materials.jwl_category" :options="$materialCategories" :selectedValue="$materials['jwl_category']" required="true" :action="$actionValue" span="Half" /> --}}
                    <x-ui-dropdown-select label="UOM" click-event="refreshUOMs" model="matl_uoms.name" :options="$materialUOMs" :selectedValue="$matl_uoms['name']" required="true" :action="$actionValue" span="Full" />
                    <x-ui-text-field label="Description" model="materials.descr" type="textarea" :action="$actionValue" required="true" enabled="false" placeHolder="Enter Description" span="Full" />

                    <x-ui-text-field label="Selling Price" model="materials.jwl_selling_price" type="number" :action="$actionValue" required="true" placeHolder="Enter Selling Price" span="Half" />
                    <x-ui-text-field label="Buying Price" model="materials.jwl_buying_price" type="number" :action="$actionValue" required="true" placeHolder="Enter Buying Price" span="Half" />

            </div>

            <div class="card-body p-2 mt-10">
                <h2 class="mb-2 text-center">Side Materials</h2>

                <x-ui-button click-event="addBoms" cssClass="btn btn-secondary" iconPath="images/create-icon.png" button-name="Add" :action="$actionValue" />

                <div class="list-group mt-5" style="max-height: 500px; overflow-y: auto;" id="scroll-container">
                    @foreach($matl_boms_array as $key => $detail)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="mb-1">No. {{$key+1}}</h5>
                                <x-ui-dropdown-select label="Category" click-event="" model="matl_boms.{{ $key }}.base_category_id" :options="$materialCategories" :selectedValue="$matl_boms[$key]['base_category_id']" required="true" :action="$actionValue" span="Half" />
                                <x-ui-dropdown-select label="Material" click-event="" model="matl_boms.{{ $key }}.base_matl_id" onChanged="generateSpecs(1)" :options="$baseMaterials" :selectedValue="$matl_boms[$key]['base_matl_id']" required="true" :action="$actionValue" span="Half" />
                                <x-ui-text-field label="Carat" model="matl_boms.{{ $key }}.jwl_sides_carat" type="text" :action="$actionValue" required="false" placeHolder="Enter Sides Carat" span="Half" />
                                <x-ui-text-field label="Count" model="matl_boms.{{ $key }}.jwl_sides_cnt" type="number" :action="$actionValue" required="false" placeHolder="Enter Sides Count" span="Half" />
                                <x-ui-text-field label="Parcel" model="matl_boms.{{ $key }}.jwl_sides_parcel" type="text" :action="$actionValue" required="false" placeHolder="Enter Sides Parcel" span="Half" />
                                <x-ui-text-field label="Price" model="matl_boms.{{ $key }}.jwl_sides_price" type="number" :action="$actionValue" required="false" placeHolder="Enter Sides Price" span="Half" />
                                {{-- <x-ui-text-field label="Document" model="" type="document" :action="$actionValue" required="false" placeHolder="Upload document" span="Full" /> --}}
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

        <div id="cameraContainer1" style="display: none;">
        </div>
        <div id="cameraContainer2" style="display: none;">
        </div>

    </form>

</x-ui-page-card>
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
  <script>
document.addEventListener('DOMContentLoaded', () => {
    const captureButton = document.querySelector('#cameraButton');
    const videoElement = document.getElementById('cameraStream');

    captureButton.addEventListener('click', async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            videoElement.srcObject = stream;
            videoElement.play();
            videoElement.style.display = 'block';
        } catch (err) {
            console.error('Error accessing the camera', err);
        }
    });

    document.getElementById('cameraButton').addEventListener('click', () => {
        const canvas = document.createElement('canvas');
        canvas.width = videoElement.videoWidth;
        canvas.height = videoElement.videoHeight;
        canvas.getContext('2d').drawImage(videoElement, 0, 0);
        const imageDataUrl = canvas.toDataURL('image/jpeg');

        // Emit the captured image to Livewire
        Livewire.emit('imagesCaptured', imageDataUrl);
    });
});
</script>

    </script>

