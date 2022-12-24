<div>
    <div id="kt_content_container" class="container-xxl mb-5">
        <div class="card shadow-sm">
            <form wire:submit.prevent='{{ $is_edit_mode ? 'update' : 'store' }}' class="form w-100">
                <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#kt_docs_card_collapsible">
                    <h3 class="card-title">{{ $is_edit_mode ? "Sunting Item Unit {$item?->name}" : "Buat Item Unit {$item?->name}" }}</h3>
                    <div class="card-toolbar rotate-180">
                        <i class="bi bi-arrow-bar-down"></i>
                    </div>
                </div>
                <div id="kt_docs_card_collapsible" class="collapse show">
                    <div class="card-body">
                        <div class="mb-10">
                            <label class="form-label">Item Name</label>
                            <select class="form-select @error('inputs.item_id') is-invalid @enderror" wire:model.lazy="inputs.item_id" >
                            <option value="{{$item->id}}">{{$item->name}}</option>
                            </select>
                            @error('inputs.item_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Unit</label>
                            <select class="form-select @error('inputs.unit_from') is-invalid @enderror" wire:model.lazy="inputs.unit_from">
                                <option selected value="" >-- Pilih Salah Satu --</option>
                                @foreach($unit_from as $unit)
                                    <option value="{{$unit->id}}">{{$unit->name}}</option>
                                @endforeach
                            </select>
                            @error('inputs.unit_from') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-10">
                            <label class="form-label">Multiplier</label>
                            <input wire:model.debounce.1s="inputs.multiplier" type="Number" class="form-control @error('inputs.multiplier') is-invalid @enderror"/>
                            @error('inputs.multiplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-10">
                            <label class="form-label">To</label>
                            <select class="form-select @error('inputs.unit_to') is-invalid @enderror" wire:model.lazy="inputs.unit_to">
                                <option selected value="" >-- Pilih Salah Satu --</option>
                                @foreach($unit_to as $unit)
                                    <option value="{{$unit->id}}">{{$unit->name}}</option>
                                @endforeach
                            </select>
                            @error('inputs.unit_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        @include('layout.customs.button-submit')
                        @if ($is_edit_mode)
                            <button type="button" class="btn btn-secondary" wire:click="$emit('master_item_unit_detail_edit_mode',false)"> {{ __('generic.button_cancel') }}</button>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="post d-flex flex-column-fluid" id="kt_post">
        <div id="kt_content_container" class="container-xxl">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive mt-10">
                        <table id="tbl" class="table table-striped table-hover gy-7 gs-7">
                            <thead>
                                <tr class="fw-bold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                                    <th class="min-w-10px">From</th>
                                    <th class="min-w-10px">Multiplier</th>
                                    <th class="min-w-10px">To</th>
                                    <th class="min-w-10px">Action</th>
                                </tr>
                            </thead>
                            <tbody >
                                <div>
                                    @foreach ($list_item_unit as $item_unit)
                                    <tr>
                                        <td>{{ $item_unit->from_unit->name }}</td>
                                        <td>{{ $item_unit->multiplier }}</td>
                                        <td>{{ $item_unit->to_unit->name }} </td>
                                        <td>
                                            <a href="#"  wire:click="$emit('master_item_unit_detail_edit', {{ $item_unit->id}})" class="btn btn-primary btn-sm"><i class="bi bi-pencil-square fs-2 me-2"></i></a>
                                            {{--  <a href="#" wire:click="$emit('master_item_unit_detail_delete',  {{ $item_unit->id}})" class="btn btn-primary btn-sm"><i class="bi bi-trash-fill fs-2 me-2"></i></a></td>  --}}
                                        </tr>
                                    @endforeach
                                </div>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('layout.customs.modal-delete', ['destroy_listener' => 'master_item_unit_detail_destroy'])
</div>
