<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <div>
            <x-ui-button click-event="{{ route('materials.index') }}" type="Back" button-name="Back" />
        </div>
    </div>
    <x-ui-page-card title="{{ $actionValue }} Material" status="{{ $status }}">

        <form wire:submit.prevent="{{ $actionValue }}" class="form w-100">
            <x-ui-tab-view id="materialTab" tabs="material,materialDetail"> </x-ui-tab-view>
            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="material" role="tabpanel" aria-labelledby="material-tab">
                    <x-ui-expandable-card id="UserCard" title="Material General Info" :isOpen="true">

                        <div class="photo-upload-container">
                            <!-- Photo Upload Box 1 -->
                            <div class="photo-upload-box">
                                <input type="file" id="photo1" name="photo1" class="photo-input" onchange="previewImage(event, 'preview1')" accept="image/*">
                                <label for="photo1" class="image-upload-label">
                                    <div class="image-preview" id="preview1"></div>
                                    <div class="overlay">
                                        <button type="button" class="view-btn" onclick="viewImage('preview1')">Full Screen</button>
                                        <button type="button" class="delete-btn" onclick="deleteImage('preview1', 'photo1')">Delete</button>
                                    </div>
                                </label>
                                <div class="image-label">Photo 1</div>
                            </div>
                            <!-- Photo Upload Box 2 -->
                            <div class="photo-upload-box">
                                <input type="file" id="photo2" name="photo2" class="photo-input" onchange="previewImage(event, 'preview2')" accept="image/*">
                                <label for="photo2" class="image-upload-label">
                                    <div class="image-preview" id="preview2"></div>
                                    <div class="overlay">
                                        <button type="button" class="view-btn" onclick="viewImage('preview2')">Full Screen</button>
                                        <button type="button" class="delete-btn" onclick="deleteImage('preview2', 'photo2')">Delete</button>
                                    </div>
                                </label>
                                <div class="image-label">Photo 2</div>
                            </div>
                            <!-- Photo Upload Box 3 -->
                            <div class="photo-upload-box">
                                <input type="file" id="photo3" name="photo3" class="photo-input" onchange="previewImage(event, 'preview3')" accept="image/*">
                                <label for="photo3" class="image-upload-label">
                                    <div class="image-preview" id="preview3"></div>
                                    <div class="overlay">
                                        <button type="button" class="view-btn" onclick="viewImage('preview3')">Full Screen</button>
                                        <button type="button" class="delete-btn" onclick="deleteImage('preview3', 'photo3')">Delete</button>
                                    </div>
                                </label>
                                <div class="image-label">Photo 3</div>
                            </div>
                            <!-- Photo Upload Box 4 -->
                            <div class="photo-upload-box">
                                <input type="file" id="photo4" name="photo4" class="photo-input" onchange="previewImage(event, 'preview4')" accept="image/*">
                                <label for="photo4" class="image-upload-label">
                                    <div class="image-preview" id="preview4"></div>
                                    <div class="overlay">
                                        <button type="button" class="view-btn" onclick="viewImage('preview4')">Full Screen</button>
                                        <button type="button" class="delete-btn" onclick="deleteImage('preview4', 'photo4')">Delete</button>
                                    </div>
                                </label>
                                <div class="image-label">Photo 4</div>
                            </div>
                        </div>

                        <x-ui-text-field label="Material Code" model="materials.code" type="text" :action="$actionValue" required="true" enabled="false" placeHolder="" />

                        <x-ui-text-field label="Name" model="materials.name" type="text" :action="$actionValue" required="true" placeHolder="Enter Name" />
                        <x-ui-text-field label="Type Code" model="materials.type_code" type="text" :action="$actionValue" required="true" placeHolder="Enter Type Code" span="Full" />
                        <x-ui-text-field label="Class Code" model="materials.class_code" type="text" :action="$actionValue" required="true" placeHolder="Enter Class Code" span="Full" />
                        <x-ui-text-field label="Carat" model="materials.jwl_carat" type="text" :action="$actionValue" required="false" placeHolder="Carat" span="Half" />
                        <x-ui-text-field label="Base Material" model="materials.jwl_base_matl" type="text" :action="$actionValue" required="false" placeHolder="Enter Base Material" span="Half" />
                        <x-ui-text-field label="Base Material" model="materials.jwl_base_matl" type="text" :action="$actionValue" required="false" placeHolder="Enter Base Material" span="Half" />
                        <x-ui-text-field label="Category" model="materials.jwl_category" type="text" :action="$actionValue" required="false" placeHolder="Enter Category" span="Half" />
                        <x-ui-text-field label="Weight (g)" model="materials.jwl_wgt_gold" type="number" :action="$actionValue" required="false" placeHolder="Enter Weight (g)" span="Half" />
                        <x-ui-text-field label="Supplier ID" model="materials.jwl_supplier_id" type="number" :action="$actionValue" required="false" placeHolder="Enter Supplier ID" span="Half" />
                        <x-ui-text-field label="Sides Carat" model="materials.jwl_sides_carat" type="number" :action="$actionValue" required="false" placeHolder="Enter Sides Carat" span="Half" />
                        <x-ui-text-field label="Sides Count" model="materials.jwl_sides_cnt" type="number" :action="$actionValue" required="false" placeHolder="Enter Sides Count" span="Half" />
                        <x-ui-text-field label="Sides Material" model="materials.jwl_sides_matl" type="text" :action="$actionValue" required="false" placeHolder="Enter Sides Material" span="Half" />
                        <x-ui-text-field label="Selling Price (USD)" model="materials.jwl_selling_price_usd" type="number" :action="$actionValue" required="false" placeHolder="Enter Selling Price (USD)" span="Half" />
                        <x-ui-text-field label="Selling Price (IDR)" model="materials.jwl_selling_price_idr" type="number" :action="$actionValue" required="false" placeHolder="Enter Selling Price (IDR)" span="Half" />
                        <x-ui-text-field label="Sides Calculation Method" model="materials.jwl_sides_calc_method" type="text" :action="$actionValue" required="false" placeHolder="Enter Sides Calculation Method" span="Half" />
                        <x-ui-text-field label="Material Price" model="materials.jwl_matl_price" type="number" :action="$actionValue" required="false" placeHolder="Enter Material Price" span="Half" />
                        <x-ui-text-field label="Selling Price" model="materials.jwl_selling_price" type="number" :action="$actionValue" required="false" placeHolder="Enter Selling Price" span="Half" />
                        <x-ui-text-field label="Description" model="materials.descr" type="textarea" :action="$actionValue" required="false" placeHolder="Enter Description" span="Full" />
                        <x-ui-text-field label="Class Code" model="materials.class_code" type="text" :action="$actionValue" required="false" placeHolder="Enter Class Code" span="Full" />
                        <x-ui-text-field label="Class Code" model="materials.class_code" type="text" :action="$actionValue" required="false" placeHolder="Enter Class Code" span="Full" />


                    </x-ui-expandable-card>
                </div>
                <div class="tab-pane fade" id="materialDetail" role="tabpanel" aria-labelledby="materialdetail-tab">
                    <x-ui-expandable-card id="detailCard" title="Material Detail" :isOpen="true">
                        <x-ui-text-field label="Sequence" model="material_details.seq" type="number" :action="$actionValue" required="true" placeholder="Enter Sequence" />
                        <x-ui-text-field label="Sides Carat" model="material_details.jwl_sides_carat" type="number" :action="$actionValue" required="false" placeholder="Enter Sides Carat" span="Half" />
                        <x-ui-text-field label="Sides Count" model="material_details.jwl_sides_cnt" type="number" :action="$actionValue" required="false" placeholder="Enter Sides Count" span="Half" />
                        <x-ui-text-field label="Sides Material" model="material_details.jwl_sides_matl" type="text" :action="$actionValue" required="false" placeholder="Enter Sides Material" span="Half" />
                        <x-ui-text-field label="Sides Parcel" model="material_details.jwl_sides_parcel" type="text" :action="$actionValue" required="false" placeholder="Enter Sides Parcel" span="Half" />
                        <x-ui-text-field label="Sides Price" model="material_details.jwl_sides_price" type="number" :action="$actionValue" required="false" placeholder="Enter Sides Price" span="Half" />
                        <x-ui-text-field label="Sides Amount" model="material_details.jwl_sides_amt" type="number" :action="$actionValue" required="false" placeholder="Enter Sides Amount" span="Half" />

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
</div>

