@php
    $id = str_replace(['.', '[', ']'], '_', $model);
    $blankValue = isset($type) && $type === 'int' ? '0' : '';
    $colClass = 'col-sm' . (!empty($label) ? ' mb-5' : '');
    $containerClass = !empty($label) ? 'form-floating flex-grow-1' : 'flex-grow-1';
    // Determine enabled state externally.
    $isEnabled = isset($enabled) && ($enabled === 'always' || $enabled === 'true');
@endphp

<div wire:key="{{ $id }}-dropdown-search" class="{{ $colClass }}"
    @if (isset($span)) span="{{ $span }}" @endif
    @if (isset($visible) && $visible === 'false') style="display: none;" @endif>

    <!-- Make the container position relative so the overlay positions correctly -->
    <div class="input-group position-relative">
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

                    // Initialize Select2 with AJAX
                    $(selectElement).select2({
                        placeholder: '{{ $placeHolder ?? 'Select an option' }}',
                        allowClear: true,
                        minimumInputLength: 1,
                        ajax: {
                            url: '/search-dropdown',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return {
                                    q: params.term || '',
                                    model: $(selectElement).data('search-model'),
                                    where: $(selectElement).data('search-where'),
                                    option_value: $(selectElement).data('option-value'),
                                    option_label: $(selectElement).data('option-label'),
                                };
                            },
                            processResults: function (data) {
                                if (data && data.results) {
                                    return { results: data.results };
                                } else {
                                    console.error('Invalid response format for {{ $id }}:', data);
                                    return { results: [] };
                                }
                            },
                            cache: false
                        },
                        language: {
                            errorLoading: function () {
                                return 'Results could not be loaded.';
                            },
                            inputTooShort: function () {
                                return 'Please enter 1 or more characters';
                            },
                            noResults: function () {
                                return 'No results found';
                            },
                            searching: function () {
                                return 'Searching...';
                            }
                        }
                    });

                    // Remove duplicate event bindings
                    $(selectElement).off('select2:select');
                    $(selectElement).off('select2:clear');

                    // Bind select event
                    $(selectElement).on('select2:select', function () {
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

                                    // Replace parameter values
                                    const processedParams = params.map(param => {
                                        if (param === '$event.target.value') {
                                            return value;
                                        }
                                        return param;
                                    });

                                    $wire.call(methodName, ...processedParams);
                                } else {
                                    console.error(`Invalid onChanged format: ${onChanged}`);
                                }
                            } else {
                                $wire.call(onChanged, value);
                            }
                        @endif
                    });

                    // Bind clear event
                    $(selectElement).on('select2:clear', function () {
                        const blankValue = '{{ $blankValue }}';
                        @this.set('{{ $model }}', blankValue);
                    });

                    console.log(`Select2 initialized for #{{ $id }}`);

                    // Restore existing value if present
                    const existingValue = '{{ $selectedValue ?? '' }}' || '{{ $this->$model ?? '' }}';
                    if (existingValue && existingValue !== '{{ $blankValue }}') {
                        // Fetch display text for existing value
                        fetch('/search-dropdown?model={{ urlencode($searchModel) }}&option_value={{ $optionValue }}&option_label={{ $optionLabel }}&where={{ urlencode($searchWhereCondition) }}&id=' + existingValue)
                            .then(response => response.json())
                            .then(data => {
                                if (data && data.results && data.results.length > 0) {
                                    const item = data.results[0];
                                    const option = new Option(item.text, item.id, true, true);
                                    $(selectElement).append(option).trigger('change');
                                }
                            })
                            .catch(error => console.warn('Failed to restore selected value:', error));
                    }
                };

                // Initialize Select2 when component is created
                initSelect2();
            }">

            <select id="{{ $id }}"
                class="form-select responsive-input @error($model) is-invalid @enderror
                    @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled-gray @endif"
                wire:model="{{ $model }}"
                data-placeholder="{{ $placeHolder ?? 'Select an option' }}"
                data-search-model="{{ $searchModel }}"
                data-search-where="{{ $searchWhereCondition }}"
                data-option-value="{{ $optionValue }}"
                data-option-label="{{ $optionLabel }}"
                @if (!$isEnabled && ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'))) disabled @endif
                wire:loading.attr="disabled">
                <option value="{{ $blankValue }}"></option>
            </select>

            @if (!empty($label))
                <label for="{{ $id }}" class="@if (isset($required) && $required === 'true') required @endif">
                    {{ $label }}
                </label>
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

        @if (isset($clickEvent) && $clickEvent !== '')
            <x-ui-button type="InputButton" :clickEvent="$clickEvent" cssClass="btn btn-secondary"
                :buttonName="$buttonName" :action="$action" :enabled="$buttonEnabled" loading="true" />
        @endif
    </div>
</div>

{{--
Enhanced UI Dropdown Search Component with AJAX Support

Features:
- Real-time AJAX search with Select2
- Multiple WHERE conditions support:
  * AND conditions: separated by & (ampersand)
  * OR conditions: separated by | (pipe) within AND groups
  * Example: "status_code=A&deleted_at=null" or "status=A|status=I&type=CUSTOMER"
- Multi-label display:
  * Separate multiple fields with comma (,)
  * Example: optionLabel="code,name" displays "ABC123 - Product Name"
- Supports operators: =, !=, >, <, null values

Example usage:
<x-ui-dropdown-search
    label="Search Partner"
    model="selectedPartnerId"
    optionValue="id"
    optionLabel="code,name,address"
    searchModel="App\Models\TrdTire1\Master\Partner"
    searchWhereCondition="status_code=A&deleted_at=null&type=SUPPLIER|type=CUSTOMER"
    placeHolder="Type to search partners..."
    selectedValue="123"
    type="int"
    required="false"
    onChanged="onPartnerSelected"
/>
--}}
