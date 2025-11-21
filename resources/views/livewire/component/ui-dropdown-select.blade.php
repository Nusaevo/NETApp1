@php
    $id = str_replace(['.', '[', ']'], '_', $model);
    $blankValue = isset($type) && $type === 'int' ? '0' : '';

    // Menentukan class untuk kolom dan form-floating
    $colClass = 'col-sm' . (!empty($label) ? ' mb-4' : '');
    $containerClass = !empty($label) ? 'form-floating flex-grow-1' : 'flex-grow-1';

    // Determine input class based on whether there's a label
    // Height and padding now handled through CSS
    $inputClass = !empty($label) ? 'form-select' : 'form-select form-select-sm';
@endphp

<div class="{{ $colClass }}" wire:ignore.self
    @if (isset($span)) span="{{ $span }}" @endif
    @if (isset($visible) && $visible === 'false') style="display: none;" @endif>

    <div class="input-group">
        <div class="{{ $containerClass }} position-relative"
             x-data="{
                open: @entangle('showDropdown').live,
                highlightIndex: @entangle('highlightIndex').live,
                focusSearch() {
                    this.$nextTick(() => {
                        const searchInput = this.$refs.searchInput;
                        if (searchInput) {
                            searchInput.focus();
                        }
                    });
                },
                getOptionsCount() {
                    const items = document.querySelectorAll('.dropdown-option-item');
                    return items.length;
                },
                handleArrowUp() {
                    if (window.Livewire && window.Livewire.find && window.Livewire.find('{{ $this->getId() }}')) {
                        $wire.call('decrementHighlight');
                    }
                },
                handleArrowDown() {
                    if (window.Livewire && window.Livewire.find && window.Livewire.find('{{ $this->getId() }}')) {
                        $wire.call('incrementHighlight');
                    }
                },
                selectOption(value, label) {
                    // Close dropdown first
                    this.open = false;
                    // Call Livewire method instead of JavaScript handling
                    if (window.Livewire && window.Livewire.find && window.Livewire.find('{{ $this->getId() }}')) {
                        $wire.call('selectOption', value, label);
                    }
                },
                clearSelection() {
                    // Close dropdown first
                    this.open = false;
                    // Call Livewire method instead of JavaScript handling
                    if (window.Livewire && window.Livewire.find && window.Livewire.find('{{ $this->getId() }}')) {
                        $wire.call('clearSelection');
                    }
                }
             }"
             x-on:focus-search-input.window="focusSearch()"
             :class="{ 'dropdown-open': open }"
             wire:ignore.self>

            <!-- Hidden select for reference only (no wire:model needed since we use dispatch) -->
            <select id="{{ $id }}" name="{{ isset($model) ? $model : '' }}" wire:key="{{ $id }}"
                class="d-none"
                @if (isset($required) && $required === 'true') required @endif>
                <option value="{{ $selectedValue ?? '' }}">{{ $selectedLabel ?? '' }}</option>
            </select>

            <!-- Visible searchable select display -->
            <div class="{{ $inputClass }} @error($model) is-invalid @enderror
                @if (isset($enabled) && $enabled === 'false') disabled-gray @endif ui-dropdown-display"
                {{-- Disable dropdown when in "View" mode or when "enabled" is "false" --}}
                @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled style="pointer-events: none; opacity: 0.65;" @else wire:click="openDropdown" @click="open = true; focusSearch()" style="cursor: pointer;" @endif
                wire:loading.attr="disabled">

                <!-- Display selected value (like option selected) -->
                @if(!empty($selectedLabel))
                    <!-- Text container with proper overflow handling - backend handles formatting -->
                    <span class="ui-dropdown-text">
                        {!! $selectedLabel !!}
                    </span>
                @elseif(!empty($selectedValue) && !$labelLoaded)
                    <!-- Loading state for lazy loading label -->
                    <span class="text-muted">
                        <i class="fas fa-spinner fa-spin"></i> Loading...
                    </span>
                @else
                    <!-- Empty state like blank option -->
                    &nbsp;
                @endif
            </div>

            <!-- Clear button (X) -->
            @if(!empty($selectedLabel) && !((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')))
                <button type="button"
                        class="btn position-absolute ui-dropdown-clear-btn"
                        style="right: 32px; top: 50%; transform: translateY(-50%); z-index: 10 !important; display: flex !important;"
                        wire:click="clearSelection"
                        @click.stop
                        title="Clear selection">
                    ×
                </button>
            @endif

            <!-- Dropdown arrow (like select arrow) -->
            <span class="position-absolute"
                  style="right: 10px; top: 50%; transform: translateY(-50%); pointer-events: none; z-index: 5; color: #6c757d;">
                <i class="fas fa-chevron-down"
                   :class="{ 'fa-chevron-up': open, 'fa-chevron-down': !open }"></i>
            </span>            @if (!empty($label))
                <label for="{{ $id }}" class="@if (isset($required) && $required === 'true') required @endif">
                    {{ $label }}
                </label>
            @endif

            @if (!empty($placeHolder) && empty($selectedLabel))
                <div class="placeholder-text">{{ $placeHolder }}</div>
            @endif

            @error($model)
                <div class="error-message">{{ $message }}</div>
            @enderror

            <!-- Dropdown (attached to input) -->
            <div x-show="open"
                 x-transition
                 class="position-absolute w-100 bg-white border rounded shadow"
                 style="top: calc(100% - 1px); left: 0; border-top: none; z-index: 9999; max-height: 300px; overflow: hidden;"
                 @click.away="open = false; if (window.Livewire && window.Livewire.find && window.Livewire.find('{{ $this->getId() }}')) { $wire.closeDropdown(); }">

        <!-- Search Input -->
        <div class="p-2 border-bottom">
            <input type="text"
                   x-ref="searchInput"
                   wire:model.live.debounce.250ms="textFieldSearch"
                   class="form-control form-control-sm"
                   placeholder="Ketik untuk mencari..."
                   @keydown.arrow-up.prevent="handleArrowUp()"
                   @keydown.arrow-down.prevent="handleArrowDown()"
                   @keydown.enter.prevent="if (window.Livewire && window.Livewire.find && window.Livewire.find('{{ $this->getId() }}')) { $wire.selectHighlightedOption(); }"
                   @keydown.escape="open = false; if (window.Livewire && window.Livewire.find && window.Livewire.find('{{ $this->getId() }}')) { $wire.closeDropdown(); }"
                   autocomplete="off">
        </div>

        <!-- Loading indicator -->
        <div wire:loading.delay wire:target="textFieldSearch"
             class="p-2 text-center text-muted">
            <i class="fas fa-spinner fa-spin"></i> Searching...
        </div>

        <!-- Options list -->
        <div wire:loading.remove wire:target="textFieldSearch"
             style="max-height: 250px; overflow-y: auto;">
            <ul class="list-unstyled m-0">
                @if(!empty($options))
                    @foreach ($options as $i => $option)
                        <li class="px-3 py-2 dropdown-option-item"
                            wire:key="option-{{ $option['id'] ?? $i }}"
                            :class="{
                                'bg-primary text-white option-highlighted': highlightIndex === {{ $i }},
                                'bg-white text-dark': highlightIndex !== {{ $i }}
                            }"
                            wire:click.prevent="selectOptionFromList({{ $i }})"
                            @click="highlightIndex = {{ $i }}"
                            @mouseover="highlightIndex = {{ $i }}"
                            style="cursor: pointer; transition: background-color 0.15s ease;">
                            @php
                $optionText = $option['text'] ?? $option['label'] ?? 'No label';
            @endphp
            <div class="ui-dropdown-option-text">
                {!! $optionText !!}
            </div>
                        </li>
                    @endforeach
                @elseif(!empty($textFieldSearch) && strlen($textFieldSearch) >= $minSearchLength && !$isSearching)
                    <li class="px-3 py-2 text-muted">
                        No results found
                    </li>
                @elseif(empty($textFieldSearch))
                    <li class="px-3 py-2 text-muted">

                    </li>
                @endif
            </ul>
        </div>
            </div>
        </div> <!-- Penutup div containerClass -->

        <!-- Button for Click Event -->
        @if (isset($clickEvent) && $clickEvent !== '')
            <x-ui-button type="InputButton" :clickEvent="$clickEvent" cssClass="btn btn-secondary"
                :buttonName="$buttonName" :action="$action" :enabled="$buttonEnabled" loading="true" />
        @endif
    </div>
</div>
