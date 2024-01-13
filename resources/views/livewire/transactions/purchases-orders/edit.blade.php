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



                    <x-ui-expandable-card id="UserCard" title="Puchase Order Info" :isOpen="true">
                        <x-ui-text-field label="Tgl Transaksi" model="inputs.tr_date" type="date" :action="$actionValue" required="true" span="Half"/>

                        <x-ui-text-field-search label="Supplier" name="Supplier" click-event="refreshSupplier" model="inputs.partner_id" :options="$suppliers" :selectedValue="$inputs['partner_id']" required="true" :action="$actionValue" span="Half" />

                        <div class="card-body p-2 mt-10">
                            <h2 class="mb-2 text-center">Barang</h2>
                            @if($actionValue === "Create")
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#MaterialDialogBox">
                                    Tambah
                                </button>
                            @endif

                            <x-ui-dialog-box id="MaterialDialogBox" :visible="$materialDialogVisible">
                                <x-slot name="title">
                                    <h2>My Dialog Title</h2>
                                </x-slot>

                                <x-slot name="body">
                                    @livewire('panels.material-form', ['materialActionValue' => $actionValue])
                                </x-slot>
                            </x-ui-dialog-box>
                            {{-- <x-ui-button click-event="openModal" cssClass="btn btn-success" iconPath="images/create-icon.png" button-name="Tambah" :action="$actionValue" /> --}}

                            <div class="card-body p-2 mt-10">
                                <div class="list-group mt-5" style="max-height: 500px; overflow-y: auto;" id="scroll-container">
                                    @foreach($input_details as $key => $detail)
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="col-md-1 d-flex align-items-center justify-content-center">
                                                    <span>{{$key+1}}</span>
                                                </div>
                                                <div class="col-md-2">
                                                    @if(isset($detail['image_path']) && !empty($detail['image_path']))
                                                        <img src="{{ Storage::url($detail['image_path']) }}" alt="Material Photo" class="img-fluid">
                                                    @endif
                                                </div>
                                                <div class="col-md-9">
                                                     <x-ui-text-field model="input_details.{{ $key }}.matl_descr" label='Description' type="text" :action="$actionValue" placeHolder="Description" enabled="false" span="Full"/>

                                                     <x-ui-text-field model="input_details.{{ $key }}.matl_code" label='Code' type="text" :action="$actionValue" placeHolder="Material Code" enabled="false" span="Half"/>

                                                     <x-ui-text-field model="input_details.{{ $key }}.selling_price" label='Selling Price' type="text" :action="$actionValue" placeHolder="Selling Price" enabled="false" span="Half"/>

                                                     <x-ui-text-field model="input_details.{{ $key }}.buying_price" label='Price' type="number" :onChanged="'changePrice('. $key .', $event.target.value)'" :action="$actionValue" required="true" placeHolder=""/>
                                                     <x-ui-text-field model="input_details.{{ $key }}.qty" label='Qty' type="number" :onChanged="'changeQty('. $key .', $event.target.value)'" :action="$actionValue" required="true" placeHolder="" />
                                                     <x-ui-text-field model="input_details.{{ $key }}.amt" label='Amount' type="text" :action="$actionValue" enabled="false" placeHolder="" />
                                                </div>
                                            </div>
                                            <!-- Updated delete button with rounded "X" -->
                                            <div class="close-button">
                                                <a href="#" wire:click="deleteDetails({{ $key }})" class="btn btn-link">
                                                    X
                                                 </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                {{-- <div class="d-flex justify-content-end mt-4">
                                    <h3>Total Price: {{ rupiah($total_prices) }}</h3>
                                </div> --}}
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
    document.addEventListener('DOMContentLoaded', function () {
        window.livewire.on('closeMaterialDialog', function () {
            $('#MaterialDialogBox').modal('hide');
        });
    });
</script>

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

