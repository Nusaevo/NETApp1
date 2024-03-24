<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div id="kt_content_container" class="container-xxl mb-5">
        <div class="card shadow-sm">
            <form wire:submit.prevent='{{ $is_edit_mode ? 'update' : 'store' }}' class="form w-100">
                <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#kt_docs_card_collapsible">
                    <h3 class="card-title">{{ $is_edit_mode ? "Sunting Detail Transfer {$transfer?->id} {$transfer?->transfer_date}" : "Buat Detail Transfer {$transfer?->id} {$transfer?->transfer_date}" }}</h3>
                    <div class="card-toolbar rotate-180">
                        <i class="bi bi-arrow-bar-down"></i>
                    </div>
                </div>
                <div id="kt_docs_card_collapsible" class="collapse show">
                    <div class="card-body">
                        <div class="mb-10">
                            <label class="required form-label">Item</label>
                            <select class="form-select @error('itemId') is-invalid @enderror" aria-label="Select an option"  wire:model="itemId">
                                <option selected value="null" >-- Choose One --</option>
                                @foreach($items as $item)
                                <option value="{{$item->id}}">{{$item->name}}</option>
                                @endforeach
                            </select>
                            @error('itemId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-10">
                            <label class="required form-label">Unit</label>
                            <select class="form-select @error('inputs.unit_id') is-invalid @enderror" aria-label="Select an option"  wire:model.defer="inputs.unit_id">
                                @if(empty($units))
                                    <option selected value="null" disabled >-- Choose One --</option>
                                @endif
                                @foreach($units as $unit)
                                <option value="{{$unit->id}}">{{$unit->name}}</option>
                                @endforeach
                            </select>
                            @error('inputs.unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Qty</label>
                            <input wire:model.debounce.1s="inputs.qty" type="number" class="form-control @error('inputs.qty') is-invalid @enderror"/>
                            @error('inputs.qty') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Qty Defect</label>
                            <input wire:model.debounce.1s="inputs.qty_defect" type="number" class="form-control @error('inputs.qty_defect') is-invalid @enderror"/>
                            @error('inputs.qty_defect') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Kondisi</label>
                            <select class="form-select @error('inputs.remark') is-invalid @enderror" aria-label="Select an option"  wire:model.defer="inputs.remark">
                                <option selected value="null" >-- Choose One --</option>
                                @foreach($remarks as $remark)
                                <option value="{{$remark}}">{{$remark}}</option>
                                @endforeach
                            </select>
                            @error('inputs.remark') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="card-footer">
                        @include('layout.customs.button-submit')
                        @if ($is_edit_mode)
                            <button type="button" class="btn btn-secondary" wire:click="$emit('transaction_transfer_show_edit_mode',false)"> {{ __('generic.button_cancel') }}</button>
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
                    <div class="table-responsive">
                        @livewire('transactions.transfers.show-data-table')
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('layout.customs.modal-delete', ['destroy_listener' => 'transaction_transfer_show_destroy'])
</div>
