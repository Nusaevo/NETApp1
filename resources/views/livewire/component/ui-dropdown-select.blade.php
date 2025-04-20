@php
    $id = str_replace(['.', '[', ']'], '_', $model);
    $blankValue = (isset($type) && $type === 'int') ? '0' : '';
@endphp

<div class="relative">
    <!-- Search Input -->
    <input type="text"
           id="{{ $id }}_search"
           wire:model.debounce.500ms="searchTerm"
           class="form-control border border-gray-300 rounded-md shadow-sm focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
           placeholder="{{ $placeHolder ?? 'Search...' }}"
           wire:keydown.arrow-up="decrementHighlight"
           wire:keydown.arrow-down="incrementHighlight"
           wire:keydown.enter="selectOption"
           wire:keydown.escape="reset"
           wire:keydown.tab="reset"
           autocomplete="off"
           aria-autocomplete="list">

    <!-- Dropdown Options -->
    <div class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg"
         style="display: {{ $searchTerm || count($options) > 0 ? 'block' : 'none' }};">
        @if($isSearching)
            <div class="p-2 text-gray-500 text-sm">Searching...</div>
        @elseif(!empty($options))
            @foreach ($options as $i => $option)
                <div class="p-2 cursor-pointer hover:bg-indigo-500 hover:text-white {{ $highlightIndex === $i ? 'bg-indigo-500 text-white' : '' }}"
                     wire:click="selectOption"
                     wire:keydown.enter.prevent="selectOption"
                     wire:keydown.arrow-up.prevent="decrementHighlight"
                     wire:keydown.arrow-down.prevent="incrementHighlight">
                    {{ $option['label'] }}
                </div>
            @endforeach
        @else
            <div class="p-2 text-gray-500 text-sm">No results found!</div>
        @endif
    </div>

    @error($model)
        <div class="mt-1 text-red-500 text-sm">{{ $message }}</div>
    @enderror
</div>
