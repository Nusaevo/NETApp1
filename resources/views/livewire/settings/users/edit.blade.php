<div>
    <div>
        @include('layout.customs.notification')
    </div>
    <div>
        <a href="{{ route('users.index') }}" class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2"><i class="bi bi-arrow-left-circle fs-2 me-2"></i> Kembali</a>
    </div>

    <div id="kt_content_container" class="container-xxl mb-5" wire:ignore>
        <div class="card shadow-sm">
            <div >
                 <h3>{{ $action }} User</h3>
            </div>
            <form wire:submit.prevent="{{ $action === 'Edit' ? 'update' : 'store' }}" class="form w-100">
                <x-ui-expandable-card  id="UserGeneralInfoCard" title="User General Info" :isOpen="true">
                    <x-ui-text-field
                    label="Nama Pelanggan"
                    model="inputs.first_name"
                    type="text"
                    :disabled="$action === 'View'"
                    />

                    <div class="mb-10">
                        <label class="required form-label">Alamat</label>
                        <input wire:model.defer="inputs.last_name" type="text" class="form-control @error('user.last_name') is-invalid @enderror" {{ $action === 'View' ? 'disabled' : '' }}/>
                        @error('user.last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-10">
                        <label class="required form-label">Email</label>
                        <input wire:model.defer="inputs.email" type="email" class="form-control @error('user.email') is-invalid @enderror" placeholder="user@gmail.com" {{ $action === 'View' ? 'disabled' : '' }}/>
                        @error('user.email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </x-ui-expandable-card>



                <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#user_info_tab" wire:ignore.self>
                    <h3 class="card-title">User Info</h3>
                    <div class="card-toolbar rotate-180">
                        <i class="bi bi-arrow-bar-down"></i>
                    </div>
                </div>
                <div id="user_info_tab" class="collapse" wire:ignore.self>
                    <div class="card-body">
                        <div class="mb-10">
                            <label class="form-label">Company</label>
                            <input wire:model.defer="inputs.company" type="text"
                                class="form-control @error('user.company') is-invalid @enderror"
                                {{ $action === 'View' ? 'disabled' : '' }} />
                            @error('user.company') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="form-label">Phone</label>
                            <input wire:model.defer="inputs.phone" type="text"
                                class="form-control @error('user.phone') is-invalid @enderror"
                                {{ $action === 'View' ? 'disabled' : '' }} />
                            @error('user.phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    @if ($action === 'Edit')
                        <button wire:click="update" class="btn btn-primary">Update</button>
                    @elseif ($action === 'Create')
                        <button wire:click="store" class="btn btn-success">Create</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
