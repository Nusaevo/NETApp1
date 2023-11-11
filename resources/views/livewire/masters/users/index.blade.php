<div>
    <div>
        @include('layout.customs.notification')
    </div>

    <div id="kt_content_container" class="container-xxl mb-5">
        <div class="card shadow-sm">
            <form wire:submit.prevent='{{ $is_edit_mode ? 'update' : 'store' }}' class="form w-100">
                <div class="card-header collapsible collapsed cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#kt_docs_card_collapsible" wire:ignore.self>
                    <h3 class="card-title">{{ $is_edit_mode ? "Sunting Akun {$user?->name}" : "Buat Akun Baru" }}</h3>
                    <div class="card-toolbar rotate-180">
                        <i class="bi bi-arrow-bar-down"></i>
                    </div>
                </div>
                <div id="kt_docs_card_collapsible" class="collapse" wire:ignore.self>
                    <div class="card-body">

                        <div class="mb-10">
                            <label class="required form-label">Nama Akun</label>
                            <input wire:model.debounce.1s="inputs.name" type="text" class="form-control @error('inputs.name') is-invalid @enderror" placeholder=""/>
                            @error('inputs.name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Kegunaan Akun</label>
                            <select class="form-select @error('inputs.purpose') is-invalid @enderror" wire:model.debounce.1s="inputs.purpose">
                                @foreach ($purposes as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                            @error('inputs.purpose') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Email Akun</label>
                            <input wire:model.debounce.1s="inputs.email" type="email" class="form-control @error('inputs.email') is-invalid @enderror" placeholder=""/>
                            @error('inputs.email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">{{ $is_edit_mode ? "Reset Sandi Baru (isi hanya saat ingin mereset)" : "Sandi Baru" }}</label>
                            <input wire:model.debounce.1s="inputs.password" type="password" class="form-control @error('inputs.password') is-invalid @enderror" placeholder=""/>
                            @error('inputs.password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Konfirmasi {{ $is_edit_mode ? "Reset Sandi Baru (isi hanya saat ingin mereset)" : "Sandi Baru" }}</label>
                            <input wire:model.debounce.1s="inputs.password_confirmation" type="password" class="form-control @error('inputs.password_confirmation') is-invalid @enderror" placeholder=""/>
                            @error('inputs.password_confirmation') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                    </div>
                    <div class="card-footer">
                        @include('layout.customs.button-submit')
                        @if ($is_edit_mode)
                            <button type="button" class="btn btn-secondary" wire:click="$emit('master_user_edit_mode',false)"> {{ __('generic.button_cancel') }}</button>
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
                        @livewire('masters.users.index-data-table')
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('layout.customs.modal-delete', ['destroy_listener' => 'master_user_destroy'])
</div>
