@php
    $id = str_replace(['.', '[', ']'], '_', $name);
    $colClass = 'col-sm' . (!empty($label) ? ' mb-5' : '');
    $containerClass = !empty($label) ? 'form-floating flex-grow-1' : 'flex-grow-1';
    // Determine enabled state based on action and enabled parameter
    $isEnabled = !((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'));
@endphp

{{--
    Komponen Dropdown Search Multiple Select

    Cara Penggunaan:
    @php
        $items = [
            '1' => 'Item Pertama',
            '2' => 'Item Kedua',
            '3' => 'Item Ketiga',
            '4' => 'Item Keempat',
            '5' => 'Item Kelima'
        ];

        $selectedItems = ['1', '3']; // Item yang sudah dipilih
    @endphp

    <x-tes-component
        :items="$items"
        :selectedItems="$selectedItems"
        placeholder="Pilih item yang diinginkan..."
        name="my_items"
        label="Pilih Item"
        :multiple="true"
    />

    Parameter:
    - items: Array asosiatif dengan key => value
    - selectedItems: Array berisi key dari item yang sudah dipilih
    - placeholder: Teks placeholder untuk dropdown
    - name: Nama untuk input field
    - label: Label untuk field (opsional)
    - multiple: Boolean untuk multiple select (default: true)
--}}

<div class="{{ $colClass }}"
    @if (isset($span)) span="{{ $span }}" @endif
    @if (isset($visible) && $visible === 'false') style="display: none;" @endif>

    <div class="input-group">
        <div class="{{ $containerClass }}">
            <div class="dropdown-search-multiple" id="{{ $id }}_container" style="position: relative; z-index: 10;">
                <div class="dropdown">
                    <div class="selected-chips-container {{ !$isEnabled ? 'disabled' : '' }}"
                         id="{{ $id }}_chips_container"
                         @if($isEnabled) data-bs-toggle="dropdown" aria-expanded="false" @endif>
                        <!-- Chips akan ditampilkan di sini -->
                    </div>
                    <ul class="dropdown-menu w-100" id="{{ $id }}_dropdown">
                        <li class="px-3 py-2">
                            <input type="text" class="form-control form-control-sm"
                                   id="{{ $id }}_search"
                                   placeholder="Cari item..."
                                   autocomplete="off">
                        </li>
                        <li><hr class="dropdown-divider"></li>
                                        <div id="{{ $id }}_items_container">
                    @php
                        $firstFiveItems = array_slice($items, 0, 5, true);
                    @endphp
                    @foreach($firstFiveItems as $key => $item)
                        <li>
                            <div class="dropdown-item d-flex align-items-center {{ in_array($key, $selectedItems) ? 'selected' : '' }}"
                                 data-value="{{ $key }}"
                                 data-item-text="{{ is_array($item) ? ($item['label'] ?? $item['value'] ?? $key) : $item }}">
                                <span>{{ is_array($item) ? ($item['label'] ?? $item['value'] ?? $key) : $item }}</span>
                            </div>
                        </li>
                    @endforeach
                </div>
                    </ul>
                </div>
                <input type="hidden" id="{{ $id }}_selected_values" name="{{ $name }}_values" value="{{ json_encode($selectedItems) }}">
            </div>

            @if (!empty($label))
                <label for="{{ $id }}" class="@if (isset($required) && $required === 'true') required @endif">
                    {{ $label }}
                </label>
            @endif

            @error($name)
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <!-- Button for Click Event -->
        @if (isset($clickEvent) && $clickEvent !== '')
            <x-ui-button type="InputButton" :clickEvent="$clickEvent" cssClass="btn btn-secondary"
                :buttonName="$buttonName" :action="$action" :enabled="$buttonEnabled" loading="true" />
        @endif
    </div>
</div>

<style>
.dropdown-search-multiple .dropdown-menu {
    max-height: 300px;
    overflow-y: auto;
    z-index: 20;
}

.dropdown-search-multiple .dropdown-item {
    cursor: pointer;
    padding: 0.5rem 1rem;
}

.dropdown-search-multiple .dropdown-item:hover {
    background-color: #f8f9fa;

}

.dropdown-search-multiple .dropdown-item.selected {
    background-color: #e7f3ff;
    color: #0d6efd;
    font-weight: 500;
}

.dropdown-search-multiple .dropdown-item:hover {
    background-color: #f8f9fa;
    cursor: pointer;
}

.dropdown-search-multiple .btn-outline-secondary:focus {
    box-shadow: 0 0 0 0.25rem rgba(108, 117, 125, 0.25);
}

/* Styles untuk chips - menyesuaikan dengan form-select */
.selected-chips-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    min-height: 38px; /* Sama dengan form-select */
    padding: 0.375rem 0.75rem;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    background-color: #fff;
    cursor: pointer;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    font-size: 1rem;
    line-height: 1.5;
    width: 100%;
    box-sizing: border-box;
    position: relative;
    z-index: 10;
}

.selected-chips-container:hover {
    border-color: #adb5bd;
}

.selected-chips-container:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    outline: 0;
}

