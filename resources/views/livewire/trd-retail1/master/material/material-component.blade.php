<div>
    @php
        use App\Models\TrdRetail1\Master\Material;
    @endphp

    <x-ui-page-card title="{{ $this->trans($actionValue) }} {!! $menuName !!}" status="{{ $this->trans($status) }}">

        {{-- Tabs --}}
        @if ($actionValue === 'Create' || (!$isComponent && $actionValue !== 'Create'))
            <x-ui-tab-view id="myTab" tabs="general"></x-ui-tab-view>
        @endif

        <x-ui-tab-view-content id="tabMaterial" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <div class="row mt-4">


                    <!-- Main Form Section -->
                    <div class="col-md-12">
                        <x-ui-card title="Images">
                            <x-ui-padding>
                                <div class="material-info-container">
                                    <div class="photo-and-button-container">
                                        <!-- Photo Container -->
                                        <div class="multiple-photo-container">
                                            @forelse($capturedImages as $key => $image)
                                                <div class="photo-box">
                                                    <x-ui-image src="{{ $image['url'] }}" alt="Captured Image"
                                                        width="200px" height="200px" />
                                                    <div class="image-close-button">
                                                        <x-ui-link-text type="close" :clickEvent="'deleteImage(' . $key . ')'"
                                                            class="btn btn-link" name="x" />
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="photo-box empty">
                                                    <p>No Images Captured</p>
                                                </div>
                                            @endforelse
                                        </div>
                                        <div class="button-container">
                                            <x-ui-image-button :action="$customActionValue"
                                                hideStorageButton="false"></x-ui-image-button>
                                        </div>
                                    </div>
                                </div>
                            </x-ui-padding>
                        </x-ui-card>

                        <x-ui-card title="Main Information">
                            <div class="row">
                                <x-ui-dropdown-select label="{{ $this->trans('category') }}" model="materials.category"
                                    :options="$materialCategories" :enabled="$panelEnabled" required="true" onChanged="onCategoryChanged" />
                                <x-ui-text-field label="{{ $this->trans('code') }}" model="materials.code"
                                    type="text" :action="$actionValue" required="true" enabled="false"
                                    clickEvent="getMatlCode" buttonName="Get Code" :buttonEnabled="$panelEnabled"/>
                                <x-ui-text-field label="{{ $this->trans('barcode') }}" model="matl_uoms.barcode"
                                    type="text" :action="$actionValue" enabled="true" />
                            </div>

                            <div class="row">
                                <x-ui-text-field label="{{ $this->trans('brand') }}" model="materials.brand"
                                    type="text" :action="$actionValue" required="true" enabled="true" />
                                <x-ui-text-field label="{{ $this->trans('type_code') }}" model="materials.type_code"
                                    type="text" :action="$actionValue" required="true" enabled="true" />
                            </div>

                            <div class="row">
                                <x-ui-text-field label="{{ $this->trans('color_code') }}" model="materials.color_code"
                                    type="text" :action="$actionValue" required="false" enabled="true" />

                                <x-ui-text-field label="{{ $this->trans('color_name') }}" model="materials.color_name"
                                    type="text" :action="$actionValue" required="false" enabled="true" />
                            </div>
                            <div class="row">
                                <x-ui-text-field label="{{ $this->trans('name') }}" model="materials.name"
                                    type="text" :action="$actionValue" required="true" enabled="true" />
                            </div>
                            <div class="row">
                                <x-ui-text-field label="{{ $this->trans('remarks') }}" model="materials.remarks"
                                    type="textarea" :action="$customActionValue" />
                            </div>
                            <div class="row">
                                <x-ui-text-field label="{{ $this->trans('buying_price') }}"
                                    model="materials.buying_price" type="number" :action="$actionValue" required="false"
                                    enabled="true" />
                                <x-ui-text-field label="{{ $this->trans('seq') }}" model="materials.seq" type="text"
                                    :action="$actionValue" required="true" enabled="true" />
                            </div>

                            <div class="row">
                                <x-ui-text-field label="{{ $this->trans('selling_price') }}"
                                    model="materials.selling_price" type="number" :action="$actionValue" required="false"
                                    enabled="true" />
                                <x-ui-text-field label="{{ $this->trans('cogs') }}" model="materials.cogs"
                                    type="number" :action="$actionValue" required="true" enabled="true" />

                                <x-ui-text-field label="{{ $this->trans('stock') }}" model="materials.stock"
                                    type="text" :action="$actionValue" required="false" enabled="false" />

                            </div>
                            <div class="row">
                                <x-ui-text-field label="{{ $this->trans('tag') }}" model="materials.tag"
                                    type="text" :action="$actionValue" required="false" enabled="false" />
                            </div>
                        </x-ui-card>

                    </div>

                </div>
            </div>
        </x-ui-tab-view-content>


        {{-- <x-ui-card>
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
                                    <x-ui-image-button :action="$customActionValue" hideStorageButton="false"></x-ui-image-button>

                                    <x-ui-dialog-box id="storageDialogBox" :width="'2000px'" :height="'2000px'">
                                        <x-slot name="body">
                                            @livewire('base.master.gallery.storage-component', [ 'isComponent' => true])
                                        </x-slot>
                                    </x-ui-dialog-box>
                                </div>
                            </div>
                        </div>
                    </x-ui-padding>
                    <x-ui-padding>

                        <div class="row">
                            <x-ui-dropdown-select label="{{ $this->trans('category') }}" clickEvent="" model="materials.category"
                                :options="$materialCategories" :enabled="$panelEnabled" required="true" :action="$actionValue" onChanged="onCategoryChanged" />
                            <x-ui-text-field label="{{ $this->trans('code') }}" model="materials.code" type="code"
                                :action="$actionValue" required="true" :enabled="$panelEnabled" clickEvent="getMatlCode"
                                buttonName="Get Code" />
                            <x-ui-text-field label="{{ $this->trans('barcode') }}" model="matl_uoms.barcode"
                                    type="text" :action="$actionValue" enabled="true" />
                        </div>
                        <div class="row">
                            <x-ui-text-field label="{{ $this->trans('seq') }}" model="materials.seq" type="number"
                                :action="$actionValue" required="true" enabled="true" />
                            <x-ui-text-field label="{{ $this->trans('brand') }}" model="materials.brand" type="text"
                                :action="$actionValue" required="true" enabled="true" />
                            <x-ui-text-field label="{{ $this->trans('type_code') }}" model="materials.type_code"
                                    type="text" :action="$actionValue" required="true" enabled="true" />
                        </div>
                        <div class="row">
                            <x-ui-text-field label="{{ $this->trans('color_code') }}" model="materials.color_code" type="text"
                                :action="$actionValue" required="false" enabled="true" />
                            <x-ui-text-field label="{{ $this->trans('color_name') }}" model="materials.color_name"
                                    type="text" :action="$actionValue" required="false" enabled="true" />
                        </div>
                        <div class="row">
                            <x-ui-text-field label="{{ $this->trans('name') }}" model="materials.name" type="text"
                                :action="$actionValue" required="true" enabled="true" />
                        </div>
                        <div class="row">
                                <x-ui-text-field label="{{ $this->trans('remarks') }} " model="materials.remarks"
                                    type="textarea" :action="$customActionValue" />
                        </div>

                        <div class="row">
                                <x-ui-text-field label="{{ $this->trans('selling_price') }}" model="materials.selling_price"
                                    type="number" :action="$actionValue" required="false" enabled="true" />
                                <x-ui-dropdown-select label="{{ $this->trans('uom') }}" clickEvent="" model="matl_uoms.matl_uom"
                                        :options="$materialUOMs" :enabled="$panelEnabled" required="true" :action="$actionValue"/>
                        </div>

                        <div class="row">
                            <x-ui-text-field label="{{ $this->trans('buying_price') }}" model="materials.buying_price"
                                type="number" :action="$actionValue" required="false" enabled="true" />
                            <x-ui-text-field label="{{ $this->trans('cogs') }}" model="materials.cogs" type="number"
                                :action="$actionValue" required="true" />
                            <x-ui-text-field label="{{ $this->trans('stock') }}" model="materials.stock" type="text"
                                :action="$actionValue" required="true" enabled="false" />
                        </div>
                        <div class="row">
                            <x-ui-text-field label="{{ $this->trans('tag') }}" model="materials.tag" type="text"
                                :action="$actionValue" required="false" enabled="false"/>
                        </div>
                    </x-ui-padding>
                </x-ui-card> --}}

        <x-ui-footer>
            @if (!$isComponent && $actionValue == 'Edit')
                @include('layout.customs.buttons.disable')
            @endif
            <x-ui-button clickEvent="Save" button-name="Save" loading="true" :action="$customActionValue"
                cssClass="btn-primary" iconPath="save.svg" />
            @if ($isComponent)
                <x-ui-button clickEvent="addPurchaseOrder" button-name="Add Item" loading="true" :action="$actionValue"
                    cssClass="btn-primary" iconPath="add.svg" />
            @endif
        </x-ui-footer>
    </x-ui-page-card>

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
</div>
