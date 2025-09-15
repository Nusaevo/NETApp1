@php
     $blankValue = isset($type) && $type === 'int' ? '0' : '';
    $colClass = 'col-sm' . (!empty($label) ? ' mb-5' : '');
    $containerClass = !empty($label) ? 'form-floating flex-grow-1' : 'flex-grow-1';
    // Determine enabled state externally.
    $isEnabled = isset($enabled) && ($enabled === 'always' || $enabled === 'true');

    // Generate ID if not provided - this is the key fix!
    $componentId = $id ?? str_replace(['.', '[', ']'], '_', $model ?? 'textfield_' . uniqid());

    // Debug the ID
    if (empty($componentId)) {
        $componentId = 'textfield_' . uniqid();
    }
@endphp

<div wire:key="{{ $componentId }}-textfield-search" class="{{ $colClass }} ui-text-field-search" @if (isset($span)) span="{{ $span }}" @endif
    @if (isset($visible) && $visible === 'false') style="display: none;" @endif>
    <!-- Input-group dengan position relative untuk overlay -->
    <div class="input-group position-relative">
        <!-- This container is ignored by Livewire for select2 handling -->
        <div wire:ignore class="{{ $containerClass }}" x-data x-init="() => {
                const initSelect2 = () => {
                    const selectElement = document.getElementById('{{ $componentId }}');
                    if (!selectElement) {
                        return;
                    }

                    // Check if jQuery and Select2 are available
                    if (typeof $ === 'undefined' || typeof $.fn.select2 === 'undefined') {
                        return;
                    }

                    try {
                        // If instance already exists, destroy it first
                        if ($(selectElement).hasClass('select2-hidden-accessible')) {
                            $(selectElement).select2('destroy');
                        }

                        // Initialize Select2 with proper configuration
                        $(selectElement).select2({
                            placeholder: '{{ $placeHolder ?? "Select an option" }}',
                            allowClear: true,
                            width: '100%',
                            minimumResultsForSearch: {{ count($options ?? []) > 10 ? '0' : 'Infinity' }},
                            // Ensure search functionality remains active
                            escapeMarkup: function(markup) {
                                return markup;
                            }
                        });

                        // Remove any existing event handlers
                        $(selectElement).off('change.textfieldSearch');

                        // Bind change event WITHOUT re-initialization
                        $(selectElement).on('change.textfieldSearch', function (e) {
                            const value = $(this).val();

                            try {
                                // Update Livewire model
                                @this.set('{{ $model }}', value);

                                // Handle onChanged callback if provided
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
                                        }
                                    } else {
                                        $wire.call(onChanged, value);
                                    }
                                @endif
                            } catch (error) {
                                // Silent error handling
                            }
                        });

                        // Set initial value if available
                        const currentValue = '{{ $selectedValue ?? "" }}';
                        if (currentValue && currentValue !== '{{ $blankValue }}') {
                            $(selectElement).val(currentValue).trigger('change.select2');
                        }

                    } catch (error) {
                        // Silent error handling
                    }
                };

                // Initialize once with delay to ensure DOM is ready
                setTimeout(() => {
                    initSelect2();
                }, 50);
            }">

            <select id="{{ $componentId }}"
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
                <label for="{{ $componentId }}" class="@if (isset($required) && $required === 'true') required @endif">
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

        <!-- Overlay untuk disabled state - full height -->
        @if (!$isEnabled)
            <div class="disabled-overlay"></div>
        @endif

        <!-- Button for Click Event -->
        @if (isset($clickEvent) && $clickEvent !== '')
            <x-ui-button type="InputButton" :clickEvent="$clickEvent" cssClass="btn btn-secondary"
                :buttonName="$buttonName" :action="$action" :enabled="$buttonEnabled" loading="true" />
        @endif
    </div>
</div>
