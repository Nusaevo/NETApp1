<div class="text-center position-relative">
    @if($enable_this_row)
    <div class="btn-group dropup d-none d-md-inline-block">
        <button class="btn btn-primary btn-sm dropdown-toggle" type="button"
            id="dropdownMenuButton{{ $row->id }}" data-bs-toggle="dropdown" data-bs-display="static"
            aria-expanded="false">
            <i class="bi bi-three-dots-vertical"></i>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            @if($allow_details && isset($permissions['read']) && $permissions['read'])
            <li>
                <a class="dropdown-item btn btn-sm" href="#" wire:click="viewData({{ $row->id }})">
                    <i class="bi bi-eye"></i> Detil
                </a>
            </li>
            @endif
            @if($allow_edit && (isset($permissions['read']) && $permissions['read'] || isset($permissions['update']) && $permissions['update']))
            <li>
                <a class="dropdown-item btn btn-sm" href="#" wire:click="editData({{ $row->id }})">
                    <i class="bi bi-pencil"></i> Edit
                </a>
            </li>
            @endif
            @if($allow_delete && isset($permissions['delete']) && $permissions['delete'])
            <li>
                <a class="dropdown-item btn btn-sm btn-dialog-box" href="#" wire:click="selectData({{ $row->id }})">
                    <i class="bi bi-trash"></i> Delete
                </a>
            </li>
            @endif
            @if($allow_disable && isset($permissions['delete']) && $permissions['delete'])
            <li>
                <a class="dropdown-item btn btn-sm btn-dialog-box" href="#" wire:click="selectData({{ $row->id }})">
                    <i class="bi bi-x-circle"></i> Disable
                </a>
            </li>
            @endif
            @if($custom_actions && isset($custom_actions))
            @foreach ($custom_actions as $action)
                @if(!isset($action['condition']) || $action['condition'])
                    <li>
                        @if(isset($action['onClick']))
                            <a class="dropdown-item btn btn-sm" href="#" wire:click="{{ $action['onClick'] }}">
                                <i class="{{ $action['icon'] }}"></i> {{ $action['label'] }}
                            </a>
                        @else
                            <a class="dropdown-item btn btn-sm" href="{{ $action['route'] }}" style="text-decoration: none;">
                                <i class="{{ $action['icon'] }}"></i> {{ $action['label'] }}
                            </a>
                        @endif
                    </li>
                @endif
            @endforeach
            @endif
        </ul>
    </div>
    <div class="mobile-button">
        @if($allow_details && isset($permissions['read']) && $permissions['read'])
        <button class="btn btn-primary btn-sm" wire:click="viewData({{ $row->id }})">
            <i class="bi bi-eye"></i> Detil
        </button>
        @endif
        @if($allow_edit && (isset($permissions['read']) && $permissions['read'] || isset($permissions['update']) && $permissions['update']))
        <button class="btn btn-secondary btn-sm" wire:click="editData({{ $row->id }})">
            <i class="bi bi-pencil"></i> Edit
        </button>
        @endif
        @if($allow_delete && isset($permissions['delete']) && $permissions['delete'])
        <button class="btn btn-danger btn-sm btn-dialog-box" wire:click="selectData({{ $row->id }})">
            <i class="bi bi-trash"></i> Delete
        </button>
        @endif
        @if($allow_disable && isset($permissions['delete']) && $permissions['delete'])
        <button class="btn btn-warning btn-sm btn-dialog-box" wire:click="selectData({{ $row->id }})">
            <i class="bi bi-x-circle"></i> Disable
        </button>
        @endif
        @if($custom_actions && isset($custom_actions))
        @foreach ($custom_actions as $action)
            @if(!isset($action['condition']) || $action['condition'])
                @if(isset($action['onClick']))
                    <button class="btn btn-info btn-sm" wire:click="{{ $action['onClick'] }}">
                        <i class="{{ $action['icon'] }}"></i> {{ $action['label'] }}
                    </button>
                @else
                    <button class="btn btn-info btn-sm" onclick="window.location='{{ $action['route'] }}'">
                        <i class="{{ $action['icon'] }}"></i> {{ $action['label'] }}
                    </button>
                @endif
            @endif
        @endforeach
        @endif
    </div>
    @endif
</div>
