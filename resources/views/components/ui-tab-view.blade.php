{{-- ui-tab-view.blade.php --}}
<div id="tab-container-{{ $id }}">
    <ul class="nav nav-tabs" id="{{ $id }}" role="tablist">
        @php
            $tabItems = is_array($tabs) ? $tabs : explode(',', $tabs);
        @endphp

        @foreach ($tabItems as $tab)
            @php
                $tabWithoutSpaces = str_replace(' ', '', $tab);
            @endphp
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="{{ $tabWithoutSpaces }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $tabWithoutSpaces }}" type="button" role="tab" aria-controls="{{ $tabWithoutSpaces }}">{{ ucfirst($tab) }}</button>
            </li>
        @endforeach
    </ul>
</div>

<script>
    document.addEventListener('livewire:load', function () {
        const setActiveTab = (tabContainer) => {
            // Set the first tab as active by default or maintain the current active state
            let activeTabExists = false;
            tabContainer.querySelectorAll('.nav-link').forEach(tab => {
                if (tab.classList.contains('active')) {
                    activeTabExists = true;
                }
            });

            if (!activeTabExists) {
                let firstTab = tabContainer.querySelector('.nav-link');
                if (firstTab) {
                    firstTab.classList.add('active');
                    firstTab.setAttribute('aria-selected', 'true');
                }
            }
        };

        const tabContainer = document.getElementById('tab-container-{{ $id }}');
        if (tabContainer) {
            setActiveTab(tabContainer); // Set the active tab on load

            tabContainer.addEventListener('click', function (event) {
                if (event.target && event.target.matches('#{{ $id }} .nav-link')) {
                    // Deactivate all tabs
                    tabContainer.querySelectorAll('.nav-link').forEach(tab => {
                        tab.classList.remove('active');
                        tab.setAttribute('aria-selected', 'false');
                    });

                    // Activate clicked tab
                    event.target.classList.add('active');
                    event.target.setAttribute('aria-selected', 'true');
                }
            });
        }
    });
</script>
