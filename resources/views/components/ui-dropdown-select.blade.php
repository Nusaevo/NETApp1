@php
$id = str_replace(['.', '[', ']'], '_', $model);
$blankValue = (isset($type) && $type === 'int') ? '0' : '';
@endphp

<div wire:ignore.self class="col-sm mb-5" @if(isset($span)) span="{{ $span }}" @endif>
    <div class="form-floating">
        <select id="{{ $id }}" name="{{ isset($model) ? $model : '' }}"
                @if(isset($modelType) && $modelType === 'lazy') wire:model.lazy="{{ isset($model) ? $model : '' }}"
                @else wire:model="{{ isset($model) ? $model : '' }}"
                @endif
                @if (isset($onChanged) && $onChanged) wire:change="{{ $onChanged }}" @endif
                class="form-select @error($model) is-invalid @enderror @if (isset($enabled) && $enabled === 'false') disabled-gray @endif"
                @if (!(isset($enabled) && ($enabled === 'always' || $enabled === 'true')) && (isset($action) && $action === 'View' || (isset($enabled) && $enabled === 'false'))) disabled @endif
                @if (isset($required) && $required === 'true') required @endif
                wire:loading.attr="disabled">

            <!-- Blank option with dynamic value -->
            <option value="{{ $blankValue }}"></option>

            @if (!is_null($options))
                @forelse ($options as $option)
                    <option value="{{ $option['value'] }}" @if(isset($model) && $model === $option['value']) selected @endif>
                        {{ $option['label'] }}
                    </option>
                @empty
                    <!-- No options available -->
                @endforelse
            @endif
        </select>

        @if (!empty($label))
            <label for="{{ $id }}" class="@if(isset($required) && $required === 'true') required @endif">{{ $label }}</label>
        @endif
        @if(!empty($placeHolder))
            <div class="placeholder-text">{{ $placeHolder }}</div>
        @endif
        @error($model)
            <div class="error-message">{{ $message }}</div>
        @enderror
    </div>

    <!-- Refresh Button -->
    @if (isset($clickEvent) && $clickEvent !== '')
        @if ((isset($enabled) && ($enabled === 'always' || $enabled === 'true')) || ((!empty($action) && $action !== 'View') && (isset($enabled) && $enabled !== 'false')))
            <button type="button" wire:click="{{ $clickEvent }}" wire:loading.attr="disabled" class="btn btn-secondary btn-sm" data-toggle="tooltip" title="Refresh your search to get the latest data"
                    @if (!(isset($enabled) && ($enabled === 'always' || $enabled === 'true')) && ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'))) disabled @endif>
                <i class="bi bi-arrow-repeat"></i>
            </button>
        @endif
    @endif
</div>
