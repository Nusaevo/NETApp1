@php
    $id = str_replace(['.', '[', ']'], '_', $model);
    $isVertical = isset($layout) && $layout === 'vertical';
    $isVisible = !isset($visible) || $visible === 'true';
    $isEnabled = !isset($enabled) || $enabled === 'true';
    $isRequired = isset($required) && $required === 'true';
@endphp

<!-- Container -->
<div
    class="mb-3 responsive-field"
    @if (isset($span)) span="{{ $span }}" @endif
    @if (!$isVisible) style="display: none;" @endif
>
    <!-- Label -->
    @if (!empty($label))
        <div class="responsive-label mb-2">
            <label class="{{ $isRequired ? 'required' : '' }}">
                {{ $label }}:
            </label>
        </div>
    @endif

    <!-- Checkboxes -->
    <div
        class="form-options"
        style="
            display: flex;
            flex-wrap: {{ $isVertical ? 'wrap' : 'nowrap' }};
            flex-direction: {{ $isVertical ? 'column' : 'row' }};
            gap: 10px;
        "
    >
        @foreach ($options as $key => $optionLabel)
            <div
                class="form-check"
                style="{{ $isVertical ? 'margin-bottom: 5px;' : 'margin-right: 15px;' }}"
            >
                <input
                    type="checkbox"
                    wire:model="{{ $model }}.{{ $key }}"
                    id="checkbox_{{ $id }}_{{ $key }}"
                    class="form-check-input"
                    @if (!$isEnabled) disabled @endif
                />
                <label
                    for="checkbox_{{ $id }}_{{ $key }}"
                    class="form-check-label"
                >
                    {{ $optionLabel }}
                </label>
            </div>
        @endforeach
    </div>

    <!-- Validation Error -->
    @error($model)
        <div class="error-message text-danger">{{ $message }}</div>
    @enderror
</div>
