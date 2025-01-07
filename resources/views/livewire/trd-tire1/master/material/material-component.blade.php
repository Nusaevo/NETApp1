<div>
    @php
        use App\Models\TrdTIre1\Master\Material;
    @endphp

    <x-ui-page-card title="{{ $actionValue }} {!! $menuName !!}" status="{{ $status }}">

        {{-- Tabs --}}
        @if ($actionValue === 'Create')
            <x-ui-tab-view id="myTab" tabs="general"></x-ui-tab-view>
        @endif

        <x-ui-tab-view-content id="tabMaterial" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <div class="row mt-4">

                    <!-- Main Form Section -->
                    <div class="col-md-8">
                        <x-ui-card title="Main Information">
                            <x-ui-padding>
                                <div class="row">
                                    <x-ui-dropdown-select label="{{ $this->trans('brand') }}" model="materials.brand"
                                        :options="$materialMerk" required="false" :action="$customActionValue" />
                                    <x-ui-text-field label="{{ $this->trans('code') }}" model="materials.code"
                                        type="code" :action="$actionValue" required="true" enabled="true"
                                        clickEvent="getMatlCode" buttonName="Kode Baru" />
                                </div>
                                <div class="row">
                                    <x-ui-dropdown-select label="{{ $this->trans('category') }}"
                                        model="materials.category" :options="$materialType" required="true" :action="$actionValue"
                                        onChanged="onBrandChanged" />
                                    <x-ui-dropdown-select label="{{ $this->trans('type_code') }}"
                                        model="materials.type_code" :options="$materialJenis" required="false"
                                        :action="$customActionValue" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field label="{{ $this->trans('name') }}" model="materials.name"
                                        type="text" :action="$customActionValue" required="true" enabled="true" />
                                </div>
                                <div class="row">
                                    <x-ui-text-field label="{{ $this->trans('barcode') }}" model="matl_uoms.barcode"
                                        type="number" :action="$customActionValue" required="false" enabled="true" />
                                </div>
                            </x-ui-padding>
                        </x-ui-card>

                        <x-ui-card title="Additional Information">
                            <x-ui-padding>
                                <div class="row">
                                    <x-ui-text-field label="{{ $this->trans('size') }}" model="materials.size"
                                        type="text" :action="$customActionValue" required="false" enabled="true" />
                                    <x-ui-text-field label="{{ $this->trans('pattern') }}" model="materials.pattern"
                                        type="text" :action="$customActionValue" required="false" enabled="true" />
                                    <x-ui-text-field label="{{ $this->trans('point') }}" model="materials.point"
                                        type="number" :action="$customActionValue" required="false" enabled="true" />
                                </div>
                            </x-ui-padding>
                        </x-ui-card>
                    </div>

                    <!-- Sidebar Section -->
                    <div class="col-md-4">
                        <x-ui-card title="Associations">
                            <x-ui-padding>
                                <x-ui-text-field label="{{ $this->trans('stock') }}" model="materials.stok"
                                    type="number" :action="$customActionValue" required="false" enabled="false" />
                                <x-ui-text-field label="{{ $this->trans('reserved') }}" model="materials.reserved"
                                    type="number" :action="$customActionValue" required="false" enabled="true" />
                            </x-ui-padding>
                        </x-ui-card>

                        <x-ui-card title="Pricing">
                            <x-ui-padding>
                                <x-ui-text-field label="{{ $this->trans('selling_price') }}"
                                    model="materials.selling_price" type="number" :action="$customActionValue"
                                    required="true" enabled="true" />
                                <x-ui-text-field label="{{ $this->trans('buying_price') }}"
                                    model="materials.buying_price" type="number" :action="$customActionValue" required="true"
                                    enabled="true" />
                                <x-ui-text-field label="{{ $this->trans('cost') }}" model="materials.cost"
                                    type="number" :action="$customActionValue" required="true" enabled="true" />
                            </x-ui-padding>
                        </x-ui-card>
                        <x-ui-card title="Tagging">
                            <x-ui-padding>
                                <x-ui-text-field label="{{ $this->trans('tag') }}" model="materials.tag" type="text"
                                    :action="$customActionValue" required="true" enabled="true" />
                            </x-ui-padding>
                        </x-ui-card>
                    </div>

                </div>
            </div>
        </x-ui-tab-view-content>

        <x-ui-footer>
            <x-ui-button clickEvent="Save" button-name="Save" loading="true" :action="$customActionValue" cssClass="btn-primary"
                iconPath="save.svg" />
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
