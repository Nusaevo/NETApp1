<div class="text-center">
    @if($enable_this_row)
    <div class="dropdown">
        <button class="btn btn-primary btn-sm dropdown-toggle" type="button"
            id="dropdownMenuButton{{ $row->id }}" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-three-dots-vertical"></i>
        </button>
        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton{{ $row->id }}">
            @if($allow_details && isset($permissions['read']) && $permissions['read'])
            <li><a class="dropdown-item" href="#" wire:click="viewData({{ $row->id }})">
                <i class="bi bi-eye me-2"></i>Detil
            </a></li>
            @endif
            @if($allow_edit && (isset($permissions['read']) && $permissions['read'] || isset($permissions['update']) && $permissions['update']))
            <li><a class="dropdown-item" href="#" wire:click="editData({{ $row->id }})">
                <i class="bi bi-pencil me-2"></i>Edit
            </a></li>
            @endif
            @if($allow_delete && isset($permissions['delete']) && $permissions['delete'])
            <li><a class="dropdown-item text-danger btn-dialog-box" href="#" wire:click="selectData({{ $row->id }})">
                <i class="bi bi-trash me-2"></i>Delete
            </a></li>
            @endif
            @if($allow_disable && isset($permissions['delete']) && $permissions['delete'])
            <li><a class="dropdown-item text-warning btn-dialog-box" href="#" wire:click="selectData({{ $row->id }})">
                <i class="bi bi-x-circle me-2"></i>Disable
            </a></li>
            @endif
            @if($custom_actions && isset($custom_actions))
            @foreach ($custom_actions as $action)
                @if(!isset($action['condition']) || $action['condition'])
                    <li>
                        @if(isset($action['onClick']))
                            <a class="dropdown-item" href="#" wire:click="{{ $action['onClick'] }}">
                                <i class="{{ $action['icon'] }} me-2"></i>{{ $action['label'] }}
                            </a>
                        @else
                            <a class="dropdown-item" href="{{ $action['route'] }}">
                                <i class="{{ $action['icon'] }} me-2"></i>{{ $action['label'] }}
                            </a>
                        @endif
                    </li>
                @endif
            @endforeach
            @endif
        </ul>
    </div>
    @endif
</div>

@push('styles')
<style>
/* CLEAN DROPDOWN ACTION - NO TABLE INTERFERENCE */
.text-center .dropdown {
    position: relative;
    display: inline-block;
}

.text-center .dropdown .btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.text-center .dropdown .dropdown-menu {
    position: absolute;
    top: 100%;
    left: 50%;
    transform: translateX(-50%);
    z-index: 1050;
    margin-top: 2px;
    min-width: 150px;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

/* ACTION COLUMN FIXED WIDTH */
td:has(.dropdown) {
    width: 60px;
    padding: 0.5rem 0.25rem;
    vertical-align: middle;
}

/* PREVENT ROW LAYOUT CHANGES */
.data-table-body tr,
.table tbody tr {
    position: static;
}

.data-table-body td,
.table tbody td {
    overflow: visible;
}
</style>
@endpush
