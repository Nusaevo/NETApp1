@php
    $id = 'multiple_select_' . uniqid();
    $isEnabled = !((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'));
@endphp

<div wire:ignore>
    <div class="dropdown" data-bs-auto-close="outside" id="{{ $id }}_container">
        <div class="selected-chips-container {{ !$isEnabled ? 'disabled' : '' }}"
             id="multiSelectToggle_{{ $id }}"
             @if($isEnabled) data-bs-toggle="dropdown" aria-expanded="false" @endif>
            <small>{{ $label ?? 'Multi-select' }}</small>
            <div id="selectedContainer_{{ $id }}" class="badge-group chips-placeholder">
                {{ $placeHolder ?? 'Please select...' }}
            </div>
            <span class="arrow">▾</span>
        </div>

        <ul class="dropdown-menu w-100" aria-labelledby="multiSelectToggle_{{ $id }}" id="multiSelectMenu_{{ $id }}">
            @foreach ($options as $option)
                <li>
                    <div class="dropdown-item d-flex align-items-center {{ in_array((string)$option['value'], (array)$model) ? 'selected' : '' }}"
                         data-value="{{ (string)$option['value'] }}"
                         data-item-text="{{ $option['label'] }}">
                        <span>{{ $option['label'] }}</span>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>

<style>
.selected-chips-container {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-height: 56px;
    padding: 10px 40px 10px 12px;
    border: 1px solid #dcdcdc;
    border-radius: 10px;
    background-color: white;
    cursor: pointer;
    position: relative;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.selected-chips-container:hover {
    border-color: #adb5bd;
}

.selected-chips-container.disabled {
    background-color: #e9ecef;
    pointer-events: none;
    opacity: 0.65;
}

.arrow {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
}

.badge-group {
    display: flex;
    gap: .35rem;
    flex-wrap: wrap;
    align-items: center;
    min-height: 20px;
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
    cursor: pointer;
    user-select: none;
    position: relative;
}

.selected-chip:hover {
    background-color: #353638;
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

.chips-placeholder {
    color: #98a2b3;
    font-style: italic;
}

.dropdown-item {
    cursor: pointer;
    padding: 0.5rem 1rem;
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

.dropdown-item.selected {
    background-color: #e7f3ff;
    color: #0d6efd;
    font-weight: 500;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('UiDropdownMultipleSelect initialized for ID:', '{{ $id }}');
    const container = document.getElementById('{{ $id }}_container');
    const menu = document.getElementById('multiSelectMenu_{{ $id }}');
    const chipsContainer = document.getElementById('selectedContainer_{{ $id }}');
    const dropdownItems = menu.querySelectorAll('.dropdown-item');
    const isEnabled = {{ $isEnabled ? 'true' : 'false' }};
    const onChangedEvent = '{{ $onChanged ?? "" }}';

    console.log('Container found:', !!container);
    console.log('Menu found:', !!menu);
    console.log('Chips container found:', !!chipsContainer);
    console.log('Dropdown items count:', dropdownItems.length);
    console.log('Is enabled:', isEnabled);
    console.log('OnChanged event:', onChangedEvent);

    let selectedItems = (@json($model ?? [])).map(v => String(v));
    let allItemsData = {};
    dropdownItems.forEach(item => {
        allItemsData[String(item.dataset.value)] = item.dataset.itemText;
    });

    console.log('Initial selectedItems:', selectedItems);
    console.log('All items data:', allItemsData);

    // Initial render
    updateChips();
    updateMenu();

    // Handle click on dropdown item
    dropdownItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Dropdown item clicked:', this.dataset.value, this.dataset.itemText);
            if (!isEnabled) return;
            const value = String(this.dataset.value);
            if (!selectedItems.includes(value)) {
                selectedItems.push(value);
                console.log('Added item to selectedItems:', value);
            }
            updateChips();
            updateMenu();
            updateLivewire();
        });
    });

    // Prevent closing dropdown on click inside
    menu.addEventListener('click', e => e.stopPropagation());

    function updateChips() {
        console.log('updateChips called with selectedItems:', selectedItems);
        chipsContainer.innerHTML = '';
        if (selectedItems.length === 0) {
            chipsContainer.textContent = '{{ $placeHolder ?? "Please select..." }}';
            chipsContainer.classList.add('chips-placeholder');
            return;
        }
        chipsContainer.classList.remove('chips-placeholder');

        selectedItems.forEach(val => {
            const label = allItemsData[val] || val;
            console.log('Creating chip for:', val, 'with label:', label);
            const chip = document.createElement('span');
            chip.className = 'selected-chip';
            chip.setAttribute('data-value', val);
            chip.innerHTML = `${label}<span class="chip-remove"> ×</span>`;

            chip.addEventListener('click', e => {
                e.stopPropagation();
                console.log('Removing item:', val);
                selectedItems = selectedItems.filter(v => v !== val);
                updateChips();
                updateMenu();
                updateLivewire();
            });
            chipsContainer.appendChild(chip);
        });
    }

    function updateMenu() {
        menu.querySelectorAll('.dropdown-item').forEach(item => {
            const value = String(item.dataset.value);
            if (selectedItems.includes(value)) {
                item.closest('li').style.display = 'none';
            } else {
                item.closest('li').style.display = 'block';
            }
        });
    }

    function updateLivewire() {
        if (window.Livewire) {
            const comp = Livewire.find(container.closest('[wire\\:id]')?.getAttribute('wire:id'));
            if (comp) {
                comp.set('selectedItems', selectedItems);
                if (onChangedEvent) {
                    comp.call(onChangedEvent, selectedItems);
                }
            }
        }
    }

    // Listen for updates from Livewire
    Livewire.on('selectedItemsUpdated', function() {
        // Get the current Livewire component
        const comp = Livewire.find(container.closest('[wire\\:id]')?.getAttribute('wire:id'));
        if (comp) {
            selectedItems = (comp.get('selectedItems') || []).map(v => String(v));
            const items = comp.get('items') || [];
            allItemsData = {};
            items.forEach(opt => {
                allItemsData[String(opt.value)] = opt.label;
            });
            updateChips();
            updateMenu();
        }
    });
});
</script>