.selected-chips-container.disabled {
    background-color: #e9ecef;
    border-color: #ced4da;
    color: #6c757d;
    cursor: not-allowed;
    opacity: 0.65;
    pointer-events: none;
}

.selected-chips-container.disabled:hover {
    border-color: #ced4da;
    background-color: #e9ecef;
}

/* Form floating label styling */
.form-floating .selected-chips-container {
    min-height: 58px; /* Sama dengan form-floating */
}

.form-floating > label {
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    padding: 1rem 0.75rem;
    pointer-events: none;
    border: 1px solid transparent;
    transform-origin: 0 0;
    transition: opacity 0.1s ease-in-out, transform 0.1s ease-in-out;
    color: #6c757d;
    z-index: 10;
}

.form-floating > .selected-chips-container:focus ~ label,
.form-floating > .selected-chips-container:not(:placeholder-shown) ~ label {
    opacity: 0.65;
    transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
}

.selected-chip {
    display: inline-flex;
    align-items: center;
    background-color: #6c757d;
    color: white;
    padding: 0.20rem 0.50rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    margin: 0;
    transition: all 0.2s ease;
    z-index: 15;
    cursor: pointer;
    user-select: none;
    position: relative;
}

.selected-chip:hover {
    background-color: #353638;
    z-index: 20;
}


.chip-remove {
    margin-left: 0.5rem;
    font-size: 1rem;
    line-height: 1;
    opacity: 0.8;
    transition: opacity 0.2s ease;
}

.selected-chip:hover .chip-remove {
    opacity: 1;
}

/* Placeholder text styling */
.chips-placeholder {
    color: #6c757d;
    padding-top: 15px;
    padding-left: 10px;
    align-self: center;
}

/* Ensure chips container has higher z-index when chips are present */
.selected-chips-container:has(.selected-chip) {
    z-index: 15;
}

