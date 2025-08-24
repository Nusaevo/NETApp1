<div>
    <div class="application-selector-container">
        @php
            $selectedApp = collect($applications)->firstWhere('value', $selectedApplication);
            $selectedImagePath = 'customs/logos/' . ($selectedApp['value'] ?? 'default') . '.png';
        @endphp

        <!-- Dropdown Container -->
        <div class="app-dropdown-container dropdown w-100">
            <!-- Current Selected Application with Logo (Clickable) -->
            <button type="button"
                    class="app-dropdown-trigger btn btn-light current-app-display mb-2 py-2 px-3 rounded w-100 text-start"
                    style="background-color: var(--bs-light-bg-subtle); border: 1px solid var(--bs-border-color); min-height: 40px;"
                    data-bs-toggle="dropdown"
                    aria-expanded="false">
                <div class="d-flex align-items-center">
                    <img src="{{ asset($selectedImagePath) }}"
                         alt="{{ $selectedApp['label'] ?? 'App' }}"
                         class="current-app-logo me-3"
                         style="height: 28px; width: 28px; object-fit: contain; border-radius: 0.25rem; border: 1px solid var(--bs-border-color);"
                         onerror="this.src='{{ asset('customs/logos/default.png') }}'; this.onerror=null;">
                    <div class="flex-fill">
                        <div class="fw-semibold text-dark current-app-name" style="font-size: 0.9rem; line-height: 1.3;">{{ $selectedApp['label'] ?? 'Application' }}</div>
                    </div>
                    <i class="bi bi-chevron-down text-muted" style="font-size: 0.6rem;"></i>
                </div>
            </button>

            <!-- Dropdown Menu with App List -->
            <ul class="dropdown-menu w-100 shadow-lg" style="max-height: 280px; overflow-y: auto;">
                @foreach($applications as $application)
                    @php
                        $imagePath = 'customs/logos/' . $application['value'] . '.png';
                    @endphp
                    <li>
                        <a class="dropdown-item app-option py-2 px-3"
                           href="#"
                           wire:click="configApplicationChanged('{{ $application['value'] }}')"
                           onclick="console.log('ApplicationComponent: Livewire click on:', '{{ $application['value'] }}');">
                            <div class="d-flex align-items-center">
                                <img src="{{ asset($imagePath) }}"
                                     alt="{{ $application['label'] }}"
                                     class="me-3"
                                     style="height: 28px; width: 28px; object-fit: contain; border-radius: 0.25rem; border: 1px solid var(--bs-border-color);"
                                     onerror="this.src='{{ asset('customs/logos/default.png') }}'; this.onerror=null;">
                                <div class="flex-fill">
                                    <div class="fw-medium" style="font-size: 0.9rem; line-height: 1.3;">{{ $application['label'] }}</div>
                                </div>
                                @if($selectedApplication == $application['value'])
                                    <i class="bi bi-check-circle-fill text-success" style="font-size: 0.9rem;"></i>
                                @endif
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Hidden Select (backup) -->
        <select id="applicationSelect"
                class="form-select d-none"
                wire:change="configApplicationChanged($event.target.value)"
                wire:loading.attr="disabled">
            @foreach($applications as $application)
                @php
                    $imagePath = 'customs/logos/' . $application['value'] . '.png';
                @endphp
                <option value="{{ $application['value'] }}"
                        @if($selectedApplication == $application['value']) selected @endif
                        data-image="{{ asset($imagePath) }}">
                    {{ $application['label'] }}
                </option>
            @endforeach
        </select>
    </div>

@push('scripts')
<script>
// Application Component Dropdown - Minimal
document.addEventListener('DOMContentLoaded', function() {
    // Let the global initialization handle everything
    // Just add component-specific logging
    const appTrigger = document.querySelector('.app-dropdown-trigger');
    if (appTrigger) {
        // Component-specific click logging
        appTrigger.addEventListener('click', function(e) {
            // Silent operation
        });
    }
});
</script>
@endpush

<style>
    /* Application Component Dropdown - Single Adaptive Component */
    .application-selector-container {
        display: block;
        width: 100%;
    }

    .app-dropdown-container {
        position: relative;
        width: 100%;
    }

    .app-dropdown-container .dropdown-menu {
        display: none;
        z-index: 1050;
        background-color: #fff;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.5rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        padding: 6px 0;
        margin-top: 4px;
    }

    .app-dropdown-container .dropdown-menu.show {
        display: block !important;
    }

    .app-dropdown-container .app-option {
        padding: 10px 12px;
        border: none;
        cursor: pointer;
        min-height: 40px;
        transition: background-color 0.15s ease;
    }

    .app-dropdown-container .app-option:hover {
        background-color: var(--bs-light);
        color: var(--bs-dark);
    }

    .app-dropdown-container .current-app-logo {
        flex-shrink: 0;
    }

    .app-dropdown-container .app-dropdown-trigger {
        transition: all 0.2s ease;
        user-select: none;
        width: 100%;
    }

    .app-dropdown-container .app-dropdown-trigger:hover {
        background-color: var(--bs-secondary-bg) !important;
        border-color: var(--bs-primary) !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    /* Dark theme */
    [data-bs-theme="dark"] .app-dropdown-container .dropdown-menu {
        background-color: var(--bs-dark) !important;
        border-color: var(--bs-border-color);
    }

    [data-bs-theme="dark"] .app-dropdown-container .app-option {
        color: var(--bs-body-color);
    }

    [data-bs-theme="dark"] .app-dropdown-container .app-option:hover {
        background-color: var(--bs-secondary-bg);
    }

    /* Desktop specific styles */
    @media (min-width: 992px) {
        .app-dropdown-container .current-app-logo {
            height: 28px;
            width: 28px;
        }

        .app-dropdown-container .app-dropdown-trigger {
            min-height: 40px;
            padding: 8px 12px;
        }

        .app-dropdown-container .dropdown-menu {
            max-height: 280px;
        }
    }

    /* Mobile specific styles */
    @media (max-width: 991.98px) {
        .app-dropdown-container .current-app-logo {
            height: 24px !important;
            width: 24px !important;
        }

        .app-dropdown-container .app-dropdown-trigger {
            min-height: 36px !important;
            padding: 6px 10px !important;
            font-size: 0.85rem !important;
        }

        .app-dropdown-container .dropdown-menu {
            max-height: 250px !important;
        }

        /* When in offcanvas, ensure proper positioning */
        .offcanvas .app-dropdown-container .dropdown-menu {
            z-index: 1056 !important;
            position: absolute !important;
        }
    }
</style>

</div>
