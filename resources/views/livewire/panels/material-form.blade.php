<x-ui-page-card title="{{ $actionValue }} Material" status="{{ $status }}">
    <form wire:submit.prevent="{{ $actionValue }}" class="form w-100">
        <x-ui-tab-view id="materialTab" tabs="material,detail"> </x-ui-tab-view>
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="material" role="tabpanel" aria-labelledby="material-tab" wire:ignore.self>
                <x-ui-expandable-card id="UserCard" title="Material General Info" :isOpen="true">

                    <div class="material-info-container">
                        <!-- Photo Container -->
                        <div class="photo-container">
                            <div class="photo-upload-box" onclick="document.getElementById('photo').click();">
                                <input type="file" wire:model="photo" id="photo" accept="image/*" capture="camera" style="display: none;">
                                <span wire:loading.remove></span>
                                <span wire:loading>
                                    <div class="loading-overlay">Loading...</div>
                                </span>
                                @if ($photo)
                                <div class="image-preview" wire:loading.remove wire:target="photo">
                                    <img src="{{ $photo->temporaryUrl() }}" alt="Material Photo">
                                </div>
                                @elseif($object && $object->attachments->first())
                                <div class="image-preview" wire:loading.remove wire:target="photo">
                                    <img src="{{ Storage::url($object->attachments->first()->path) }}" alt="Material Photo">
                                </div>
                                @else
                                <div class="image-placeholder" wire:loading.remove wire:target="photo">
                                    Click to Upload photo
                                </div>
                                @endif
                            </div>
                            <button type="button" onclick="viewFullscreen('preview')" class="fullscreen-btn btn btn-primary">View Fullscreen</button>
                        </div>

                        <div class="fields-container">
                            <x-ui-text-field label="Material Code" model="materials.code" type="text" :action="$actionValue" required="true" enabled="false" placeHolder="" />
                            {{-- <x-ui-text-field label="Name" model="materials.name" type="text" :action="$actionValue" required="true" placeHolder="Enter Name" />--}}
                            <x-ui-dropdown-select label="Category" click-event="refreshCategories" model="materials.jwl_category" :options="$materialCategories" :selectedValue="$materials['jwl_category']" required="true" :action="$actionValue" span="Half" />
                            <x-ui-dropdown-select label="UOM" click-event="refreshUOMs" model="matl_uoms.name" :options="$materialUOMs" :selectedValue="$matl_uoms['name']" required="true" :action="$actionValue" span="Half" />
                            <x-ui-text-field label="Barcode" model="matl_uoms.barcode" type="number" :action="$actionValue" required="true" placeHolder="Enter Barcode" span="Full" />
                            <x-ui-text-field label="Selling Price" model="materials.jwl_selling_price" type="number" :action="$actionValue" required="true" placeHolder="Enter Selling Price" span="Full" />
                            <x-ui-text-field label="Buying Price" model="materials.jwl_buying_price" type="number" :action="$actionValue" required="true" placeHolder="Enter Buying Price" span="Full" />
                            <x-ui-text-field label="Description" model="materials.descr" type="textarea" :action="$actionValue" required="true" placeHolder="Enter Description" span="Full" />
                        </div>
                    </div>
                </x-ui-expandable-card>
            </div>
            <div class="tab-pane fade" id="detail" role="tabpanel" aria-labelledby="detail-tab" wire:ignore.self>
                <x-ui-expandable-card id="detailCard" title="Material Detail" :isOpen="true">
                    <x-ui-dropdown-select label="Base Material" click-event="refreshBaseMaterials" model="matl_boms.base_matl_id" :options="$baseMaterials" :selectedValue="$matl_boms['base_matl_id']" required="true" :action="$actionValue" span="Full" />
                    <x-ui-text-field label="Sides Material" model="matl_boms.jwl_sides_matl" type="text" :action="$actionValue" required="false" placeHolder="Enter Sides Material" span="Half" />
                    <x-ui-text-field label="Sides Carat" model="matl_boms.jwl_sides_carat" type="text" :action="$actionValue" required="false" placeHolder="Enter Sides Carat" span="Half" />
                    <x-ui-text-field label="Sides Count" model="matl_boms.jwl_sides_cnt" type="number" :action="$actionValue" required="false" placeHolder="Enter Sides Count" span="Half" />
                    <x-ui-text-field label="Sides Parcel" model="matl_boms.jwl_sides_parcel" type="text" :action="$actionValue" required="false" placeHolder="Enter Sides Parcel" span="Half" />
                    <x-ui-text-field label="Sides Price" model="matl_boms.jwl_sides_price" type="number" :action="$actionValue" required="false" placeHolder="Enter Sides Price" span="Half" />
                    <x-ui-text-field label="Sides Amount" model="matl_boms.jwl_sides_amt" type="number" :action="$actionValue" required="false" placeHolder="Enter Sides Amount" span="Half" />
                    <x-ui-table id="DetailTable">
                        <x-slot name="title">
                            Detail
                        </x-slot>

                        <x-slot name="button">
                            <x-ui-button click-event="saveBoms" cssClass="btn btn-success" iconPath="images/create-icon.png" button-name="Simpan" :action="$actionValue" />
                        </x-slot>

                        <x-slot name="headers">
                            <th class="min-w-100px">Seq</th>
                            <th class="min-w-300px">Base Material</th>
                            <th class="min-w-300px">Sides Material</th>
                            <th class="min-w-15px">Action</th>
                        </x-slot>

                        <x-slot name="rows">
                            @foreach($matl_boms_array as $key => $detail)
                            <tr>
                                <td class="border">
                                    {{ $key + 1}}
                                </td>
                                <td class="border">
                                    {{ $detail['base_matl_name'] ?? "" }}
                                </td>
                                <td class="border">
                                    {{ $detail['jwl_sides_matl'] ?? "" }}
                                </td>
                                <td class="border">
                                    <x-ui-button button-name="Edit" click-event="editBoms({{ $key }})" :action="$actionValue" cssClass="btn btn-secondary" />
                                    <x-ui-button button-name="Delete" click-event="deleteBoms({{ $key }})" :action="$actionValue" cssClass="btn btn-danger" />
                                </td>
                            </tr>

                            @endforeach
                        </x-slot>
                    </x-ui-table>
                </x-ui-expandable-card>
            </div>


        </x-ui-tab-view-content>

        <div class="card-footer d-flex justify-content-end">
            <div>
                <x-ui-button click-event="{{ $actionValue }}" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="images/save-icon.png" />
            </div>
        </div>

    </form>
</x-ui-page-card>

<script>
    function viewFullscreen() {
        var image = document.querySelector('.image-preview img'); // Select the image inside the .image-preview
        if (image) {
            // Create a modal for fullscreen view
            var modal = document.createElement('div');
            modal.style.display = 'flex';
            modal.style.alignItems = 'center';
            modal.style.justifyContent = 'center';
            modal.style.position = 'fixed';
            modal.style.top = 0;
            modal.style.left = 0;
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
            modal.style.zIndex = 10000;
            modal.onclick = function() {
                document.body.removeChild(modal);
            };

            var img = new Image();
            img.src = image.src; // Set the src for the fullscreen image
            img.style.maxWidth = '90%';
            img.style.maxHeight = '90%';
            img.style.objectFit = 'contain';

            modal.appendChild(img);
            document.body.appendChild(modal);
        }
    }
</script>

