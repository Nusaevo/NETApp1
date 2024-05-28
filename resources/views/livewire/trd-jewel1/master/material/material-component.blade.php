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
                        {{-- <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#cameraModal" data-bs-dismiss="modal">
                            <span style="font-size: 16px;">   {{ $this->trans('btnCamera') }}</span>
                        </button> --}}
                        <x-ui-button clickEvent="" id="btnCamera" cssClass="btn btn-secondary" iconPath="add.svg" button-name="{{ $this->trans('btnCamera') }}" :action="$actionValue" />
                            <!-- Hidden File Input -->
                         <input type="file" id="imageInput" accept="image/*" style="display: none;" multiple onchange="handleFileUpload(event)">
                        <x-ui-button clickEvent="addFromGallery" cssClass="btn btn-secondary" iconPath="add.svg" button-name="{{ $this->trans('btnGallery') }}" :action="$actionValue" />
                    </div>
                </div>
            </div>
        </x-ui-padding>

        <x-ui-padding>
            @if($searchMode)
                <x-ui-text-field label="{{ $this->trans('search_product') }}" model="product_code" type="text" :action="$actionValue" enabled="true" placeHolder="" span="HalfWidth" enabled="true" clickEvent="searchProduct" />
            @endif
            <x-ui-dropdown-select label="{{ $this->trans('category1') }}" clickEvent="" model="materials.jwl_category1" :options="$materialCategories1" :enabled="$enableCategory1" required="true" :action="$actionValue" span="Half" onChanged="generateMaterialDescriptions" />
            <x-ui-text-field label="{{ $this->trans('code') }}" model="materials.code" type="code" :action="$actionValue" required="true" enabled="true" placeHolder="" span="Half" enabled="true" />
            <x-ui-dropdown-select label="{{ $this->trans('category2') }}" clickEvent="" model="materials.jwl_category2" :options="$materialCategories2" required="false" :action="$actionValue" span="Half" onChanged="generateMaterialDescriptions" />
            <x-ui-text-field label="{{ $this->trans('buying_price') }}" model="materials.jwl_buying_price" type="number" :action="$actionValue" required="true" placeHolder="" span="Half" onChanged="markupPriceChanged" />
            <x-ui-dropdown-select label="{{ $this->trans('purity') }}" clickEvent="" model="materials.jwl_carat" :options="$materialJewelPurity" required="false" :action="$actionValue" span="Half" />
            <x-ui-text-field label="{{ $this->trans('markup_price') }}" model="materials.markup" type="number" :action="$actionValue" required="true" placeHolder="" span="Half" onChanged="markupPriceChanged" />
            <x-ui-text-field label="{{ $this->trans('weight') }}" model="materials.jwl_wgt_gold" type="number" :action="$actionValue" required="true" enabled="true" placeHolder="" span="Half" onChanged="generateMaterialDescriptions" />
            <x-ui-text-field label="{{ $this->trans('selling_price') }}" model="materials.jwl_selling_price" type="number" :action="$actionValue" required="true" placeHolder="" span="Half" onChanged="sellingPriceChanged" />
            <x-ui-text-field label="{{ $this->trans('description') }}" model="materials.name" type="text" :action="$actionValue" required="true" enabled="false" placeHolder="{{ $this->trans('placeHolder_description') }}" span="Half" />
            <x-ui-text-field label="{{ $this->trans('bom_description') }}" model="materials.descr" type="text" :action="$actionValue" required="true" enabled="false" placeHolder="{{ $this->trans('placeHolder_bom_description') }}" span="Half" />

            {{-- <x-ui-dropdown-select label="UOM" clickEvent="" model="matl_uoms.name" :options="$materialUOMs" required="true" :action="$actionValue" span="Half" /> --}}
        </x-ui-padding>
        <x-ui-padding>
            <x-ui-list-table id="Table" title="Side Materials">
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
                                <x-ui-text-field label="{{ $this->trans('price') }}" model="matl_boms.{{ $key }}.jwl_sides_price" type="number" :action="$actionValue" required="false" placeHolder="" span="Half" />

                                @isset($matl_bom['base_matl_id_note'])
                                @switch($matl_bom['base_matl_id_note'])
                                @case(Material::JEWELRY)
                                <x-ui-dropdown-select label="{{ $this->trans('purity') }}" clickEvent="" model="matl_boms.{{ $key }}.purity" :options="$sideMaterialJewelPurity" required="false" :action="$actionValue" span="Full" />
                                @break
                                @case(Material::DIAMOND)
                                {{-- <x-ui-dropdown-select label="{{ $this->trans('shapes') }}" clickEvent="" model="matl_boms.{{ $key }}.shapes" :options="$sideMaterialShapes" required="false" :action="$actionValue" span="Half" /> --}}
                                <x-ui-dropdown-select label="{{ $this->trans('clarity') }}" clickEvent="" model="matl_boms.{{ $key }}.clarity" :options="$sideMaterialClarity" required="false" :action="$actionValue" span="Half" />
                                <x-ui-dropdown-select label="{{ $this->trans('color') }}" clickEvent="" model="matl_boms.{{ $key }}.color" :options="$sideMaterialGiaColors" required="false" :action="$actionValue" span="Half" />
                                <x-ui-dropdown-select label="{{ $this->trans('cut') }}" clickEvent="" model="matl_boms.{{ $key }}.cut" :options="$sideMaterialCut" required="false" :action="$actionValue" span="Half" />
                                <x-ui-text-field label="{{ $this->trans('gia_number') }}" model="matl_boms.{{ $key }}.gia_number" type="number" :action="$actionValue" required="false" placeHolder="" span="Half" />
                                @break
                                @case(Material::GEMSTONE)
                                {{-- <x-ui-dropdown-select label="{{ $this->trans('gemstone') }}" clickEvent="" model="matl_boms.{{ $key }}.gemstone" :options="$sideMaterialGemStone" required="false" :action="$actionValue" span="Half" /> --}}
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
        <x-ui-button clickEvent="runExe" cssClass="btn btn-secondary" button-name="Scan Label" :action="$actionValue" />
        <x-ui-button clickEvent="printBarcode" cssClass="btn btn-secondary" button-name="Print Label" :action="$actionValue" />
        @if($searchMode)
            <x-ui-button clickEvent="SaveWithoutNotification" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
        @else
            <x-ui-button clickEvent="Save" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
        @endif
    </x-ui-footer>
