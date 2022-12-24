<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div id="kt_content_container" class="container-xxl mb-5">
        <div class="card shadow-sm">
            <form wire:submit.prevent='{{ $is_edit_mode ? 'update' : 'store' }}' class="form w-100">
                <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#kt_docs_card_collapsible">
                    <h3 class="card-title">{{ $is_edit_mode ? "Sunting Penyuplai {$supplier?->name}" : "Buat Penyuplai Baru" }}</h3>
                    <div class="card-toolbar rotate-180">
                        <i class="bi bi-arrow-bar-down"></i>
                    </div>
                </div>
                <div id="kt_docs_card_collapsible" class="collapse" wire:ignore.self>
                    <div class="card-body">

                        <div class="mb-10">
                            <label class="required form-label">Nama Penyuplai</label>
                            <input wire:model.debounce.1s="inputs.name" type="text" class="form-control @error('inputs.name') is-invalid @enderror" placeholder="CV Maju Unggul"/>
                            @error('inputs.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-10">
                            <label class="required form-label">Nama</label>
                            <input wire:model.debounce.1s="inputs.contact_name" type="text" class="form-control @error('inputs.contact_name') is-invalid @enderror" placeholder="Christopher"/>
                            @error('inputs.contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Telepon</label>
                            <input wire:model.debounce.1s="inputs.contact_number" type="number" class="form-control @error('inputs.contact_number') is-invalid @enderror" placeholder="8123456789"/>
                            @error('inputs.contact_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>
                    <div class="card-footer">
                        @include('layout.customs.button-submit')
                        @if ($is_edit_mode)
                            <button type="button" class="btn btn-secondary" wire:click="$emit('master_supplier_edit_mode',false)"> {{ __('generic.button_cancel') }}</button>
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
                        @livewire('masters.suppliers.index-data-table')
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('layout.customs.modal-delete', ['destroy_listener' => 'master_supplier_destroy'])
</div>
