<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div id="kt_content_container" class="container-xxl mb-5">
        <div class="card shadow-sm">
            <form wire:submit.prevent='{{ $is_edit_mode ? 'update' : 'store' }}' class="form w-100">
                <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#kt_docs_card_collapsible">
                    <h3 class="card-title">{{ $is_edit_mode ? "Sunting Gudang {$warehouse?->name}" : "Buat Gudang Baru" }}</h3>
                    <div class="card-toolbar rotate-180">
                        <i class="bi bi-arrow-bar-down"></i>
                    </div>
                </div>
                <div id="kt_docs_card_collapsible" class="collapse" wire:ignore.self>
                    <div class="card-body">

                        <div class="mb-10">
                            <label class="required form-label">Nama Gudang</label>
                            <input wire:model.debounce.1s="inputs.name" type="text" class="form-control @error('inputs.name') is-invalid @enderror" placeholder="CV Maju Unggul"/>
                            @error('inputs.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-10">
                            <div class="form-check form-check-custom form-check-solid">
                                <input wire:model.lazy="inputs.purpose" type="radio" class="form-check-input @error('inputs.purpose') is-invalid @enderror" value="is_out"/>
                                <label class="form-check-label" for="flexRadioDefault">
                                    Gudang Pengeluaran ( Toko - Customer )
                                </label>
                            </div>
                            <div class="form-check form-check-custom form-check-solid mt-3">
                                <input wire:model.lazy="inputs.purpose" type="radio" class="form-check-input @error('inputs.purpose') is-invalid @enderror" value="is_receive"/>
                                <label class="form-check-label" for="flexRadioDefault">
                                    Gudang Penerimaan (Gudang - Toko)
                                </label>
                            </div>
                        </div>

                    </div>
                    <div class="card-footer">
                        @include('layout.customs.button-submit')
                        @if ($is_edit_mode)
                            <button type="button" class="btn btn-secondary" wire:click="$emit('master_warehouse_edit_mode',false)"> {{ __('generic.button_cancel') }}</button>
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
                        @livewire('masters.warehouses.index-data-table')
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('layout.customs.modal-delete', ['destroy_listener' => 'master_warehouse_destroy'])
</div>