</x-ui-page-card>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('btnCamera').addEventListener('click', triggerFileInput);
    });

    function triggerFileInput() {
        document.getElementById('imageInput').click();
    }

    function handleFileUpload(event) {
        const files = event.target.files;
        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                Livewire.emit('imagesCaptured', e.target.result);
            };
            reader.readAsDataURL(file);
        });
    }
</script>
{{-- <x-ui-dialog-box id="cameraModal" :width="'2000px'" :height="'2000px'">
    <x-slot name="body">
        <div class="form-group">
            <label for="cameraSelect1">Camera 1</label>
            <select id="cameraSelect1" class="form-control"></select>
        </div>
        <div class="form-group">
            <label for="cameraSelect2">Camera 2</label>
            <select id="cameraSelect2" class="form-control"></select>
        </div>
        <div class="camera-container">
            <div class="camera-box">
                <video id="cameraStream1" style="width: 100%; height: auto; margin-top: 10px;" autoplay></video>
            </div>
            <div class="camera-box">
                <video id="cameraStream2" style="width: 100%; height: auto; margin-top: 10px;" autoplay></video>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer">
        <button type="button" id="captureButton" class="btn btn-primary">Capture</button>
    </x-slot>
</x-ui-dialog-box> --}}
{{--
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cameraButton = document.querySelector('[data-bs-target="#cameraModal"]');
        const captureButton = document.getElementById('captureButton');
        const cameraSelect1 = document.getElementById('cameraSelect1');
        const cameraSelect2 = document.getElementById('cameraSelect2');
        const cameraStream1 = document.getElementById('cameraStream1');
        const cameraStream2 = document.getElementById('cameraStream2');
        let currentStream1 = null;
        let currentStream2 = null;

        // Function to start the camera stream
        function startCameraStream(deviceId, cameraStream, currentStream) {
            const constraints = {
                video: {
                    deviceId: { exact: deviceId },
                    width: 320,
                    height: 240
                }
            };

            // Stop any existing stream
            if (currentStream) {
                currentStream.getTracks().forEach(track => track.stop());
            }

            // Start a new stream
            navigator.mediaDevices.getUserMedia(constraints)
                .then(stream => {
                    cameraStream.srcObject = stream;
                    if (cameraStream === cameraStream1) {
                        currentStream1 = stream;
                    } else {
                        currentStream2 = stream;
                    }
                })
                .catch(error => {
                    console.error('Error accessing media devices.', error);
                });
        }

        // Show camera dialog
        cameraButton.addEventListener('click', function() {
            $('#cameraModal').modal('show');
            // Get list of video input devices
            navigator.mediaDevices.enumerateDevices().then(devices => {
                cameraSelect1.innerHTML = '';
                cameraSelect2.innerHTML = '';
                const videoDevices = devices.filter(device => device.kind === 'videoinput');
                if (videoDevices.length === 0) {
                    const option = document.createElement('option');
                    option.text = 'No camera devices found';
                    cameraSelect1.appendChild(option);
                    cameraSelect2.appendChild(option);
                } else {
                    let droidCamCount = 0;
                    videoDevices.forEach((device, index) => {
                        const option = document.createElement('option');
                        option.value = device.deviceId;
                        option.text = device.label || `Camera ${index + 1}`;
                        cameraSelect1.appendChild(option.cloneNode(true));
                        cameraSelect2.appendChild(option);

                        // Prioritize DroidCam devices
                        if (device.label.includes('DroidCam')) {
                            if (droidCamCount === 0) {
                                cameraSelect1.value = device.deviceId;
                                startCameraStream(device.deviceId, cameraStream1, currentStream1);
                            } else if (droidCamCount === 1) {
                                cameraSelect2.value = device.deviceId;
                                startCameraStream(device.deviceId, cameraStream2, currentStream2);
                            }
                            droidCamCount++;
                        }
                    });

                    // If no DroidCam is found, start the first available cameras
                    if (droidCamCount === 0) {
                        if (videoDevices.length > 0) {
                            startCameraStream(videoDevices[0].deviceId, cameraStream1, currentStream1);
                        }
                        if (videoDevices.length > 1) {
                            startCameraStream(videoDevices[1].deviceId, cameraStream2, currentStream2);
                        }
                    }
                }
            }).catch(error => {
                console.error('Error accessing media devices.', error);
                const option = document.createElement('option');
                option.text = 'Error accessing media devices';
                cameraSelect1.appendChild(option);
                cameraSelect2.appendChild(option);
            });
        });

        // Start the camera stream when a new camera is selected
        cameraSelect1.addEventListener('change', function() {
            if (cameraSelect1.value !== '') {
                startCameraStream(cameraSelect1.value, cameraStream1, currentStream1);
            }
        });

        cameraSelect2.addEventListener('change', function() {
            if (cameraSelect2.value !== '') {
                startCameraStream(cameraSelect2.value, cameraStream2, currentStream2);
            }
        });

        // Capture image from the current streams
        captureButton.addEventListener('click', function() {
            if (cameraSelect1.value === '' || !currentStream1 || cameraSelect2.value === '' || !currentStream2) {
                alert('Please select both cameras first.');
                return;
            }

            // Capture image from the first stream
            const canvas1 = document.createElement('canvas');
            canvas1.width = 640;
            canvas1.height = 480;
            const context1 = canvas1.getContext('2d');
            context1.drawImage(cameraStream1, 0, 0, canvas1.width, canvas1.height);
            const dataUri1 = canvas1.toDataURL('image/jpeg', 0.9);

            // Capture image from the second stream
            const canvas2 = document.createElement('canvas');
            canvas2.width = 640;
            canvas2.height = 480;
            const context2 = canvas2.getContext('2d');
            context2.drawImage(cameraStream2, 0, 0, canvas2.width, canvas2.height);
            const dataUri2 = canvas2.toDataURL('image/jpeg', 0.9);

            // Emit both images to Livewire
            Livewire.emit('imagesCaptured', dataUri1);
            Livewire.emit('imagesCaptured', dataUri2);
        });

        // Stop the camera streams when the modal is closed
        $('#cameraModal').on('hidden.bs.modal', function() {
            if (currentStream1) {
                currentStream1.getTracks().forEach(track => track.stop());
                currentStream1 = null;
            }
            if (currentStream2) {
                currentStream2.getTracks().forEach(track => track.stop());
                currentStream2 = null;
            }
        });
    });
</script> --}}
