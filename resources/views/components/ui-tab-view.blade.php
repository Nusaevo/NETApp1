<div id="tab-container-{{ $id }}" class="modern-tab-container">
    <ul class="nav nav-tabs modern-tabs" id="{{ $id }}" role="tablist">
        @php
            $tabItems = is_array($tabs) ? $tabs : explode(',', $tabs);
        @endphp

        @foreach ($tabItems as $tab)
            @php
                $tabWithoutSpaces = str_replace(' ', '', $tab);
                $isActive = $loop->first ? 'active' : ''; // First tab is active by default
            @endphp
            <li class="nav-item" role="presentation">
                <button class="nav-link modern-tab-link {{ $isActive }}"
                        id="{{ $tabWithoutSpaces }}-tab"
                        data-bs-toggle="tab"
                        data-bs-target="#{{ $tabWithoutSpaces }}"
                        type="button"
                        role="tab"
                        aria-controls="{{ $tab }}">
                    <span class="tab-text">{{ ucfirst($tab) }}</span>
                    <div class="tab-indicator"></div>
                </button>
            </li>
        @endforeach
    </ul>
</div>

<style>
.modern-tab-container {
    margin-bottom: 2rem;
}

.modern-tabs {
    border-bottom: 2px solid #e9ecef;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-radius: 0.5rem 0.5rem 0 0;
    padding: 0.25rem 0.75rem 0 0.75rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.modern-tab-link {
    position: relative;
    background: none !important;
    border: none !important;
    border-radius: 0.375rem 0.375rem 0 0 !important;
    padding: 0.5rem 1rem !important;
    margin: 0 0.125rem !important;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
    color: #6c757d !important;
    font-weight: 500 !important;
    font-size: 0.875rem !important;
    overflow: hidden;
    min-height: auto !important;
}

.modern-tab-link:hover {
    background: rgba(108, 117, 125, 0.08) !important;
    color: #495057 !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.modern-tab-link.active {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%) !important;
    color: #212529 !important;
    font-weight: 600 !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 0.8);
    border: 1px solid #dee2e6 !important;
    border-bottom: 1px solid #ffffff !important;
    margin-bottom: -2px !important;
}

.tab-text {
    position: relative;
    z-index: 2;
    display: block;
    transition: all 0.3s ease;
}

.tab-indicator {
    position: absolute;
    bottom: 0;
    left: 50%;
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, #6c757d 0%, #495057 100%);
    border-radius: 1px 1px 0 0;
    transform: translateX(-50%);
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.modern-tab-link:hover .tab-indicator {
    width: 30%;
    background: linear-gradient(90deg, #495057 0%, #212529 100%);
}

.modern-tab-link.active .tab-indicator {
    width: 70%;
    height: 3px;
    background: linear-gradient(90deg, #007bff 0%, #0056b3 100%);
    box-shadow: 0 1px 4px rgba(0, 123, 255, 0.4);
}

/* Ripple effect */
.modern-tab-link::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(108, 117, 125, 0.1);
    transition: all 0.6s ease;
    transform: translate(-50%, -50%);
    pointer-events: none;
}

.modern-tab-link:active::before {
    width: 200px;
    height: 200px;
}

/* Content area styling */
.modern-tab-container + .tab-content {
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-top: none;
    border-radius: 0 0 0.75rem 0.75rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    overflow: hidden;
}

.modern-tab-container + .tab-content .tab-pane {
    padding: 1.5rem;
    animation: fadeInUp 0.4s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(15px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Badge/Count support */
.tab-badge {
    display: inline-block;
    padding: 0.2rem 0.4rem;
    font-size: 0.7rem;
    font-weight: 600;
    line-height: 1;
    color: #fff;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.25rem;
    background-color: #6c757d;
    margin-left: 0.5rem;
    transition: all 0.3s ease;
}

.modern-tab-link.active .tab-badge {
    background-color: #007bff;
    transform: scale(1.1);
}

/* Responsive design */
@media (max-width: 768px) {
    .modern-tabs {
        padding: 0.125rem 0.5rem 0 0.5rem;
    }

    .modern-tab-link {
        padding: 0.375rem 0.75rem !important;
        font-size: 0.8rem !important;
        margin: 0 0.0625rem !important;
    }

    .modern-tab-container + .tab-content .tab-pane {
        padding: 1rem;
    }
}

@media (max-width: 576px) {
    .modern-tab-link {
        padding: 0.25rem 0.5rem !important;
        font-size: 0.75rem !important;
    }

    .tab-text {
        font-size: 0.8rem;
    }
}
</style>
@push('scripts')
<script>
    (function() {
        // Initialize a variable to keep track of the active tab
        let activeTabId = null;

        document.addEventListener('DOMContentLoaded', function () {
            const tabContainer = document.getElementById('tab-container-{{ $id }}');
            const tabs = tabContainer.querySelectorAll('.nav-link');

            tabs.forEach(tab => {
                tab.addEventListener('click', function () {
                    // Update the activeTabId when a tab is clicked
                    activeTabId = tab.getAttribute('id');

                    // Activate the clicked tab and corresponding pane
                    activateTab(tab);
                });
            });

            // Function to activate a tab and its corresponding pane
            function activateTab(tab) {
                const tabId = tab.getAttribute('id');
                const paneId = tab.getAttribute('data-bs-target');

                // Deactivate all tabs and panes
                tabs.forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active', 'show'));

                // Activate the current tab and pane
                tab.classList.add('active');
                document.querySelector(paneId).classList.add('active', 'show');
            }

            // Function to restore the active tab state after Livewire updates
            function restoreActiveTabState() {
                if (activeTabId) {
                    const activeTab = document.getElementById(activeTabId);
                    if (activeTab) {
                        activateTab(activeTab);
                    }
                }
            }

            // Listen for Livewire's hook that runs after DOM updates
            window.Livewire.hook('morph.updated', function () {
                restoreActiveTabState();
            });

            // If no tab is active, activate the first one
            if (tabs.length > 0 && !activeTabId) {
                activateTab(tabs[0]);
            }
        });
    })();
</script>
@endpush
