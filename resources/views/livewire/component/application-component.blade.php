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
</div>
