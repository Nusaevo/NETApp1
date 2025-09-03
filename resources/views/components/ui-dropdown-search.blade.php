@php
    $id = str_replace(['.', '[', ']'], '_', $model);
    $blankValue = isset($type) && $type === 'int' ? '0' : '';
    $colClass = 'col-sm' . (!empty($label) ? ' mb-4' : '');
    $containerClass = !empty($label) ? 'form-floating flex-grow-1' : 'flex-grow-1';
    // Determine enabled state externally.
    $isEnabled = isset($enabled) && ($enabled === 'always' || $enabled === 'true');

    // Use standard form-select class for consistent sizing
    $inputClass = 'form-select';

    // Default to session app_code if connection is not specified
    // Use 'Default' as a special value - the controller will interpret this
    // as a request to use the app_code session variable
    $dbConnection = isset($connection) ? $connection : 'Default';

    // Handle SQL query if provided, using component property if available
    // Access the raw query value directly from the component property
    $rawQuery = $query ?? '';

     // Escape single quotes for HTML attributes and JavaScript
    // Replace single quotes with encoded version to avoid issues
    $escapedQuery = !empty($rawQuery) ? str_replace("'", "\'", (string)$rawQuery) : '';

    // Force SQL query processing and ensure it's a string - must not be empty
    $sqlQuery = !empty($rawQuery) ? (string)$rawQuery : '';

    // Ensure we never have empty query in the output
    if (empty($sqlQuery)) {
        // \Log::warning('Empty SQL query in dropdown', ['component_id' => $id]);
    }

    // Handle model-based params (backward compatibility)
    $hasModelParams = !empty($searchModel) && !empty($searchWhereCondition);
    $hasQueryParam = !empty($sqlQuery);

    // Generate a unique ID for this instance to avoid conflicts
    $uniqueId = uniqid('select2_');
@endphp

