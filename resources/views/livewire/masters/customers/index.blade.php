<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div id="kt_content_container" class="container-xxl mb-5">
        <div class="card shadow-sm">
            <form wire:submit.prevent='{{ $is_edit_mode ? 'update' : 'store' }}' class="form w-100">
                <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#kt_docs_card_collapsible">
                    <h3 class="card-title">{{ $is_edit_mode ? "Sunting Penyuplai {$customer?->name}" : "Buat Customer Baru" }}</h3>
                    <div class="card-toolbar rotate-180">
                        <i class="bi bi-arrow-bar-down"></i>
                    </div>
                </div>
                <div id="kt_docs_card_collapsible" class="collapse" wire:ignore.self>
                    <div class="card-body">

                        <div class="mb-10">
                            <label class="required form-label">Nama Pelanggan</label>
                            <input wire:model.debounce.1s="inputs.name" type="text" class="form-control @error('inputs.name') is-invalid @enderror" placeholder="CV Maju Unggul"/>
                            @error('inputs.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Alamat</label>
                            <input wire:model.debounce.1s="inputs.address" type="text" class="form-control @error('inputs.address') is-invalid @enderror" placeholder="Jl.KH Wahid Asyim No.10"/>
                            @error('inputs.address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">City</label>
                            <input wire:model.debounce.1s="inputs.city" type="text" class="form-control @error('inputs.city') is-invalid @enderror" placeholder="Jombang"/>
                            @error('inputs.city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- <div class="mb-10">
                            <label class="required form-label">NPWP</label>
                            <input wire:model.debounce.1s="inputs.npwp" type="number" class="form-control @error('inputs.npwp') is-invalid @enderror" placeholder="123123445656"/>
                            @error('inputs.npwp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div> --}}

                        <div class="mb-10">
                            <label class="required form-label">Nama Kontak</label>
                            <input wire:model.debounce.1s="inputs.contact_name" type="text" class="form-control @error('inputs.contact_name') is-invalid @enderror" placeholder="Christopher"/>
                            @error('inputs.contact_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Nomor Kontak</label>
                            <input wire:model.debounce.1s="inputs.contact_number" type="number" class="form-control @error('inputs.contact_number') is-invalid @enderror" placeholder="8123456789"/>
                            @error('inputs.contact_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Email</label>
                            <input wire:model.debounce.1s="inputs.email" type="email" class="form-control @error('inputs.email') is-invalid @enderror" placeholder="pos-online@gmail.com"/>
                            @error('inputs.email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>
                    <div class="card-footer">
                        @include('layout.customs.button-submit')
                        @if ($is_edit_mode)
                            <button type="button" class="btn btn-secondary" wire:click="$emit('master_customer_edit_mode',false)"> {{ __('generic.button_cancel') }}</button>
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
                        @livewire('masters.customers.index-data-table')
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('layout.customs.modal-delete', ['destroy_listener' => 'master_customer_destroy'])
</div>
