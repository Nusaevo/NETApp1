<div>
    @php
        use App\Models\TrdTIre1\Master\Material;
    @endphp

    <x-ui-page-card isForm="true"
        title="{{ $this->trans($actionValue) }} {!! $menuName !!} {{ $this->object->code ? ' (#' . $this->object->code . ')' : '' }}"
        status="{{ $this->trans($status) }}">

        {{-- Tabs --}}
        @if ($actionValue === 'Create')
            <x-ui-tab-view id="myTab" tabs="general"></x-ui-tab-view>
        @endif

        <x-ui-tab-view-content id="tabMaterial" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <div class="row mt-4">
                    <!-- Main Form Section -->
                    <div class="col-md-8">
                        <x-ui-card title="Product Type">
                            <x-ui-dropdown-select label="{{ $this->trans('type_code') }}" model="materials.type_code"
                                :options="$materialType" required="true" :action="$actionValue" onChanged="onBrandChanged"
                                :enabled="$isPanelEnabled" />
                        </x-ui-card>
                        <x-ui-card title="Main Information">
                            <x-ui-padding>
                                <div class="row">
                                    <x-ui-dropdown-select label="{{ $this->trans('brand') }}" model="materials.brand"
                                        :selectedValue="$materials['brand']" :options="$materialMerk" required="false" :action="$actionValue"
                                        onChanged="generateName" clickEvent="openBrandDialogBox" buttonName="+"
                                        :enabled="$isPanelEnabled" :buttonEnabled="$isPanelEnabled" />
                                    <x-ui-dialog-box id="brandDialogBox" title="Form Merk" width="600px" height="400px"
                                        onOpened="openBrandDialogBox" onClosed="closeBrandDialogBox" :buttonEnabled="$isPanelEnabled">
                                        <x-slot name="body">
                                            <x-ui-text-field label="Code" model="inputs_brand.str1" type="text"
                                                :action="$actionValue" required="true" enabled="true" capslockMode="true" />
                                            <x-ui-text-field label="Merk" model="inputs_brand.str2" type="text"
                                                :action="$actionValue" required="true" enabled="true" capslockMode="true" />
                                        </x-slot>
                                        <x-slot name="footer">
                                            <x-ui-button clickEvent="saveBrand" button-name="Save" loading="true"
                                                :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
                                        </x-slot>
                                    </x-ui-dialog-box>

                                    <x-ui-text-field label="{{ $this->trans('code') }}" model="materials.code"
                                        type="code" :action="$actionValue" required="true" enabled="true"
                                        clickEvent="getMatlCode" buttonName="Kode Baru" :buttonEnabled="$isPanelEnabled" />
                                </div>
                                <div class="row">
                                    <x-ui-dropdown-select label="{{ $this->trans('category') }}"
                                        model="materials.category" :options="$materialCategory" required="false"
                                        :action="$actionValue" :buttonEnabled="$isPanelEnabled" onChanged="generateNameTag" />
                                    {{-- <x-ui-dropdown-select label="{{ $this->trans('class_code') }}"
                                        model="materials.class_code" :options="$materialJenis" required="false"
                                        :action="$actionValue" /> --}}

                                    <x-ui-text-field-search label="{{ $this->trans('class_code') }}"
                                        model="materials.class_code" type="string" :selectedValue="$materials['class_code']"
                                        :options="$materialJenis" required="false" :action="$actionValue"
                                        clickEvent="openJenisDialogBox" buttonName="+" onChanged="generateNameTag" />
                                    <x-ui-dialog-box id="JenisDialogBox" title="Form Jenis" width="600px"
                                        height="400px" onOpened="openJenisDialogBox" onClosed="closeJenisDialogBox">
                                        <x-slot name="body">
                                            <x-ui-text-field label="Code" model="inputs_jenis.str1" type="text"
                                                :action="$actionValue" required="false" enabled="true"
                                                capslockMode="true" />
                                        </x-slot>
                                        <x-slot name="footer">
                                            <x-ui-button clickEvent="saveJenis" button-name="Save" loading="true"
                                                :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
                                        </x-slot>
                                    </x-ui-dialog-box>
                                </div>
                                <div class="row">
                                    <x-ui-text-field label="{{ $this->trans('size') }}" model="materials.size"
                                        type="text" :action="$actionValue" required="true" enabled="true"
                                        onChanged="generateName" capslockMode="true" />

                                    <x-ui-text-field-search label="{{ $this->trans('pattern') }}"
                                        model="materials.pattern" type="string" :selectedValue="$materials['pattern']" :options="$materialPattern"
                                        required="false" :action="$actionValue" onChanged="generateName"
                                        clickEvent="openPatternDialogBox" buttonName="+" />

                                    <x-ui-dialog-box id="patternDialogBox" title="Form Pattern" width="600px"
                                        height="400px" onOpened="openPatternDialogBox"
                                        onClosed="closePatternDialogBox">
                                        <x-slot name="body">
                                            <x-ui-text-field label="Code" model="inputs_pattern.str1"
                                                type="text" :action="$actionValue" required="true" enabled="true"
                                                capslockMode="true" />
                                        </x-slot>
                                        <x-slot name="footer">
                                            <x-ui-button clickEvent="savePattern" button-name="Save" loading="true"
                                                :action="$actionValue" cssClass="btn-primary" iconPath="save.svg" />
                                        </x-slot>
                                    </x-ui-dialog-box>
                                </div>
                                <div class="row">
                                    <x-ui-text-field label="{{ $this->trans('name') }}" model="materials.name"
                                        type="text" :action="$actionValue" onChanged="generateNameTag" required="true"
                                        enabled="true" capslockMode="true" />
                                </div>
                            </x-ui-padding>
                            <x-ui-padding>
                                <div class="material-info-container">
                                    <div class="photo-and-button-container">
                                        <!-- Photo Container -->
                                        <div class="multiple-photo-container">
                                            @forelse($capturedImages as $key => $image)
                                                <div class="photo-box">
                                                    <img src="{{ $image['url'] }}" alt="Captured Image"
                                                        class="photo-box-image">
                                                    <div class="image-close-button">
                                                        <x-ui-link-text type="close" :clickEvent="'deleteImage(' . $key . ')'"
                                                            class="btn btn-link" name="x" :action="$actionValue" />
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="photo-box empty">
                                                    <p>No Images Captured</p>
                                                </div>
                                            @endforelse
                                        </div>

                                        <div class="button-container">
                                            {{-- <x-ui-image-button :action="$actionValue"
                                                hideStorageButton="false"></x-ui-image-button>

                                            <x-ui-dialog-box id="storageDialogBox" :width="'2000px'"
                                                :height="'2000px'" onOpened="openStorageDialog"
                                                onClosed="closeStorageDialog">
                                                <x-slot name="body">
                                                    @livewire('base.master.gallery.storage-component', ['isComponent' => true])
                                                </x-slot>
                                            </x-ui-dialog-box> --}}
                                        </div>
                                    </div>
                                </div>
                            </x-ui-padding>
                        </x-ui-card>
                    </div>

                    <!-- Sidebar Section -->
                    <div class="col-md-4">
                        <x-ui-card title="Pricing">
                            <x-ui-padding>
                                <x-ui-dropdown-select label="{{ $this->trans('uom') }}" model="matl_uoms.matl_uom"
                                    :options="$materialUOM" type="number" :action="$actionValue" required="false"
                                    enabled="true" :selectedValue="$matl_uoms['matl_uom']" />
                                <x-ui-text-field label="{{ $this->trans('selling_price') }}"
                                    model="matl_uoms.selling_price" type="number" :action="$actionValue" required="true"
                                    enabled="true" :value="$matl_uoms['selling_price']" />
                                <x-ui-text-field label="{{ $this->trans('cost') }}" model="materials.cost"
                                    type="number" :action="$actionValue" required="false" enabled="false" />
                            </x-ui-padding>
                        </x-ui-card>
                        <x-ui-card title="Associations">
                            <x-ui-padding>
                                <x-ui-text-field label="{{ $this->trans('stock') }}" model="matl_uoms.qty_oh"
                                    type="text" :action="$actionValue" required="false" enabled="false"
                                    :value="$matl_uoms['qty_oh']" />
                                <x-ui-text-field label="{{ $this->trans('reserved') }}" model="matl_uoms.qty_fgi"
                                    type="text" :action="$actionValue" required="false" enabled="false"
                                    :value="$matl_uoms['qty_fgi']" />
                            </x-ui-padding>
                        </x-ui-card>
                        <x-ui-card title="Tagging">
                            <x-ui-padding>
                                <x-ui-text-field label="{{ $this->trans('tag') }}" model="materials.tag"
                                    type="text" :action="$actionValue" required="false" enabled="true"
                                    onChanged="generateNameTag" enabled="false" />
                            </x-ui-padding>
                        </x-ui-card>
                    </div>

                </div>
            </div>
        </x-ui-tab-view-content>

        <x-ui-footer>
            @if (
                $actionValue !== 'Create' &&
                    (!$object instanceof App\Models\SysConfig1\ConfigUser || auth()->user()->id !== $object->id))
                @if (isset($permissions['delete']) && $permissions['delete'])
                    <div style="padding-right: 10px;">
                        @include('layout.customs.buttons.disable')
                    </div>
                @endif
            @endif
            <div>
                @include('layout.customs.buttons.save')
            </div>
        </x-ui-footer>
        {{-- <x-ui-footer>
            @include('layout.customs.buttons.save')
        </x-ui-footer> --}}
    </x-ui-page-card>
</div>
