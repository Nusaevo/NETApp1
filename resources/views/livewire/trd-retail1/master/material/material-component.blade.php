<div>
    @php
        use App\Models\TrdRetail1\Master\Material;
    @endphp

    <x-ui-page-card isForm="true" title="{{ $this->trans($actionValue) }} {!! $menuName !!}"
        status="{{ $status }}">

        {{-- Tabs --}}
        @if ($actionValue === 'Create')
            <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>
        @elseif($actionValue !== 'Create')
            <x-ui-tab-view id="myTab" tabs="general"> </x-ui-tab-view>
        @endif

        <x-ui-tab-view-content id="tabMaterial" class="tab-content">
            <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                <div class="row mt-4">
                    <!-- Main Form Section -->
                    <div class="col-md-12">
                        <x-ui-card title="{{ $this->trans('images') }}">
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
                                                            class="btn btn-link" name="x" :action="$actionValue" />
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="photo-box empty">
                                                    <p>{{ $this->trans('no_images_captured') }}</p>
                                                </div>
                                            @endforelse
                                        </div>
                                        <div class="button-container">
                                            <x-ui-image-button hideStorageButton="false"></x-ui-image-button>
                                        </div>
                                        <x-ui-dialog-box id="storageDialogBox" :width="'2000px'" :height="'2000px'"
                                            onOpened="openStorageDialog" onClosed="closeStorageDialog">
                                            <x-slot name="body">
                                                @livewire('base.master.gallery.storage-component', ['isComponent' => true])
                                            </x-slot>
                                        </x-ui-dialog-box>
                                    </div>
                                </div>
                            </x-ui-padding>
                        </x-ui-card>

                        <x-ui-card title="{{ $this->trans('main_information') }}">
                            <div class="row">
                                <x-ui-dropdown-select label="{{ $this->trans('category') }}" model="materials.category"
                                    :options="$materialCategories" :enabled="$panelEnabled" required="true" onChanged="onCategoryChanged" />
                                <x-ui-text-field label="{{ $this->trans('code') }}" model="materials.code"
                                    type="text" :action="$actionValue" required="true" enabled="false"
                                    clickEvent="getMatlCode" buttonName="{{ $this->trans('get_code') }}"
                                    :buttonEnabled="$panelEnabled" />
                                {{-- <x-ui-text-field label="{{ $this->trans('barcode') }}" model="matl_uoms.barcode"
                                    type="text" :action="$actionValue" enabled="false" /> --}}
                            </div>

                            <div class="row">
                                <x-ui-text-field label="{{ $this->trans('brand') }}" model="materials.brand"
                                    type="text" :action="$actionValue" required="true" enabled="true"
                                    onChanged="generateName" capslockMode="true" />
                                <x-ui-text-field label="{{ $this->trans('class_code') }}" model="materials.class_code"
                                    type="text" :action="$actionValue" required="false" enabled="true"
                                    onChanged="generateName" capslockMode="true" />
                            </div>

                            <div class="row">
                                <div class="col-md-1">
                                    <x-ui-text-field label="{{ $this->trans('seq') }}" model="materials.seq"
                                        type="number" :action="$actionValue" required="true" enabled="true" />
                                </div>
                                <div class="col-md-11">

                                    <div class="row">
                                        <x-ui-text-field label="{{ $this->trans('color_code') }}"
                                            model="materials.color_code" type="text" :action="$actionValue"
                                            required="false" enabled="true" onChanged="generateName"
                                            capslockMode="true" />
                                        <x-ui-text-field label="{{ $this->trans('color_name') }}"
                                            model="materials.color_name" type="text" :action="$actionValue"
                                            required="false" enabled="true" capslockMode="true" />

                                        <x-ui-text-field label="{{ $this->trans('size') }}" model="materials.size"
                                            type="text" :action="$customActionValue" />
                                    </div>
                                </div>

                            </div>
                            <div class="row">
                                <x-ui-text-field label="{{ $this->trans('name') }}" model="materials.name"
                                    type="text" capslockMode="true" :action="$actionValue" required="true"
                                    enabled="true" />
                            </div>
                            <div class="row">
                                <x-ui-text-field label="{{ $this->trans('descr') }}" model="materials.descr"
                                    type="textarea" :action="$customActionValue" />
                            </div>
                            {{-- <div class="row">
                                <x-ui-text-field label="{{ $this->trans('buying_price') }}"
                                    model="materials.buying_price" type="number" :action="$actionValue" required="false"
                                    enabled="true" />
                            </div> --}}

                            <div class="row">
                                {{-- <x-ui-text-field label="{{ $this->trans('selling_price') }}"
                                    model="matl_uoms.selling_price" type="number" :action="$actionValue" required="false"
                                    :enabled="$panelEnabled" enabled="false" /> --}}
                                {{-- <x-ui-text-field label="{{ $this->trans('cogs') }}" model="materials.cogs"
                                    type="number" :action="$actionValue" required="true" enabled="true" /> --}}
                                {{-- <x-ui-dropdown-select label="{{ $this->trans('uom') }}" model="materials.uom"
                                    :options="$materialUOM" type="number" :action="$actionValue" required="false"
                                    enabled="true" /> --}}
                                {{-- <x-ui-text-field label="{{ $this->trans('stock') }}" model="materials.stock"
                                    type="text" :action="$actionValue" required="false" enabled="false" /> --}}

                            </div>
                            <div class="row">
                                {{-- <x-ui-text-field label="{{ $this->trans('tag') }}" model="materials.tag"
                                    type="text" :action="$actionValue" required="false" enabled="false" /> --}}

                                <div class="row">
                                    <x-ui-text-field label="{{ $this->trans('remarks') }}" model="materials.remarks"
                                        type="textarea" :action="$customActionValue" />
                                </div>
                            </div>
                        </x-ui-card>
                    </div>
                </div>
                <x-ui-card>
                    <x-ui-table id="Table" title="{!! $this->trans('uom_list') !!}">
                        <x-slot name="headers">
                            <th style="text-align: center; width: 10px;">{{ $this->trans('seq') }}</th>
                            <th style="text-align: center; width: 100px;">{{ $this->trans('base_uom') }}</th>
                            <th style="text-align: center; width: 10px;">{{ $this->trans('reff_uom') }}</th>
                            <th style="text-align: center; width: 100px;">{{ $this->trans('reff_factor') }}</th>
                            <th style="text-align: center; width: 10px;">{{ $this->trans('base_factor') }}</th>
                            <th style="text-align: center; width: 150px;">{{ $this->trans('barcode') }}</th>
                            @if ($actionValue == 'Edit')
                                <th style="text-align: center; width: 150px;">{{ $this->trans('buying_price') }}
                                </th>
                            @endif
                            <th style="text-align: center; width: 150px;">{{ $this->trans('selling_price') }}
                            </th>
                            <th style="text-align: center; width: 150px;">{{ $this->trans('stock') }}</th>
                            <th style="text-align: center; width: 50px;">{{ $this->trans('actions') }}</th>
                        </x-slot>

                        <x-slot name="rows">
                            @foreach ($input_details as $key => $input_detail)
                                <tr wire:key="list{{ $key }}">
                                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                                    <td>
                                        <x-ui-dropdown-select model="input_details.{{ $key }}.reff_uom"
                                            :options="$materialUOM" required="true" :action="$actionValue" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_details.{{ $key }}.reff_factor"
                                            type="number" required="true" :action="$actionValue" />
                                    </td>
                                    <td>
                                        <x-ui-dropdown-select model="input_details.{{ $key }}.matl_uom"
                                            :options="$materialUOM" required="true" :action="$actionValue" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_details.{{ $key }}.base_factor"
                                            type="number" required="true" :action="$actionValue" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_details.{{ $key }}.barcode"
                                            type="text" required="false" enabled="true" :action="$actionValue" />
                                    </td>
                                    @if ($actionValue == 'Edit')
                                        <td style="text-align: center;">
                                            <x-ui-text-field model="input_details.{{ $key }}.buying_price"
                                                type="number" required="false" :action="$actionValue" />
                                        </td>
                                    @endif
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_details.{{ $key }}.selling_price"
                                            type="number" required="false" :action="$actionValue" />
                                    </td>
                                    <td style="text-align: center;">
                                        <x-ui-text-field model="input_details.{{ $key }}.qty_oh"
                                            type="number" required="false" enabled="false" :action="$actionValue" />
                                    </td>
                                    <td style="text-align: center;">
                                        @php
                                            $uom = $input_detail;
                                            $isActive = empty($uom['deleted_at']);
                                        @endphp
                                           @if ($actionValue === 'Edit')
                                           <!-- Toggle Active/Inactive Button -->
                                           <x-ui-button
                                               clickEvent="toggleUomStatus({{ $key }})"
                                               cssClass="btn-sm {{ $isActive ? 'btn-danger' : 'btn-success' }}"
                                               iconPath="{{ $isActive ? 'disable.svg' : 'enable.svg' }}"
                                               button-name="{{ $isActive ? __('Non Active') : __('Activate') }}"
                                               :action="$actionValue"
                                           />

                                           <!-- Print Barcode Button -->
                                           <x-ui-button
                                               clickEvent="printBarcode({{ $key }})"
                                               cssClass="btn-secondary btn-sm ml-2"
                                               iconPath="print.svg"
                                               button-name="{{ __('Print') }}"
                                               :action="$actionValue"
                                           />
                                           @endif
                                    </td>

                                </tr>
                            @endforeach
                        </x-slot>
                        @if ($actionValue == 'Edit')
                        @endif
                        <x-slot name="button">
                            <x-ui-button clickEvent="addItem" cssClass="btn btn-primary" iconPath="add.svg"
                                button-name="{{ $this->trans('add') }}" :action="$actionValue" />
                        </x-slot>
                    </x-ui-table>
                </x-ui-card>

            </div>
        </x-ui-tab-view-content>

        <x-ui-footer>
            @if (!$isComponent && $actionValue == 'Edit')
                @include('layout.customs.buttons.disable')
            @endif
            <x-ui-button clickEvent="Save" button-name="{{ $this->trans('save') }}" loading="true"
                :action="$customActionValue" cssClass="btn-primary" iconPath="save.svg" />
            @if ($isComponent)
                <x-ui-button clickEvent="addPurchaseOrder" button-name="{{ $this->trans('add_item') }}"
                    loading="true" :action="$actionValue" cssClass="btn-primary" iconPath="add.svg" />
            @endif
        </x-ui-footer>
    </x-ui-page-card>
</div>
