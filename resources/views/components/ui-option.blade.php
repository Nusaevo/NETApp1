@php
    $id = str_replace(['.', '[', ']'], '_', $model);
    $isVertical = isset($layout) && $layout === 'vertical';
    $isVisible = !isset($visible) || $visible === 'true';
    $isEnabled = !isset($enabled) || $enabled === 'true';
    $isRequired = isset($required) && $required === 'true';
    $isSingleCheckbox = $type === 'checkbox' && count($options) === 1;
@endphp

<!-- Container -->
<div
    class="mb-3 responsive-field"
    @if (isset($span)) span="{{ $span }}" @endif
    @if (!$isVisible) style="display: none;" @endif
>
    <!-- Single Checkbox (Yes/No) dengan Field Label di Kiri -->
    @if ($isSingleCheckbox)
        @php
            $key = array_key_first($options);
            $optionLabel = $options[$key];
        @endphp
        <div class="d-flex align-items-center gap-2">
            <label for="checkbox_{{ $id }}_{{ $key }}" class="form-check-label fw-bold">
                {{ $label }} :
            </label>
            <input
                type="checkbox"
                wire:model="{{ $model }}"
                wire:change="{{ $onChanged ?? '' }}"
                id="checkbox_{{ $id }}_{{ $key }}"
                class="form-check-input"
                value=false
                @if (!$isEnabled) disabled @endif
            />
            <label class="form-check-label fw-bold">
                {{ $optionLabel }}
            </label>
        </div>

    <!-- Multiple Checkbox -->
    @elseif ($type === 'checkbox')
        @if (!empty($label))
            <div class="responsive-label mb-2">
                <label class="{{ $isRequired ? 'required' : '' }}">
                    {{ $label }} :
                </label>
            </div>
        @endif
        <div class="form-options"
            style="
                display: flex;
                flex-wrap: {{ $isVertical ? 'wrap' : 'nowrap' }};
                flex-direction: {{ $isVertical ? 'column' : 'row' }};
                gap: 10px;
            "
        >
            @foreach ($options as $key => $optionLabel)
                <div class="form-check">
                    <input
                        type="checkbox"
                        wire:model="{{ $model . '.' . $key }}"
                        wire:change="{{ $onChanged ?? '' }}"
                        id="checkbox_{{ $id }}_{{ $key }}"
                        class="form-check-input"
                        @if (!$isEnabled) disabled @endif
                    />
                    <label for="checkbox_{{ $id }}_{{ $key }}" class="form-check-label">
                        {{ $optionLabel }}
                    </label>
                </div>
            @endforeach
        </div>

    <!-- Radio Button -->
    @elseif ($type === 'radio')
        @if (!empty($label))
            <div class="responsive-label mb-2">
                <label class="{{ $isRequired ? 'required' : '' }}">
                    {{ $label }} :
                </label>
            </div>
        @endif
        <div class="form-options"
            style="
                display: flex;
                flex-wrap: {{ $isVertical ? 'wrap' : 'nowrap' }};
                flex-direction: {{ $isVertical ? 'column' : 'row' }};
                gap: 10px;
            "
        >
            @foreach ($options as $key => $optionLabel)
                <div class="form-check">
                    <input
                        type="radio"
                        wire:model="{{ $model }}"
                        wire:change="{{ $onChanged ?? '' }}"
                        id="radio_{{ $id }}_{{ $key }}"
                        class="form-check-input"
                        value="{{ $key }}"
                        @if (!$isEnabled) disabled @endif
                    />
                    <label for="radio_{{ $id }}_{{ $key }}" class="form-check-label">
                        {{ $optionLabel }}
                    </label>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Validation Error -->
    @error($model)
        <div class="error-message text-danger">{{ $message }}</div>
    @enderror
</div>
