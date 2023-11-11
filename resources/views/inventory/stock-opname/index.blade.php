<div>
    <div>
        @include('layout.customs.notification')
    </div>
    <div id="kt_content_container" class="container-xxl mb-5">
        <div class="card shadow-sm">
            <form class="form w-100">
                <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#kt_docs_card_collapsible">
                    <h3 class="card-title">Stock Opname</h3>
                    <div class="card-toolbar rotate-180">
                        <i class="bi bi-arrow-bar-down"></i>
                    </div>
                </div>
                <div id="kt_docs_card_collapsible" class="collapse" wire:ignore.self>
                    <div class="card-body">
                        <div class="mb-10">
                            <label class="required form-label">Pilih Gudang</label>
                            <select class="form-select @error('inputs.warehouse_id') is-invalid @enderror" wire:model.defer="inputs.warehouse_id">
                                <option selected value="" >Semua</option>
                                @foreach($warehouse as $warehouse)
                                    <option value="{{$warehouse->id}}">{{$warehouse->name}}</option>
                                @endforeach
                            </select>
                            @error('inputs.warehouse_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-10">
                            <label class="required form-label">Pilih Kategori Barang</label>
                            <select class="form-select @error('inputs.category_item_id') is-invalid @enderror" wire:model.defer="inputs.category_item_id">
                                <option selected value="" >Semua</option>
                                @foreach($category as $category)
                                    <option value="{{$category->id}}">{{$category->name}}</option>
                                @endforeach
                            </select>
                            @error('inputs.category_item_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-10">
                            <label class="required form-label">Cari Nama Barang</label>
                            <input wire:model.defer="inputs.name" type="text" class="form-control @error('inputs.name') is-invalid @enderror"/>
                            @error('inputs.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="card-footer">
                        <button wire:loading.remove type="{{ $button_type ?? 'button' }}" wire:click="search" class="btn btn-primary me-10" wire:loading.attr="disabled"> {{ $button_text ?? 'Search' }} </button>
                        <span wire:loading wire:target="search" class="indicator-progress">
                            {{ $button_text_loading ?? 'Harap Tunggu...' }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                        </span>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <form wire:submit.prevent='{{ $is_edit_mode ? 'update' : 'store' }}' class="form w-100">
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive mt-10">
                        <table id="tbl" class="table table-striped table-hover gy-7 gs-7">
                            <thead>
                                <tr class="fw-bold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                                    {{--  <th class="min-w-5px"></th>  --}}
                                    <th class="min-w-10px">Gudang</th>
                                    <th class="min-w-10px">Barang</th>
                                    <th class="min-w-100px">Qty</th>
                                    <th class="min-w-100px">Qty Defect</th>
                                    <th class="min-w-10px">Kategori</th>
                                    <th class="min-w-10px"></th>
                                </tr>
                            </thead>
                            <tbody >
                                <div>
                                @forelse($item_warehouses  as $key => $item_warehouses)
                                    <tr wire:key="{{ $key }}">
                                        {{--  <td class="border">
                                            <input type="checkbox" value="true" class="form-check-input" wire:model.defer="selected_items.{{ $item_warehouses->id }}">
                                        </td>  --}}
                                        <td class="border">{{ $item_warehouses->warehouse_name }}</td>
                                        <td class="border">{{ $item_warehouses->item_name}} - {{ $item_warehouses->unit_name}}</td>
                                        {{-- <td class="border">{{ $item_warehouses->qty }}</td>
                                        <td class="border">{{ $item_warehouses->qty_defect }}</td> --}}
                                        <td class="border">
                                            <div class="input-form @error('item_warehouse_qty.{{ $item_warehouses->id }}') has-error @enderror">
                                                <input type="number" wire:model.defer='item_warehouse_qty.{{ $item_warehouses->id }}' class="form-control" min="0" max="9999999999.99">
                                                @error('item_warehouse_qty.{{ $item_warehouses->id }}')
                                                    <div class="pristine-error text-primary-3 mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </td>
                                        <td class="border">
                                            <div class="input-form @error('item_warehouse_qty_defect.{{ $item_warehouses->id }}') has-error @enderror">
                                                <input type="number" wire:model.defer='item_warehouse_qty_defect.{{ $item_warehouses->id }}' class="form-control" min="0" max="9999999999.99">
                                                @error('item_warehouse_qty_defect.{{ $item_warehouses->id }}')
                                                    <div class="pristine-error text-primary-3 mt-1">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </td>
                                        <td class="border">{{ $item_warehouses->category_name }}</td>
                                    </tr>
                                @empty

                                    <tr>
                                        <td colspan='6' align="center">Tidak ada data ditemukan (tolong paling tidak pilih salah satu filter!)</td>
                                    </tr>
                                @endforelse
                                </div>
                            </tbody>
                        </table>
                        <div class="card-footer">
                            @include('layout.customs.button-submit')
                            @if ($is_edit_mode)
                                <button type="button" class="btn btn-secondary" wire:click="$emit('master_item_warehouse_edit_mode',false)"> {{ __('generic.button_cancel') }}</button>
                            @endif
                        </div>
                        {{--  <div class="card-footer">
                            <button wire:loading.remove type="{{ $button_type ?? 'submit' }}" class="btn btn-primary me-10"> {{ $button_text ?? 'Simpan' }} </button>
                            <button wire:loading type="button" class="btn btn-primary" data-kt-indicator="on">
                                <span class="indicator-progress">
                                    {{ $button_text_loading ?? 'Harap Tunggu...' }} <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>  --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
    </form>

    @include('layout.customs.modal-delete', ['destroy_listener' => 'master_item_unit_detail_destroy'])
</div>
