<div>
    <x-ui-dialog-box id="{{ $dialogId }}" title="{{ $title }}" width="{{ $width }}" height="{{ $height }}"
        onOpened="open{{ ucfirst($dialogId) }}" onClosed="close{{ ucfirst($dialogId) }}">
        <x-slot name="body">
            <!-- Search and Filter Section -->
            <div class="row">
                <x-ui-text-field type="text" label="Search Code/Name" model="searchTerm"
                    required="false" action="Create" enabled="true" clickEvent="" buttonName="" />

                @if($enableFilters)
                    <x-ui-dropdown-search
                        label="Category"
                        model="filterCategory"
                        query="SELECT str1, str2 FROM config_consts WHERE const_group='MMATL_CATEGL1' AND deleted_at IS NULL"
                        optionValue="str1"
                        optionLabel="{str2}"
                        placeHolder="Select category..."
                        type="string" />
                @endif
            </div>

            @if($enableFilters)
                <div class="row">
                    <x-ui-dropdown-search
                        label="Brand"
                        model="filterBrand"
                        query="SELECT str1, str2 FROM config_consts WHERE const_group='MMATL_BRAND' AND deleted_at IS NULL"
                        optionValue="str1"
                        optionLabel="{str2}"
                        placeHolder="Select brand..."
                        type="string" />
                    <x-ui-dropdown-search
                        label="Type"
                        model="filterType"
                        query="SELECT DISTINCT class_code FROM materials WHERE class_code IS NOT NULL AND class_code != '' AND deleted_at IS NULL"
                        optionValue="class_code"
                        optionLabel="{class_code}"
                        placeHolder="Select type..."
                        type="string" />
                </div>
            @endif

            <!-- Search Button -->
            <div class="row mt-3">
                <div class="col-md-12 d-flex justify-content-end">
                    <x-ui-button clickEvent="searchMaterials" cssClass="btn btn-primary" button-name="Search" />
                </div>
            </div>

            <!-- Results Table -->
            <x-ui-table id="materialsSelectionTable" padding="0px" margin="0px" height="400px">
                <x-slot name="headers">
                    <th class="min-w-100px">
                        @if($multiSelect)
                            Select
                        @else
                            Code
                        @endif
                    </th>
                    <th class="min-w-100px">Image</th>
                    <th class="min-w-150px">Name</th>
                    <th class="min-w-100px">Buying Price</th>
                    <th class="min-w-100px">Selling Price</th>
                </x-slot>

                <x-slot name="rows">
                    @if (empty($materialList))
                        <tr>
                            <td colspan="5" class="text-center text-muted">No Data Found</td>
                        </tr>
                    @else
                        @foreach ($materialList as $index => $material)
                            <tr wire:key="row-{{ $index }}-material-{{ $material->id }}">
                                <td style="text-align: center;">
                                    @if($multiSelect)
                                        <input type="checkbox"
                                               wire:click="selectMaterial({{ $material->id }})"
                                               @if($this->isSelected($material->id)) checked @endif
                                               class="form-check-input">
                                    @else
                                        <button type="button"
                                                wire:click="selectMaterial({{ $material->id }})"
                                                class="btn btn-sm btn-outline-primary">
                                            {{ $material->code }}
                                        </button>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    @if (isset($material->Attachment) && $material->Attachment->first())
                                        <img src="{{ $material->Attachment->first()->getUrl() }}" alt="Image"
                                            style="width: 50px; height: 50px;">
                                    @else
                                        <span class="text-muted">No Image</span>
                                    @endif
                                </td>
                                <td>{{ $material->name }}</td>
                                <td style="text-align: right;">{{ rupiah($material->buying_price ?? 0) }}</td>
                                <td style="text-align: right;">{{ rupiah($material->selling_price ?? 0) }}</td>
                            </tr>
                        @endforeach
                    @endif
                </x-slot>

                <x-slot name="footer">
                    <div class="d-flex justify-content-between">
                        <div>
                            @if($multiSelect && !empty($selectedMaterials))
                                <span class="badge bg-info">{{ count($selectedMaterials) }} materials selected</span>
                            @endif
                        </div>
                        <div>
                            <x-ui-button clickEvent="confirmSelection" cssClass="btn btn-primary" button-name="Confirm Selection"
                                         loading="true" action="Create" />
                        </div>
                    </div>
                </x-slot>
            </x-ui-table>
        </x-slot>
    </x-ui-dialog-box>
</div>
