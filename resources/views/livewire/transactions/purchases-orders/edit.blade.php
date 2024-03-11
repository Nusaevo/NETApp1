<div>
    <div>
        <x-ui-button click-event="" type="Back" button-name="Back" />
    </div>

    <x-ui-page-card title="{{ $actionValue }} Puchase Order" status="{{ $status }}">
        <form wire:submit.prevent="{{ $actionValue }}" class="form w-100">
            <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>

            <x-ui-tab-view-content id="myTabContent" class="tab-content">
                <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                    <x-ui-expandable-card id="UserCard" title="Puchase Order Info" :isOpen="true">
                        <x-ui-text-field label="Tgl Transaksi" model="inputs.tr_date" type="date" :action="$actionValue" required="true" span="Half" />
                        <x-ui-text-field-search label="Supplier" name="Supplier" click-event="refreshSupplier" model="inputs.partner_id" :options="$suppliers" :selectedValue="$inputs['partner_id']" required="true" :action="$actionValue" span="Half" />

                        <div class="card-body p-2 mt-10">
                            <h2 class="mb-2 text-center">Barang</h2>
                            @if($actionValue === "Create")
                            {{-- <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#MaterialDialogBox">
                                Tambah
                            </button> --}}
                            <x-ui-button click-event="Add" button-name="Add" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="images/save-icon.png" />
                            @endif


                            <x-ui-dialog-box id="MaterialDialogBox" :visible="$materialDialogVisible" :width="'2000px'" :height="'2000px'">
                                <x-slot name="title">

                                </x-slot>

                                <x-slot name="body">
                                    @livewire('masters.materials.material-form', ['action' => $action, 'objectId' => $objectId])
                                </x-slot>
                            </x-ui-dialog-box>

                            <div class="card-body p-2 mt-10">
                                <div class="list-group mt-5" style="max-height: 500px; overflow-y: auto;" id="scroll-container">
                                    @foreach($input_details as $key => $detail)
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <h5 class="mb-1">No. {{$key+1}}</h5>
                                            <div class="col-md-2">
                                                @php
                                                $imagePath = isset($detail['image_path']) && !empty($detail['image_path']) ? $detail['image_path'] : 'https://via.placeholder.com/300';
                                                @endphp
                                                <img src="{{ $imagePath }}" alt="Material Photo" style="max-width: 100%; max-height: 100%;">
                                            </div>
                                            <div class="col-md-9">
                                                <x-ui-text-field model="input_details.{{ $key }}.matl_code" label='Product Code' type="text" :action="$actionValue" placeHolder="" enabled="false" span="Half" />
                                                <x-ui-text-field model="input_details.{{ $key }}.barcode" label='Label Code' type="text" :action="$actionValue" placeHolder="" enabled="false" span="Half" />
                                                <x-ui-text-field model="input_details.{{ $key }}.matl_descr" label='Description' type="text" :action="$actionValue" placeHolder="Description" enabled="false" span="Full" />
                                                <x-ui-text-field model="input_details.{{ $key }}.selling_price" label='Selling Price' type="text" :action="$actionValue" placeHolder="Selling Price" enabled="false" span="Half" />
                                                <x-ui-text-field model="input_details.{{ $key }}.price" label='Buying Price' type="number" :onChanged="'changePrice('. $key .', $event.target.value)'" :action="$actionValue" required="true" placeHolder="" span="Half"/>
                                                <x-ui-text-field model="input_details.{{ $key }}.qty" label='Qty' type="number" :onChanged="'changeQty('. $key .', $event.target.value)'" :action="$actionValue" required="true" placeHolder="" span="Half"/>
                                                <x-ui-text-field model="input_details.{{ $key }}.amt" label='Amount' type="text" :action="$actionValue" enabled="false" placeHolder="" span="Half"/>
                                            </div>
                                        </div>
                                        <div class="close-button">
                                            <a href="#" wire:click="deleteDetails({{ $key }})" class="btn btn-link">
                                                X
                                            </a>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                    <h3>Total Price: {{ rupiah($total_amount) }}</h3>
                            </div>
                        </div>

                </div>
                </x-ui-expandable-card>
            </x-ui-tab-view-content>
            <div class="card-footer d-flex justify-content-end">
                {{-- @if ($actionValue === 'Edit')
        <div style="padding-right: 10px;">
            <x-ui-button click-event="{{ route('purchases_deliveries.detail', ['action' =>  Crypt::encryptString('Create'),
                'objectId' =>  Crypt::encryptString($object->id)]) }}" cssClass="btn btn-primary" type="Route" loading="true" iconPath="images/create-icon.png" button-name="Order Terima Gudang" />
            </div>
            @endif --}}
            <div>
                <x-ui-button click-event="Save" button-name="Save" loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="images/save-icon.png" />
            </div>
</div>
</form>
</x-ui-page-card>

</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.livewire.on('closeMaterialDialog', function() {
            $('#MaterialDialogBox').modal('hide');
        });
    });

</script>