/* Additional z-index for the entire component container */
.dropdown-search-multiple {
    position: relative;
    z-index: 10;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('{{ $id }}_container');
    const searchInput = document.getElementById('{{ $id }}_search');
    const itemsContainer = document.getElementById('{{ $id }}_items_container');
    const chipsContainer = document.getElementById('{{ $id }}_chips_container');
    const dropdownItems = itemsContainer.querySelectorAll('.dropdown-item');
    const hiddenInput = document.getElementById('{{ $id }}_selected_values');
    const isEnabled = {{ $isEnabled ? 'true' : 'false' }};

    console.log('Tes component initialized for:', '{{ $id }}');

    let selectedItems = [];

    // Store all items data for dynamic loading
    let allItemsData = @json($items);

    // Helper function to get Livewire component
    function getLivewireComponent() {
        if (typeof $wire !== 'undefined') {
            return $wire;
        } else if (typeof Livewire !== 'undefined') {
            const livewireComponent = document.querySelector('[wire\\:id]');
            if (livewireComponent) {
                const wireId = livewireComponent.getAttribute('wire:id');
                return Livewire.find(wireId);
            }
        }
        return null;
    }

    // Initialize selected items
    dropdownItems.forEach(item => {
        if (item.classList.contains('selected')) {
            selectedItems.push(item.dataset.value);
            // Hide selected items from dropdown initially
            item.closest('li').style.display = 'none';
        }
    });

    console.log('Initial selectedItems:', selectedItems);
    console.log('Initial allItemsData:', allItemsData);

    updateChips();
    updateHiddenInput();

        // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();

        if (searchTerm.length > 0) {
            // Show all items when searching
            loadAllItems();
        } else {
            // Show only first 5 items when search is empty
            loadFirstFiveItems();
        }

        // Filter items based on search term
        const items = itemsContainer.querySelectorAll('li');
        items.forEach(item => {
            const text = item.querySelector('span').textContent.toLowerCase();
            const isSelected = item.querySelector('.dropdown-item').classList.contains('selected');

            // Only show items that match search AND are not selected
            if (text.includes(searchTerm) && !isSelected) {
                item.style.display = 'block';
            } else if (isSelected) {
                // Keep selected items hidden
                item.style.display = 'none';
            } else {
                item.style.display = 'none';
            }
        });
    });

    // Dropdown item click handler
    dropdownItems.forEach(item => {
        item.addEventListener('click', function() {
            if (!isEnabled) return; // Prevent interaction if disabled

            const value = this.dataset.value;

            // Add item to selected
            selectedItems.push(value);
            this.classList.add('selected');

            // Hide the item from dropdown
            this.closest('li').style.display = 'none';

            updateChips();
            updateHiddenInput();
        });
    });

    // Prevent dropdown from closing when clicking inside
    container.querySelector('.dropdown-menu').addEventListener('click', function(e) {
        e.stopPropagation();
    });

        // Update chips display
    function updateChips() {
        chipsContainer.innerHTML = '';

        if (selectedItems.length === 0) {
            const placeholderText = '{{ $placeholder ?? 'Pilih beberapa...' }}';
            chipsContainer.innerHTML = '<span class="chips-placeholder">' + placeholderText + '</span>';
            return;
        }

        selectedItems.forEach(itemValue => {
            // Cari item dari allItemsData terlebih dahulu, jika tidak ada baru cari dari dropdownItems
            let itemText = allItemsData[itemValue];
            let dropdownItem = null;

            if (!itemText) {
                dropdownItem = Array.from(dropdownItems).find(item => item.dataset.value === itemValue);
                if (dropdownItem) {
                    itemText = dropdownItem.dataset.itemText;
                }
            }

            if (itemText) {
                const chip = document.createElement('span');
                chip.className = 'selected-chip';
                chip.setAttribute('data-value', itemValue);
                chip.innerHTML = `
                    ${itemText}
                    <span class="chip-remove"> ×</span>
                `;

                // Add remove functionality to entire chip
                chip.addEventListener('click', function(e) {
                    if (!isEnabled) return; // Prevent interaction if disabled

                    e.stopPropagation(); // Prevent dropdown from opening when removing chip
                    const value = this.dataset.value;
                    selectedItems = selectedItems.filter(item => item !== value);

                    // Remove selected class from dropdown item and show it again
                    // We need to reload items to show the removed item
                    const currentSearchTerm = searchInput.value;
                    if (currentSearchTerm.length > 0) {
                        loadAllItems();
                    } else {
                        loadFirstFiveItems();
                    }

                    // Re-apply search filter if needed
                    if (currentSearchTerm.length > 0) {
                        const items = itemsContainer.querySelectorAll('li');
                        items.forEach(item => {
                            const text = item.querySelector('span').textContent.toLowerCase();
                            const isSelected = item.querySelector('.dropdown-item').classList.contains('selected');

                            if (text.includes(currentSearchTerm) && !isSelected) {
                                item.style.display = 'block';
                            } else if (isSelected) {
                                item.style.display = 'none';
                            } else {
                                item.style.display = 'none';
                            }
                        });
                    }

                    updateChips();
                    updateHiddenInput();
                });

                chipsContainer.appendChild(chip);
            }
        });
    }

    // Load all items into dropdown
    function loadAllItems() {
        itemsContainer.innerHTML = '';

        Object.entries(allItemsData).forEach(([key, item]) => {
            const isSelected = selectedItems.includes(key);
            const li = document.createElement('li');
            li.innerHTML = `
                <div class="dropdown-item d-flex align-items-center ${isSelected ? 'selected' : ''}"
                     data-value="${key}"
                     data-item-text="${item}">
                    <span>${item}</span>
                </div>
            `;

            const dropdownItem = li.querySelector('.dropdown-item');
            dropdownItem.addEventListener('click', function() {
                if (!isEnabled) return; // Prevent interaction if disabled

                const value = this.dataset.value;

                // Add item to selected
                selectedItems.push(value);
                this.classList.add('selected');

                // Hide the item from dropdown
                this.closest('li').style.display = 'none';

                updateChips();
                updateHiddenInput();
            });

            if (isSelected) {
                li.style.display = 'none';
            }

            itemsContainer.appendChild(li);
        });
    }

    // Load only first 5 items into dropdown
    function loadFirstFiveItems() {
        itemsContainer.innerHTML = '';

        const firstFiveItems = Object.entries(allItemsData).slice(0, 5);
        firstFiveItems.forEach(([key, item]) => {
            const isSelected = selectedItems.includes(key);
            const li = document.createElement('li');
            li.innerHTML = `
                <div class="dropdown-item d-flex align-items-center ${isSelected ? 'selected' : ''}"
                     data-value="${key}"
                     data-item-text="${item}">
                    <span>${item}</span>
                </div>
            `;

            const dropdownItem = li.querySelector('.dropdown-item');
            dropdownItem.addEventListener('click', function() {
                if (!isEnabled) return; // Prevent interaction if disabled

                const value = this.dataset.value;

                // Add item to selected
                selectedItems.push(value);
                this.classList.add('selected');

                // Hide the item from dropdown
                this.closest('li').style.display = 'none';

                updateChips();
                updateHiddenInput();
            });

            if (isSelected) {
                li.style.display = 'none';
            }

            itemsContainer.appendChild(li);
        });
    }

    // Update hidden input with selected values
    function updateHiddenInput() {
        hiddenInput.value = JSON.stringify(selectedItems);
        console.log('updateHiddenInput called with selectedItems:', selectedItems);

        // Call Livewire method if onChanged is provided
        @if(isset($onChanged) && $onChanged)
            console.log('onChanged is set:', '{{ $onChanged }}');
            let onChanged = '{{ $onChanged }}';
            console.log('Calling method with params:', onChanged, selectedItems);

            // Get the Livewire component using helper function
            let comp = getLivewireComponent();

            // Call the method if component is found
            if (comp) {
                try {
                    comp.call(onChanged, selectedItems);
                    console.log('Method called successfully:', onChanged, selectedItems);
                } catch (error) {
                    console.error('Error calling method:', error);
                }
            } else {
                console.error('No Livewire component found');
            }
        @else
            console.log('onChanged is not set');
        @endif
    }

    // Clear search when dropdown is opened
    chipsContainer.addEventListener('click', function() {
        if (!isEnabled) return; // Prevent interaction if disabled

        setTimeout(() => {
            searchInput.value = '';
            loadFirstFiveItems();
            searchInput.focus();
        }, 100);
    });

    // Handle keyboard navigation
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            // Select first visible and non-selected item
            const visibleItems = Array.from(itemsContainer.querySelectorAll('li')).filter(item =>
                item.style.display !== 'none' && !item.querySelector('.dropdown-item').classList.contains('selected')
            );
            if (visibleItems.length > 0) {
                const firstDropdownItem = visibleItems[0].querySelector('.dropdown-item');
                if (firstDropdownItem) {
                    firstDropdownItem.click();
                }
            }
        }
    });

        // Listen for selectedItemsUpdated event from Livewire
    container.addEventListener('selectedItemsUpdated', function() {
        // Get updated data from Livewire
        const livewireSelectedItems = @json($selectedItems);
        const livewireItems = @json($items);

        // Update local selectedItems (ensure they are strings)
        selectedItems = (livewireSelectedItems || []).map(item => String(item));

        // Update allItemsData (ensure keys are strings)
        allItemsData = {};
        if (livewireItems) {
            Object.keys(livewireItems).forEach(key => {
                allItemsData[String(key)] = livewireItems[key];
            });
        }

        // Update display
        updateChips();
        updateHiddenInput();

        // Reload dropdown items
        if (searchInput.value.length > 0) {
            loadAllItems();
        } else {
            loadFirstFiveItems();
        }

                // Call onChanged if provided
        @if(isset($onChanged) && $onChanged)
            let onChanged = '{{ $onChanged }}';
            let comp = getLivewireComponent();

            if (comp) {
                try {
                    comp.call(onChanged, selectedItems);
                    console.log('onChanged called from selectedItemsUpdated event');
                } catch (error) {
                    console.error('Error calling onChanged from selectedItemsUpdated:', error);
                }
            }
        @endif
    });

    // Listen for Livewire updates
    document.addEventListener('livewire:init', function() {
        Livewire.on('selectedItemsUpdated', function() {
            // Get current data from Livewire
            const livewireSelectedItems = @this.selectedItems;
            const livewireItems = @this.items;

            // Update local data (ensure they are strings)
            selectedItems = (livewireSelectedItems || []).map(item => String(item));

            // Update allItemsData (ensure keys are strings)
            allItemsData = {};
            if (livewireItems) {
                Object.keys(livewireItems).forEach(key => {
                    allItemsData[String(key)] = livewireItems[key];
                });
            }

            // Update display
            updateChips();
            updateHiddenInput();

            // Reload dropdown items
            if (searchInput.value.length > 0) {
                loadAllItems();
            } else {
                loadFirstFiveItems();
            }

                        // Call onChanged if provided
            @if(isset($onChanged) && $onChanged)
                let onChanged = '{{ $onChanged }}';
                let comp = getLivewireComponent();

                if (comp) {
                    try {
                        comp.call(onChanged, selectedItems);
                        console.log('onChanged called from Livewire event');
                    } catch (error) {
                        console.error('Error calling onChanged from Livewire event:', error);
                    }
                }
            @endif

            console.log('Component updated from Livewire event:', { items: allItemsData, selectedItems: selectedItems });
        });
    });

    // Listen for forceUpdate event
    container.addEventListener('forceUpdate', function(event) {
        const newItems = event.detail.items;
        const newSelectedItems = event.detail.selectedItems;

        // Update local data
        selectedItems = newSelectedItems || [];
        allItemsData = newItems || {};

        // Update display
        updateChips();
        updateHiddenInput();

        // Reload dropdown items
        if (searchInput.value.length > 0) {
            loadAllItems();
        } else {
            loadFirstFiveItems();
        }

                // Call onChanged if provided
        @if(isset($onChanged) && $onChanged)
            let onChanged = '{{ $onChanged }}';
            let comp = getLivewireComponent();

            if (comp) {
                try {
                    comp.call(onChanged, selectedItems);
                    console.log('onChanged called from forceUpdate event');
                } catch (error) {
                    console.error('Error calling onChanged from forceUpdate event:', error);
                }
            }
        @endif

        console.log('Component updated:', { items: allItemsData, selectedItems: selectedItems });
    });
});
</script>
