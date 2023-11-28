<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <div>
            <x-ui-button click-event="{{ route('purchases_orders.index') }}" type="Back" button-name="Back" />
        </div>
    </div>

    <x-ui-page-card title="{{ $actionValue }} Puchase Order" status="{{ $status }}">

        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>

        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">

                <form wire:submit.prevent="{{ $actionValue }}" class="form w-100">
                    <x-ui-expandable-card id="MaterialCard" title="Tambah Barang" :isOpen="false">
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
                                <x-ui-button click-event="addItem" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="images/save-icon.png" />
                            </div>
                        </div>
                    </x-ui-expandable-card>
                    <x-ui-expandable-card id="UserCard" title="Puchase Order Info" :isOpen="true">
                        <x-ui-text-field label="Tgl Transaksi" model="inputs.tr_date" type="date" :action="$actionValue" required="true" />

                        <x-ui-text-field-search label="Supplier" name="Supplier" click-event="refreshSupplier" model="inputs.partner_id" :options="$suppliers" :selectedValue="$inputs['partner_id']" required="true" :action="$actionValue" />

                        <div class="card-body p-2 mt-10">
                            <h2 class="mb-2 text-center">Barang</h2>

                            <x-ui-button click-event="addDetails" cssClass="btn btn-success" iconPath="images/create-icon.png" button-name="Tambah" :action="$actionValue" />

                            <div class="table-responsive mt-5">
                                <table id="tbl" class="table table-striped table-hover gy-7 gs-7">
                                    <thead>
                                        <tr class="fw-bold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                                            <th class="min-w-50px">No</th>
                                            <th class="min-w-100px">Image</th>
                                            <th class="min-w-300px">Barang</th>
                                            <th class="min-w-100px">Harga</th>
                                            <th class="min-w-100px">Qty</th>
                                            <th class="min-w-15px">Sub Total</th>
                                            <th class="min-w-15px">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($input_details as $key => $detail)
                                        <tr>
                                            <td class="border">
                                                {{$key+1}}
                                            </td>
                                            <td class="border">
                                                <input type="file" id="photo1" name="photo1" class="photo-input" accept="image/*">
                                                <label for="photo1" class="image-upload-label">
                                                    <div class="image-preview" id="preview1"></div>
                                                    <div class="overlay">
                                                        <button type="button" class="view-btn" onclick="viewImage('preview1')">Full Screen</button>
                                                        <button type="button" class="delete-btn" onclick="deleteImage('preview1', 'photo1')">Delete</button>
                                                    </div>
                                                </label>
                                            </td>
                                            <td class="border">
                                                <div>
                                                    <input type="hidden" wire:model.defer='input_details.{{ $key }}.matl_id'>
                                                    <div wire:ignore class="input-form ">
                                                        <select class="form-select itemsearch form-control p-2" id="item-{{$key}}">
                                                            @if(isset($input_details[$key]['matl_descr']))
                                                            <option value='input_details.{{ $key }}.matl_id'>{{$input_details[$key]['matl_descr']}}</option>
                                                            @endif
                                                        </select>
                                                    </div>
                                                </div>
                                            </td>
                                            {{-- <td class="border">
                                                <x-ui-text-field model="input_details.{{ $key }}.price" label='' type="number"
                                            :onChanged="'changePrice('. $key .', $event.target.value)'"
                                            :action="$actionValue" required="true" placeHolder="" />
                                            </td> --}}
                                            <td class="border">
                                                <x-ui-text-field model="input_details.{{ $key }}.qty" label='' type="number" :onChanged="'changeQty('. $key .', $event.target.value)'" :action="$actionValue" required="true" placeHolder="" />
                                            </td>
                                            <td class="border">
                                                <x-ui-text-field model="input_details.{{ $key }}.price" label='' type="number" :onChanged="'changePrice('. $key .', $event.target.value)'" :action="$actionValue" required="true" placeHolder="" />
                                            </td>
                                            <td class="border">
                                                <x-ui-text-field model="input_details.{{ $key }}.amt" label='' type="text" :action="$actionValue" enabled="false" placeHolder="" />
                                            </td>

                                            <td>
                                                <x-ui-button button-name="Delete" click-event="deleteDetails({{ $key }})" :action="$actionValue" cssClass="btn-danger" />
                                            </td>
                                        </tr>

                                        @endforeach
                                        <tr>
                                            <td align="right" colspan="4">Total Harga</td>
                                            <td align="left" colspan="4"> {{ rupiah($total_amount) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </x-ui-expandable-card>
            </div>
        </x-ui-tab-view-content>
        </form>
        <div class="card-footer d-flex justify-content-end">
            @if ($actionValue === 'Edit')
            <div style="padding-right: 10px;">
                <x-ui-button click-event="{{ route('purchases_deliveries.detail', ['action' =>  Crypt::encryptString('Create'),
                'objectId' =>  Crypt::encryptString($object->id)]) }}" cssClass="btn btn-primary" type="Route" loading="true" iconPath="images/create-icon.png" button-name="Order Terima Gudang" />
            </div>
            @endif
            <div>
                <x-ui-button click-event="{{ $actionValue }}" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="images/save-icon.png" />
            </div>
        </div>
    </x-ui-page-card>
</div>

<script>
    $(document).ready(function() {
        // Function to initialize select2
        function initializeSelect2(element) {
            element.select2({
                placeholder: 'Select Item'
                , ajax: {
                    url: '/search-item'
                    , dataType: 'json'
                    , delay: 250
                    , processResults: function(data) {
                        return {
                            results: $.map(data, function(item) {
                                return {
                                    text: item.name
                                    , id: item.id
                                }
                            })
                        };
                    }
                    , cache: true
                }
            });
            element.on('change', function(e) {
                const itemClass = Array.from(document.querySelectorAll('.itemsearch'));
                var index = itemClass.indexOf(e.target) / 2;
                Livewire.emit('changeItem', e.target.id, e.target.value, index);
            });
        }

        // Initialize select2 for all .itemsearch inputs
        $('.itemsearch').each(function() {
            initializeSelect2($(this));
        });

        // Event listener for reapplying select2
        window.addEventListener('reApplySelect2', event => {
            $('.itemsearch').each(function() {
                initializeSelect2($(this));
            });
        });
    });

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

