<x-ui-page-card title="{{ $actionValue }} Master Produk" status="{{ $status }}">
    <form wire:submit.prevent="{{ $actionValue }}" class="form w-100">
        <x-ui-tab-view id="materialTab" tabs="material"> </x-ui-tab-view>
        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="material" role="tabpanel" aria-labelledby="material-tab" wire:ignore.self>
                <x-ui-expandable-card id="UserCard" title="Material General Info" :isOpen="true">

                    <div class="material-info-container">
                        <!-- Photo Container -->
                        <div class="multiple-photo-container">
                            <!-- Photo boxes -->
                            <div class="photo-box">
                                <img src="path_to_photo1.jpg" alt="Photo 1">
                            </div>
                            <div class="photo-box">
                                <img src="path_to_photo2.jpg" alt="Photo 2">
                            </div>
                            <div class="photo-box">
                                <img src="path_to_photo2.jpg" alt="Photo 2">
                            </div>
                            <div class="photo-box">
                                <img src="path_to_photo2.jpg" alt="Photo 2">
                            </div>
                            <div class="button-container">
                                <button type="button" class="btn">Add from Camera</button>
                                <button type="button" class="btn">Add from Gallery</button>
                            </div>
                        </div>

                        <div class="fields-container">
                            <x-ui-text-field label="Material Code" model="materials.code" type="text" :action="$actionValue" required="true" enabled="false" placeHolder="" />
                            {{-- <x-ui-dropdown-select label="Category" model="materials.jwl_category" :options="$materialCategories" :selectedValue="$materials['jwl_category']" required="true" :action="$actionValue" span="Half" /> --}}
                            <x-ui-dropdown-select label="UOM" click-event="refreshUOMs" model="matl_uoms.name" :options="$materialUOMs" :selectedValue="$matl_uoms['name']" required="true" :action="$actionValue" span="Full" />
                            <x-ui-text-field label="Description" model="materials.descr" type="textarea" :action="$actionValue" required="true" enabled="false" placeHolder="Enter Description" span="Full" />

                            <x-ui-text-field label="Selling Price" model="materials.jwl_selling_price" type="number" :action="$actionValue" required="true" placeHolder="Enter Selling Price" span="Half" />
                            <x-ui-text-field label="Buying Price" model="materials.jwl_buying_price" type="number" :action="$actionValue" required="true" placeHolder="Enter Buying Price" span="Half" />
                        </div>
                    </div>

                      <div class="card-body p-2 mt-10">
                        <h2 class="mb-2 text-center">Side Materials</h2>

                        <x-ui-button
                            click-event="addBoms"
                            cssClass="btn btn-success"
                            iconPath="images/create-icon.png"
                            button-name="Add"
                            :action="$actionValue" />

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


                 <x-ui-text-field label="Barcode" model="matl_uoms.barcode" type="text" :action="$actionValue" required="true" placeHolder="Enter Barcode" span="Half" enabled="false" />
                 <x-ui-button click-event="runExe" cssClass="btn btn-secondary" button-name="Scan Label" :action="$actionValue" />
                 <x-ui-button click-event="printLabel" cssClass="btn btn-secondary" button-name="Print Label" :action="$actionValue" />

                </x-ui-expandable-card>
            </div>
            {{-- <div class="tab-pane fade" id="detail" role="tabpanel" aria-labelledby="detail-tab" wire:ignore.self>
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
            </div> --}}

        </x-ui-tab-view-content>

        <div class="card-footer d-flex justify-content-end">
            <div>

                <x-ui-button click-event="Save" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="images/save-icon.png" />
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

<script>
    function scrollToBottom() {
        var container = document.getElementById('scroll-container');
        container.scrollTop = container.scrollHeight;
    }

    document.addEventListener('livewire:load', function () {
        // Call scrollToBottom function when the page loads
        scrollToBottom();

        Livewire.on('itemAdded', function () {
            // Call scrollToBottom function when a new item is added
            scrollToBottom();
        });
    });
</script>
