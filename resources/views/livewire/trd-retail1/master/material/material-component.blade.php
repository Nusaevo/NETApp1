<div>
    @php
        use App\Models\TrdRetail1\Master\Material;
    @endphp

    <x-ui-page-card title="{{ $actionValue }} {!! $menuName !!}" status="{{ $status }}">

        @if ($actionValue === 'Create')
            <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>
        @elseif(!$searchMode && $actionValue !== 'Create')
            <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>
        @endif
        <x-ui-tab-view-content id="tabMaterial" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
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
                                                <x-ui-link-text type="close" :clickEvent="'deleteImage(' . $key . ')'" class="btn btn-link"
                                                    name="x" :action="$customActionValue" />
                                            </div>
                                        </div>
                                    @empty
                                        <div class="photo-box empty">
                                            <p>No Images Captured</p>
                                        </div>
                                    @endforelse
                                </div>

                                <div class="button-container">
                                    <x-ui-image-button :action="$customActionValue" :hideStorageButton="true"></x-ui-image-button>

                                    <x-ui-dialog-box id="storageDialogBox" :width="'2000px'" :height="'2000px'">
                                        <x-slot name="body">
                                            @livewire('trd-jewel1.master.gallery.storage-component', ['isDialogBoxComponent' => true])
                                        </x-slot>
                                    </x-ui-dialog-box>
                                </div>
                            </div>
                        </div>
                    </x-ui-padding>
                    <x-ui-padding>

                        <div class="row">
                            <x-ui-text-field label="{{ $this->trans('category') }}" model="materials.category"
                                type="text" :action="$actionValue" required="true" enabled="true" />
                            <x-ui-text-field label="{{ $this->trans('code') }}" model="materials.code" type="code"
                                :action="$actionValue" required="true" :enabled="$panelEnabled" clickEvent="getMatlCode"
                                buttonName="Get Code" />
                        </div>
                        <div class="row">
                            <x-ui-text-field label="{{ $this->trans('brand') }}" model="materials.brand" type="text"
                                :action="$actionValue" required="true" enabled="true" />
                            <x-ui-text-field label="{{ $this->trans('barcode') }}" model="materials.brand"
                                type="text" :action="$actionValue" required="true" enabled="true" />
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <x-ui-text-field label="{{ $this->trans('type_code') }}" model="materials.type_code"
                                    type="text" :action="$actionValue" required="true" enabled="true" />
                            </div>
                        </div>
                        <div class="row">
                            <x-ui-text-field label="{{ $this->trans('name') }}" model="materials.name" type="text"
                                :action="$actionValue" required="true" enabled="true" />
                            <x-ui-text-field label="{{ $this->trans('buying_price') }}" model="materials.buying_price"
                                type="number" :action="$actionValue" required="true" />
                        </div>

                        <div class="row">
                            <x-ui-text-field label="{{ $this->trans('specs') }}" model="materials.specs" type="text"
                                :action="$actionValue" required="true" enabled="true" />
                            <x-ui-text-field label="{{ $this->trans('cogs') }}" model="materials.cogs" type="number"
                                :action="$actionValue" required="true" />
                        </div>
                        <div class="row">
                            <x-ui-text-field label="{{ $this->trans('dimension') }}" model="materials.dimension"
                                type="text" :action="$actionValue" required="true" enabled="true" />
                            <x-ui-text-field label="{{ $this->trans('stock') }}" model="materials.stock" type="text"
                                :action="$actionValue" required="true" enabled="true" />
                        </div>

                        <div class="row">
                            <div class="col-sm-6">
                                <x-ui-text-field label="{{ $this->trans('uom') }}" model="materials.uom"
                                    type="text" :action="$actionValue" required="true" enabled="true" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <x-ui-text-field label="{{ $this->trans('selling_price') }}" model="materials.selling_price"
                                    type="number" :action="$actionValue" required="true" enabled="true" />
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <x-ui-text-field label="{{ $this->trans('remark') }} " model="materials.remark"
                                    type="textarea" :action="$customActionValue" />
                            </div>
                        </div>
                    </x-ui-padding>
                </x-ui-card>
            </div>
            {{-- @if (!$searchMode && $actionValue !== 'Create')
        <div class="tab-pane fade show" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
            <x-ui-card>
                <div wire:ignore>
                @livewire('trd-jewel1.master.material.transaction-data-table', ['materialID' => $objectIdValue])
                </div>
            </x-ui-card>
        </div>
        @endif --}}
        </x-ui-tab-view-content>

        <x-ui-footer>

            @if (!$searchMode && $actionValue == 'Edit')
                @include('layout.customs.buttons.disable')
            @endif

            <x-ui-button clickEvent="printBarcode" cssClass="btn btn-primary" button-name="Print Label"
                :action="$customActionValue" />
            <x-ui-button clickEvent="Save" button-name="Save" loading="true" :action="$customActionValue"
                cssClass="btn-primary" iconPath="save.svg" />

            @if ($searchMode)
                <x-ui-button clickEvent="addPurchaseOrder" button-name="Add Item" loading="true" :action="$actionValue"
                    cssClass="btn-primary" iconPath="add.svg" />
            @endif

        </x-ui-footer>
    </x-ui-page-card>
</div>
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.addEventListener('openStorageDialog', function() {
                $('#storageDialogBox').modal('show');
            });

            window.addEventListener('closeStorageDialog', function() {
                $('#storageDialogBox').modal('hide');
            });
        });
    </script>
@endpush
