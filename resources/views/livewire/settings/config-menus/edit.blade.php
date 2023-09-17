<div>
    <div>
        @include('layout.customs.notification')
    </div>
    <div>
        <a href="{{ route('config_menus.index') }}" class="btn btn-link btn-color-info btn-active-color-primary me-5 mb-2">
            <i class="bi bi-arrow-left-circle fs-2 me-2"></i> Back
        </a>
    </div>

    <div id="kt_content_container" class="container-xxl mb-5" wire:ignore>
        <div class="card shadow-sm">
            <div>
                <h3>{{ $action }} Config Menu</h3>
            </div>
            <form wire:submit.prevent="{{ $action === 'Edit' ? 'update' : 'store' }}" class="form w-100">

                <div class="card-header collapsible cursor-pointer rotate" data-bs-toggle="collapse" data-bs-target="#config_menu_general_tab" wire:ignore.self>
                    <h3 class="card-title">Config Menu General Info</h3>
                    <div class="card-toolbar rotate-180">
                        <i class="bi bi-arrow-bar-down"></i>
                    </div>
                </div>

                <div id="config_menu_general_tab" class="collapse show" wire:ignore.self>
                    <div class="card-body">
                        <div class="mb-10">
                            <label class="required form-label">Appl Code</label>
                            <input wire:model.defer="inputs.appl_code" type="text" class="form-control @error('configMenu.appl_code') is-invalid @enderror" {{ $action === 'View' ? 'disabled' : '' }}/>
                            @error('configMenu.appl_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Menu Code</label>
                            <input wire:model.defer="inputs.menu_code" type="text" class="form-control @error('configMenu.menu_code') is-invalid @enderror" {{ $action === 'View' ? 'disabled' : '' }}/>
                            @error('configMenu.menu_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Menu Caption</label>
                            <input wire:model.defer="inputs.menu_caption" type="text" class="form-control @error('configMenu.menu_caption') is-invalid @enderror" {{ $action === 'View' ? 'disabled' : '' }}/>
                            @error('configMenu.menu_caption') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Status Code</label>
                            <input wire:model.defer="inputs.status_code" type="text" class="form-control @error('configMenu.status_code') is-invalid @enderror" {{ $action === 'View' ? 'disabled' : '' }}/>
                            @error('configMenu.status_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-10">
                            <label class="required form-label">Is Active</label>
                            <input wire:model.defer="inputs.is_active" type="text" class="form-control @error('configMenu.is_active') is-invalid @enderror" {{ $action === 'View' ? 'disabled' : '' }}/>
                            @error('configMenu.is_active') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
