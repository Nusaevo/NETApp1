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
        <div class="{{ $containerClass }}">
            <select id="{{ $id }}" name="{{ isset($model) ? $model : '' }}" wire:key="{{ $id }}"
                @if (isset($modelType) && $modelType === 'lazy') wire:model.lazy="{{ isset($model) ? $model : '' }}"
                @else wire:model="{{ isset($model) ? $model : '' }}" @endif
                @if (isset($onChanged) && $onChanged) wire:change="{{ $onChanged }}" @endif
                class="{{ $inputClass }} @error($model) is-invalid @enderror
                @if (isset($enabled) && $enabled === 'false') disabled-gray @endif"
                {{-- Disable dropdown when in "View" mode or when "enabled" is "false" --}}
                @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                @if (isset($required) && $required === 'true') required @endif wire:loading.attr="disabled">

                <!-- Blank option with dynamic value -->
                <option value="{{ $blankValue }}"></option>

                @if (!is_null($options))
                    @foreach ($options as $option)
                        <option value="{{ $option['value'] }}"
                            @if (isset($model) && $model === $option['value']) selected
                            @elseif($selectedValue === $option['value']) selected @endif>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                @endif
            </select>

            @if (!empty($label))
                <label for="{{ $id }}" class="@if (isset($required) && $required === 'true') required @endif">
                    {{ $label }}
                </label>
            @endif

            @if (!empty($placeHolder))
                <div class="placeholder-text">{{ $placeHolder }}</div>
            @endif

            @error($model)
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div> <!-- Penutup div containerClass -->

        <!-- Button for Click Event -->
        @if (isset($clickEvent) && $clickEvent !== '')
            <x-ui-button type="InputButton" :clickEvent="$clickEvent" cssClass="btn btn-secondary"
                :buttonName="$buttonName" :action="$action" :enabled="$buttonEnabled" loading="true" />
        @endif
    </div>
</div>
