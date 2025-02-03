@php
    $id = str_replace(['.', '[', ']'], '_', $model);
    $isVertical = isset($layout) && $layout === 'vertical';
    $isVisible = !isset($visible) || $visible === 'true';
    $isEnabled = (isset($action) && $action === 'View')
        ? 'false'
        : ((!isset($enabled) || $enabled === 'true') ? 'true' : 'false');
    $isRequired = isset($required) && $required === 'true' ? 'true' : 'false';

    // Jika options kosong atau hanya memiliki satu opsi
    $isSingleOption = empty($options) || count($options) === 1;

    // Menentukan apakah harus menampilkan single checkbox atau radio
    $isSingleCheckbox = $isSingleOption && $type === 'checkbox';
    $isSingleRadio = $isSingleOption && $type === 'radio';

    // Tentukan nilai key dan label untuk single option
    $key = empty($options) ? 'true' : array_key_first($options);
    $optionLabel = empty($options) ? 'Yes' : $options[$key];
@endphp

<div
   class="col-sm m-2"
    @if (isset($span)) span="{{ $span }}" @endif
    @if ($isVisible === 'false') style="display: none;" @endif
>
    <!-- Single Checkbox -->
    @if ($isSingleCheckbox)
        <div class="d-flex align-items-center gap-2">
            @if (!empty($label))
                <label for="checkbox_{{ $id }}_{{ $key }}" class="form-check-label fw-bold">
                    {{ $label }} :
                </label>
            @endif
            <input
                type="checkbox"
                wire:model="{{ $model }}"
                wire:change="{{ $onChanged ?? '' }}"
                id="checkbox_{{ $id }}_{{ $key }}"
                class="form-check-input"
                value="1"
                @if ($isEnabled === 'false') disabled @endif
            />
            <label for="checkbox_{{ $id }}_{{ $key }}" class="form-check-label fw-bold">
                {{ $optionLabel }}
            </label>
        </div>

    <!-- Single Radio -->
    @elseif ($isSingleRadio)
        <div class="d-flex align-items-center gap-2">
            @if (!empty($label))
                <label for="radio_{{ $id }}_{{ $key }}" class="form-check-label fw-bold">
                    {{ $label }} :
                </label>
            @endif
            <input
                type="radio"
                wire:model="{{ $model }}"
                wire:change="{{ $onChanged ?? '' }}"
                id="radio_{{ $id }}_{{ $key }}"
                class="form-check-input"
                value="1"
                @if ($isEnabled === 'false') disabled @endif
            />
            <label for="radio_{{ $id }}_{{ $key }}" class="form-check-label fw-bold">
                {{ $optionLabel }}
            </label>
        </div>

    <!-- Multiple Checkbox -->
    @elseif ($type === 'checkbox')
        @if (!empty($label))
            <div class="responsive-label">
                <label class="{{ $isRequired === 'true' ? 'required' : '' }}">
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
                        @if ($isEnabled === 'false') disabled @endif
                    />
                    <label for="checkbox_{{ $id }}_{{ $key }}" class="form-check-label">
                        {{ $optionLabel }}
                    </label>
                </div>
            @endforeach
        </div>

    <!-- Multiple Radio -->
    @elseif ($type === 'radio')
        @if (!empty($label))
            <div class="responsive-label">
                <label class="{{ $isRequired === 'true' ? 'required' : '' }}">
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
                        @if ($isEnabled === 'false') disabled @endif
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
