<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div id="kt_content_container" class="container-xxl mb-5">
        <div class="card shadow-sm">
            <form wire:submit.prevent='{{ $is_edit_mode ? 'update' : 'store' }}' class="form w-100">
                <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#kt_docs_card_collapsible">
                    <h3 class="card-title">{{ $is_edit_mode ? "Sunting Transfer {$transfer?->name}" : "Buat Transfer Baru" }}</h3>
                    <div class="card-toolbar rotate-180">
                        <i class="bi bi-arrow-bar-down"></i>
                    </div>
                </div>
                <div id="kt_docs_card_collapsible" class="collapse show">
                    <div class="card-body">

                        <div class="mb-10">
                            <label class="required form-label">Tanggal Transfer</label>
                            <input wire:model.debounce.1s="inputs.transfer_date" type="date" class="form-control @error('inputs.transfer_date') is-invalid @enderror" placeholder="CV Maju Unggul"/>
                            @error('inputs.transfer_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        
                        <div class="mb-10">
                            <label class="required form-label">Warehouse Asal</label>
                            <select class="form-select @error('inputs.origin_id') is-invalid @enderror" aria-label="Select an option"  wire:model.defer="inputs.origin_id">
                                <option selected value="null" >-- Choose One --</option>
                                @foreach($warehouses as $warehouse)
                                <option value="{{$warehouse->id}}">{{$warehouse->name. " - " . ($warehouse->is_out_purpose ? "Warehouse Pengeluaran" : "Warehouse Penerimaan")}}</option>
                                @endforeach
                            </select>
                            @error('inputs.origin_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Warehouse Tujuan</label>
                            <select class="form-select @error('inputs.destination_id') is-invalid @enderror" aria-label="Select an option"  wire:model.defer="inputs.destination_id">
                                <option selected value="null" >-- Choose One --</option>
                                @foreach($warehouses as $warehouse)
                                <option value="{{$warehouse->id}}">{{$warehouse->name. " - " . ($warehouse->is_out_purpose ? "Warehouse Pengeluaran" : "Warehouse Penerimaan")}}</option>
                                @endforeach
                            </select>
                            @error('inputs.destination_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>
                    <div class="card-footer">
                        @include('layout.customs.button-submit')
                        @if ($is_edit_mode)
                            <button type="button" class="btn btn-secondary" wire:click="$emit('transaction_transfer_edit_mode',false)"> {{ __('generic.button_cancel') }}</button>
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
                        @livewire('transactions.transfers.index-data-table')
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('layout.customs.modal-delete', ['destroy_listener' => 'transaction_transfer_destroy'])
</div>
