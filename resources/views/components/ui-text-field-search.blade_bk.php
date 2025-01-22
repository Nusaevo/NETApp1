@php
    $id = str_replace(['.', '[', ']'], '_', $model);
    $blankValue = isset($type) && $type === 'int' ? '0' : '';
@endphp

<div wire:ignore.self class="col-sm mb-5"
    @if (isset($span)) span="{{ $span }}" @endif
    @if (isset($visible) && $visible === 'false') style="display: none;" @endif>

    <div class="d-flex align-items-center">
        <div class="form-floating flex-grow-1" x-data="{
            initSelect2() {
                let selectId = '{{ $id }}';
                let selectElement = document.getElementById(selectId);

                console.log('Initializing Select2 for:', selectId);

                if (selectElement) {
                    // Initialize Select2
                    console.log('Found select element:', selectElement);
                    $(selectElement).select2({
                        placeholder: '{{ $placeHolder ?? 'Select an option' }}',
                        ajax: {
                            url: '{{ $searchUrl ?? '' }}',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                console.log('Search params sent to server:', params);
                                return {
                                    q: params.term, // The search term
                                };
                            },
                            processResults: function (data) {
                                console.log('Search results received from server:', data);
                                return {
                                    results: data.results,
                                };
                            },
                            cache: true,
                            error: function (jqXHR, textStatus, errorThrown) {
                                console.error(`Error occurred while fetching results: ${textStatus}`, errorThrown);
                            }
                        }
                    }).on('select2:open', function () {
                        console.log(`Select2 dropdown opened for ${selectId}`);
                    }).on('select2:close', function () {
                        console.log(`Select2 dropdown closed for ${selectId}`);
                    }).on('select2:selecting', function (e) {
                        console.log(`Item selecting on ${selectId}:`, e.params.args.data);
                    }).on('select2:select', function (e) {
                        console.log(`Item selected on ${selectId}:`, e.params.data);
                    });

                    // Sync value with Livewire on change
                    $(selectElement).on('change', function () {
                        const value = $(this).val();
                        console.log(`Value changed for ${selectId}:`, value);

                        @this.set('{{ $model }}', value);
                        let onChanged = '{{ isset($onChanged) ? $onChanged : '' }}';

                        if (!onChanged) {
                            console.warn('onChanged is not defined or empty.');
                            return;
                        }

                        console.log('onChanged handler:', onChanged);

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
                                console.log(`Calling Livewire method: ${methodName} with params:`, params);
                                $wire.call(methodName, ...params);
                            } else {
                                console.error(`Invalid onChanged format: ${onChanged}`);
                            }
                        } else {
                            console.log(`Calling Livewire method: ${onChanged} with value: ${value}`);
                            $wire.call(onChanged, value);
                        }
                    });

                    console.log(`Select2 successfully initialized for ${selectId}`);
                } else {
                    console.warn(`Element with ID ${selectId} not found.`);
                }
            }
        }" x-init="initSelect2();
        Livewire.hook('morph.updated', () => {
            console.log('Livewire DOM updated. Reinitializing Select2...');
            initSelect2();
        });">

            <select id="{{ $id }}"
                class="form-select responsive-input global-search @error($model) is-invalid @enderror
                @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled-gray @endif"
                wire:model="{{ $model }}"
                data-placeholder="{{ $placeHolder ?? 'Select an option' }}"
                data-url="{{ $searchUrl ?? '' }}"
                @if (
                    !(isset($enabled) && ($enabled === 'always' || $enabled === 'true')) &&
                        ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'))) disabled @endif
                wire:loading.attr="disabled">
                <option value="{{ $blankValue }}"></option>
                @if (!is_null($options))
                    @foreach ($options as $option)
                        <option value="{{ $option['value'] }}"
                            {{ $selectedValue == $option['value'] ? 'selected' : '' }}>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                @endif
            </select>

            @if (!empty($label))
                <label for="{{ $id }}"
                    class="@if (isset($required) && $required === 'true') required @endif">{{ $label }}</label>
            @endif
            @error($model)
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        @if (isset($clickEvent) && $clickEvent !== '')
            <div class="d-flex align-items-center ms-2">
                <span wire:loading.remove wire:target="{{ $clickEvent }}">
                    <x-ui-button :clickEvent="$clickEvent" cssClass="btn btn-secondary"
                        :buttonName="$buttonName ?? 'Search'" :action="$action ?? ''"
                        :enabled="$enabled ?? true" />
                </span>
                <span wire:loading wire:target="{{ $clickEvent }}">
                    <span class="spinner-border spinner-border-sm align-middle" role="status"
                        aria-hidden="true"></span>
                </span>
            </div>
        @endif
    </div>
</div>

<x-ui-text-field-search
model="inputs.partner_id"            {{-- Wire model for Livewire --}}
:options="$partners"                   {{-- Optional initial options --}}
searchUrl="/search-partner"
placeHolder="Search Partner"       {{-- Placeholder for the dropdown --}}
label="Partner"                    {{-- Label for the dropdown --}}
span="Full"                        {{-- Optional layout control --}}
required="true"                    {{-- Mark the field as required --}}
onChanged="partnerChanged"         {{-- Livewire method to call on change --}}
action="Edit"                      {{-- Optional action context --}}
selectedValue="1"                  {{-- Pre-select an option if needed --}}
/>
