@php
    $id = str_replace(['.', '[', ']'], '_', $model);
    $blankValue = (isset($type) && $type === 'int') ? '0' : '';
@endphp

<div class="col-sm mb-5"
     @if(isset($span)) span="{{ $span }}" @endif
     @if(isset($visible) && $visible === 'false') style="display: none;" @endif>
    <div class="d-flex align-items-center">
        <div class="form-floating flex-grow-1">
            <select id="{{ $id }}" name="{{ isset($model) ? $model : '' }}" wire:key="{{ $id }}"
                    @if(isset($modelType) && $modelType === 'lazy') wire:model.lazy="{{ isset($model) ? $model : '' }}"
                    @else wire:model="{{ isset($model) ? $model : '' }}"
                    @endif
                    @if (isset($onChanged) && $onChanged) wire:change="{{ $onChanged }}" @endif
                    class="form-select @error($model) is-invalid @enderror @if (isset($enabled) && $enabled === 'false') disabled-gray @endif"
                    {{-- Disable dropdown when in "View" mode or when "enabled" is "false" --}}
                    @if (isset($action) && $action === 'View' || (isset($enabled) && $enabled === 'false')) disabled @endif
                    @if (isset($required) && $required === 'true') required @endif
                    wire:loading.attr="disabled">

                <!-- Blank option with dynamic value -->
                <option value="{{ $blankValue }}"></option>

                @if (!is_null($options))
                    @forelse ($options as $option)
                        <option value="{{ $option['value'] }}"
                            @if(isset($model) && $model === $option['value']) selected
                            @elseif($selectedValue === $option['value']) selected
                            @endif>
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

        <!-- Button for Click Event -->
        @if (isset($clickEvent) && $clickEvent !== '')
            <div class="d-flex align-items-center ms-2">
                <!-- Button when not loading -->
                <span wire:loading.remove wire:target="{{ $clickEvent }}">
                    <x-ui-button
                        :clickEvent="$clickEvent"
                        cssClass="btn btn-secondary"
                        :buttonName="$buttonName ?? 'Click'"
                        :action="$action ?? ''"
                        :enabled="$enabled ?? true"
                    />
                </span>
                <!-- Loading Spinner -->
                <span wire:loading wire:target="{{ $clickEvent }}">
                    <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
                </span>
            </div>
        @endif
    </div>
</div>
