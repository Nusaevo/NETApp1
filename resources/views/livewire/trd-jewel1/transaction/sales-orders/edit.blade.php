<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div>
        <div>
            <x-ui-button click-event="{{ route('sales_orders.index') }}" type="Back" button-name="Back" />
        </div>
    </div>

    <x-ui-page-card title="{{ $actionValue }} Sales Order" status="{{ $status }}">

        <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>

        <x-ui-tab-view-content id="myTabContent" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">

                <form wire:submit.prevent="{{ $actionValue }}" class="form w-100">



                    <x-ui-expandable-card id="UserCard" title="Puchase Order Info" :isOpen="true">
                        <x-ui-text-field label="Tgl Transaksi" model="inputs.tr_date" type="date" :action="$actionValue" required="true" />

                        <x-ui-text-field-search label="Supplier" name="Supplier" click-event="refreshSupplier" model="inputs.partner_id" :options="$suppliers"  required="true" :action="$actionValue" />

                        <div class="card-body p-2 mt-10">
                            <h2 class="mb-2 text-center">Barang</h2>

                           <x-ui-button click-event="addDetails" cssClass="btn btn-success" iconPath="images/create-icon.png" button-name="Tambah" :action="$actionValue" />

                            <div class="table-responsive mt-5">
                                <table id="tbl" class="table table-striped table-hover gy-7 gs-7">
                                    <thead>
                                        <tr class="fw-bold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                                            <th class="min-w-50px">No</th>
                                            <th class="min-w-300px">Nama</th>
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
                                            <td class="border" rowspan="3">
                                                {{$key+1}}
                                            </td>
                                            <td class="border" rowspan="3">
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
                                            <td class="border">
                                                <input type="hidden" wire:model.defer='input_details.{{ $key }}.matl_id'>
                                                <x-ui-text-field model="input_details.{{ $key }}.matl_code" label='Code' type="text" :action="$actionValue" placeHolder="Material Code" enabled="false"/>
                                            </td>
                                            <td class="border" rowspan="3">
                                                <x-ui-text-field model="input_details.{{ $key }}.price" label='' type="number" :onChanged="'changePrice('. $key .', $event.target.value)'" :action="$actionValue" required="true" placeHolder="" enabled="false" />
                                            </td>
                                            <td class="border" rowspan="3">
                                                <x-ui-text-field model="input_details.{{ $key }}.qty" label='' type="number" :onChanged="'changeQty('. $key .', $event.target.value)'" :action="$actionValue" required="true" placeHolder="" />
                                            </td>
                                            <td class="border" rowspan="3">
                                                <x-ui-text-field model="input_details.{{ $key }}.amt" label='' type="text" :action="$actionValue" enabled="false" placeHolder="" />
                                            </td>
                                            <td rowspan="3">
                                                <x-ui-button button-name="Delete" click-event="deleteDetails({{ $key }})" :action="$actionValue" cssClass="btn-danger" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="border">
                                                <x-ui-text-field model="input_details.{{ $key }}.barcode" label='Barcode' type="text" :action="$actionValue" placeHolder="Barcode" enabled="false" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="border">
                                                <x-ui-text-field model="input_details.{{ $key }}.matl_descr" label='Description' type="text" :action="$actionValue" placeHolder="Description" enabled="false" />
                                            </td>
                                        </tr>
                                        @endforeach

                                        <tr>
                                            <td align="right" colspan="5">Total Harga</td>
                                            <td align="left" colspan="5"> {{ rupiah($total_amount) }}</td>
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

