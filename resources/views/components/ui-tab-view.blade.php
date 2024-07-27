<div id="tab-container-{{ $id }}">
    <ul class="nav nav-tabs" id="{{ $id }}" role="tablist">
        @php
            $tabItems = is_array($tabs) ? $tabs : explode(',', $tabs);
        @endphp

        @foreach ($tabItems as $tab)
            @php
                $tabWithoutSpaces = str_replace(' ', '', $tab);
                $isActive = $loop->first ? 'active' : ''; // First tab is active by default
            @endphp
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $isActive }}" id="{{ $tabWithoutSpaces }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $tabWithoutSpaces }}" type="button" role="tab" aria-controls="{{ $tab }}">{{ ucfirst($tab) }}</button>
            </li>
        @endforeach
    </ul>
</div>
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