<script>
    function previewImage(event, previewId) {
        var reader = new FileReader();
        reader.onload = function() {
            var output = document.getElementById(previewId);
            output.style.backgroundImage = 'url(' + reader.result + ')';
        };
        reader.readAsDataURL(event.target.files[0]);
    }

    function deleteImage(previewId, inputId) {
        var preview = document.getElementById(previewId);
        var input = document.getElementById(inputId);
        preview.style.backgroundImage = 'none';
        input.value = '';
    }

    function viewImage(previewId) {
        var preview = document.getElementById(previewId);
        var imageUrl = preview.style.backgroundImage.slice(5, -2); // Extract the URL

        // Create the modal container
        var modal = document.createElement('div');
        modal.style.position = 'fixed';
        modal.style.top = 0;
        modal.style.left = 0;
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
        modal.style.display = 'flex';
        modal.style.justifyContent = 'center';
        modal.style.alignItems = 'center';
        modal.style.zIndex = '1000';

        // Create the image element
        var img = new Image();
        img.src = imageUrl;
        img.style.maxWidth = '80%';
        img.style.maxHeight = '80%';
        img.style.margin = 'auto';

        // Close the modal on click
        modal.addEventListener('click', function() {
            document.body.removeChild(modal);
        });

        // Append the image to the modal container
        modal.appendChild(img);

        // Append the modal to the body
        document.body.appendChild(modal);
    }

</script>

