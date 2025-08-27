@php
     $blankValue = isset($type) && $type === 'int' ? '0' : '';
    $colClass = 'col-sm' . (!empty($label) ? ' mb-5' : '');
    $containerClass = !empty($label) ? 'form-floating flex-grow-1' : 'flex-grow-1';
    // Determine enabled state externally.
    $isEnabled = isset($enabled) && ($enabled === 'always' || $enabled === 'true');
@endphp

<div wire:key="{{ $id }}-select2"   class="{{ $colClass }}" @if (isset($span)) span="{{ $span }}" @endif
    @if (isset($visible) && $visible === 'false') style="display: none;" @endif>
    <!-- Make the container position relative so the overlay positions correctly -->
    <div class="input-group">
        <!-- This container is ignored by Livewire for select2 handling -->
        <div wire:ignore class="{{ $containerClass }}" x-data x-init="() => {
                const initSelect2 = () => {
                    const selectElement = document.getElementById('{{ $id }}');
                    if (!selectElement) {
                        console.warn(`Element #{{ $id }} not found.`);
                        return;
                    }
                    // If instance already exists, destroy it first
                    if ($(selectElement).hasClass('select2-hidden-accessible')) {
                        $(selectElement).select2('destroy');
                    }
                    // Initialize Select2
                    $(selectElement).select2();
                    // Remove duplicate event bindings
                    $(selectElement).off('change');
                    // Bind change event
                    $(selectElement).on('change', function () {
                        const value = $(this).val();
                        @this.set('{{ $model }}', value);

                        @if (isset($onChanged) && $onChanged)
                            let onChanged = '{{ $onChanged }}';
                            if (onChanged.includes('$event.target.value')) {
                                onChanged = onChanged.replace('$event.target.value', value);
                            }
                            if (onChanged.includes('(')) {
                                const matches = onChanged.match(/^([\w.]+)\((.*)\)$/);
                                if (matches) {
                                    const methodName = matches[1];
                                    const params = matches[2]
                                        .split(',')
                                        .map(param => param.trim())
                                        .filter(param => param !== '');
                                    $wire.call(methodName, ...params);
                                } else {
                                    console.error(`Invalid onChanged format: ${onChanged}`);
                                }
                            } else {
                                $wire.call(onChanged, value);
                            }
                        @endif

                        // Reinitialize Select2 for this element after change
                        initSelect2();
                    });
                    console.log(`Select2 initialized for #{{ $id }}`);
                };
                // Initialize Select2 when component is created
                initSelect2();
            }">

            <select id="{{ $id }}"
                class="form-select  @error($model) is-invalid @enderror
                    @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled-gray @endif"
                wire:model="{{ $model }}"
                wire:loading.attr="disabled">
                <option value="{{ $blankValue }}"></option>
                @if (!is_null($options))
                    @foreach ($options as $option)
                        <option value="{{ $option['value'] }}"
                        {{ isset($selectedValue) && $selectedValue === $option['value'] ? 'selected' : '' }}>
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
        </div>

        <!-- Overlay for disabled state (outside of wire:ignore) -->
        @if (!$isEnabled)
            <div class="disabled-overlay" style="
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: gray;
                    opacity: 0.1;
                    cursor: not-allowed;
                    z-index: 10;">
            </div>
        @endif
    <!-- Button for Click Event -->
        @if (isset($clickEvent) && $clickEvent !== '')
            <x-ui-button type="InputButton" :clickEvent="$clickEvent" cssClass="btn btn-secondary"
                :buttonName="$buttonName" :action="$action" :enabled="$buttonEnabled" loading="true" />
        @endif
    </div>
</div>