<!-- Custom styles moved to pagebase.css -->
<style>
    /* Multi-line support for dropdown results */
    .select2-container .select2-results__option {
        white-space: pre-line; /* Preserve line breaks */
        line-height: 1.4;
    }

    /* Selected value styling - more compact */
    .select2-container .select2-selection__rendered {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<div wire:key="{{ $id }}-dropdown-search" class="{{ $colClass }}"
    @if (isset($span)) span="{{ $span }}" @endif
    @if (isset($visible) && $visible === 'false') style="display: none;" @endif>

    <!-- Make the container position relative so the overlay positions correctly -->
    <div class="input-group position-relative">
        <!-- This container is ignored by Livewire for select2 handling -->
        <div wire:ignore class="{{ $containerClass }} position-relative" x-data x-init="() => {
                const initSelect2 = () => {
                    const selectElement = document.getElementById('{{ $id }}');
                    if (!selectElement) {
                        // console.warn(`Element #{{ $id }} not found.`);
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
                        width: '100%',
                        // Use standard size Select2 that matches form-select
                        // Show the clear button for all types
                        templateSelection: function(data) {
                            if (!data.id || data.id === '') {
                                return $('<span>' + data.text + '</span>');
                            }

                            // Handle line breaks in selected value - convert to spaces for compact display
                            var displayText = data.text;
                            if (displayText && displayText.includes('\n')) {
                                displayText = displayText.replace(/\n/g, ' | ');
                            }

                            return $('<span>' + displayText + '</span>');
                        },
                        templateResult: function(data) {
                            if (data.loading) {
                                return data.text;
                            }

                            // Convert line breaks to HTML breaks for display
                            var displayText = data.text;
                            if (displayText && displayText.includes('\n')) {
                                displayText = displayText.replace(/\n/g, '<br>');
                                return $('<div>' + displayText + '</div>');
                            }

                            return $('<div>' + displayText + '</div>');
                        },
                        ajax: {
                            url: '/search-dropdown',
                            dataType: 'json',
                            delay: 250,                            data: function (params) {
                                // Get query data directly from the data attribute
                                // Need to decode HTML entities from the data attribute
                                let queryValue = String($(selectElement).attr('data-query') || '');

                                // Decode HTML entities in the query value
                                try {
                                    // Use textarea trick to properly decode HTML entities
                                    const textarea = document.createElement('textarea');
                                    textarea.innerHTML = queryValue;
                                    queryValue = textarea.value;

                                    // console.log('Decoded query value:', queryValue);
                                } catch (e) {
                                    // console.error('Error decoding query:', e);
                                }

                                // Log raw value for debugging
                                // console.log('Raw query value from data attribute:', queryValue);

                                // Check if query is empty
                                if (!queryValue || queryValue.trim() === '') {
                                    // console.error('QUERY IS EMPTY! Component:', selectElement.id);
                                }

                                // Only include the query parameter, not both query and sqlQuery
                                const data = {
                                    q: params.term || '',
                                    connection: $(selectElement).data('connection'),
                                    query: queryValue, // Only use one parameter name for consistency
                                    option_value: $(selectElement).data('option-value'),
                                    option_label: $(selectElement).data('option-label'),
                                };

                                // ENHANCED DEBUGGING - check if query is properly set
                                const hasQuery = queryValue && queryValue.trim().length > 0;
                                if (!hasQuery) {
                                    // console.error(`ERROR: Empty query for dropdown ${selectElement.id}!`, {
                                    //     'data-query': $(selectElement).attr('data-query'),
                                    //     'data-connection': $(selectElement).attr('data-connection')
                                    // });
                                }

                                // console.debug(`Sending dropdown params for ${selectElement.id}:`, {
                                //     queryValue: queryValue,
                                //     queryValueLength: queryValue ? queryValue.length : 0,
                                //     connection: $(selectElement).data('connection')
                                // });

                                // console.log('Dropdown search params:', data);
                                return data;
                            },
                            processResults: function (data) {
                                if (data && data.results) {
                                    return { results: data.results };
                                } else {
                                    // console.error('Invalid response format for {{ $id }}:', data);
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

                        // Ensure placeholder is visible initially
                        if (!$(selectElement).val() || $(selectElement).val() === '{{ $blankValue }}') {
                            $(selectElement).val(null).trigger('change');
                        }

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
                                        // console.error(`Invalid onChanged format: ${onChanged}`);
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

                    // console.log(`Select2 initialized for #{{ $id }}`, {
                    //     connection: '{{ $dbConnection }}',
                    //     query: document.getElementById('{{ $id }}').getAttribute('data-query'),
                    //     hasQueryParam: {{ $hasQueryParam ? 'true' : 'false' }},
                    // });

                    // Restore existing value if present
                    const existingValue = '{{ $selectedValue ?? '' }}' || '{{ $this->$model ?? '' }}';
                    if (existingValue && existingValue !== '{{ $blankValue }}') {
                            // Fetch display text for existing value using the correct endpoint
                            const endpoint = '/search-dropdown';

                            // Use the same query from the data attribute
                            const queryParam = document.getElementById('{{ $id }}').getAttribute('data-query');

                            // Use URLSearchParams to encode parameters properly
                            const params = new URLSearchParams();
                            params.append('connection', '{{ $dbConnection }}');
                            params.append('query', queryParam);
                            params.append('option_value', '{{ $optionValue }}');
                            params.append('option_label', '{{ $optionLabel }}');
                            params.append('id', existingValue);
                            params.append('preserve_existing', 'true');  // Flag for existing value lookup
                            params.append('bypass_filters', 'true');    // Bypass business logic filters

                            // console.log('Fetching existing value with params:', params.toString());

                            fetch(`${endpoint}?${params.toString()}`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data && data.results && data.results.length > 0) {
                                        const item = data.results[0];

                                        // Build display text with status indicators
                                        let displayText = item.text;
                                        const indicators = [];

                                        if (item.is_deleted) indicators.push('Deleted');
                                        if (item.is_out_of_stock) indicators.push('Out of Stock');
                                        if (item.is_inactive) indicators.push('Inactive');
                                        if (item.is_expired) indicators.push('Expired');
                                        if (item.custom_status) indicators.push(item.custom_status);

                                        if (indicators.length > 0) {
                                            displayText += ` (${indicators.join(', ')})`;
                                        }

                                        const option = new Option(displayText, item.id, true, true);

                                        // Add appropriate CSS classes for styling
                                        if (item.is_deleted) $(option).addClass('deleted-option');
                                        if (item.is_out_of_stock) $(option).addClass('out-of-stock-option');
                                        if (item.is_inactive) $(option).addClass('inactive-option');
                                        if (item.is_expired) $(option).addClass('expired-option');

                                        $(selectElement).append(option).trigger('change');
                                    } else {
                                        // If no result found, create a placeholder option
                                        // console.warn('No display text found for existing value, creating placeholder');
                                        const option = new Option(`ID: ${existingValue} (Not Found)`, existingValue, true, true);
                                        $(option).addClass('missing-option');
                                        $(selectElement).append(option).trigger('change');
                                    }
                                })
                                .catch(error => {
                                    // console.warn('Failed to restore selected value:', error);
                                    // Create a fallback option
                                    const option = new Option(`ID: ${existingValue} (Error Loading)`, existingValue, true, true);
                                    $(option).addClass('error-option');
                                    $(selectElement).append(option).trigger('change');
                                });
                        }

                };

                // Initialize Select2 when component is created
                initSelect2();
            }">

            <select id="{{ $id }}"
                class="{{ $inputClass }} @error($model) is-invalid @enderror
                    @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled-gray @endif"
                wire:model="{{ $model }}"
                data-placeholder="{{ $placeHolder ?? 'Select an option' }}"
                data-connection="{{ $dbConnection }}"
                data-query="{{ htmlspecialchars($query, ENT_QUOTES, 'UTF-8') }}"
                data-option-value="{{ $optionValue }}"
                data-option-label="{{ $optionLabel }}"
                @if (!$isEnabled && ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'))) disabled @endif
                wire:loading.attr="disabled">
                <option value="{{ $blankValue }}" selected></option>
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
Enhanced UI Dropdown Search Component with Raw Query Support

Features:
- Uses app_code from session as default connection
- Support for raw SQL queries
- Real-time AJAX search with Select2
- Multi-label display:
  * Separate multiple fields with comma (,)
  * Example:  optionLabel="{code},{name}" displays "ABC123 - Product Name"

Example usage:
<x-ui-dropdown-search
    label="Search Brand"
    model="selectedBrandId"
    optionValue="str1"
    optionLabel="{str2}"
    query="SELECT str1, str2 FROM config_const WHERE const_group='MMATL_BRAND' AND deleted_at IS NULL"
    connection="Default"
    placeHolder="Type to search brands..."
    selectedValue="BRAND001"
    type="string"
    required="false"
    onChanged="onBrandSelected"
/>
--}}
