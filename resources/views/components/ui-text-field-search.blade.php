    @php
        $id = str_replace(['.', '[', ']'], '_', $model);
        $blankValue = isset($type) && $type === 'int' ? '0' : '';
    @endphp

    <div wire:ignore.self class="col-sm mb-5" @if (isset($span)) span="{{ $span }}" @endif
        @if (isset($visible) && $visible === 'false') style="display: none;" @endif>

        <div class="d-flex align-items-center">
            <div class="form-floating  flex-grow-1" x-data="{
                initSelect2() {
                    let selectId = '{{ $id }}';
                    let selectElement = document.getElementById(selectId);

                    if (selectElement) {
                        // Initialize select2
                        $(selectElement).select2();

                        // Sync value with Livewire on change
                        $(selectElement).on('change', function() {
                            const value = $(this).val();
                            @this.set('{{ $model }}', value);
                            let onChanged = '{{ isset($onChanged) ? $onChanged : '' }}';

                            console.log(`Value changed for ${selectId}:`, value);

                            if (!onChanged) {
                                console.warn('onChanged is not defined or empty.');
                                return;
                            }

                            // Replace $event.target.value with the actual value
                            if (onChanged.includes('$event.target.value')) {
                                onChanged = onChanged.replace('$event.target.value', value);
                            }

                            // Check if onChanged contains parentheses
                            if (onChanged.includes('(')) {
                                // Extract method name and parameters
                                const matches = onChanged.match(/^([\w.]+)\((.*)\)$/);
                                if (matches) {
                                    const methodName = matches[1];
                                    const params = matches[2]
                                        .split(',')
                                        .map(param => param.trim())
                                        .filter(param => param !== ''); // Ensure no empty parameters

                                    console.log(`Calling Livewire method: ${methodName} with params:`, params);
                                    $wire.call(methodName, ...params); // Pass the dynamic parameters
                                } else {
                                    console.error(`Invalid onChanged format: ${onChanged}`);
                                }
                            } else {
                                // Call method without parameters
                                console.log(`Calling Livewire method: ${onChanged} with value: ${value}`);
                                $wire.call(onChanged, value);
                            }
                        });

                        console.log(`select2 initialized for ${selectId}`);
                    } else {
                        console.warn(`Element with ID ${selectId} not found.`);
                    }
                }
            }" x-init="initSelect2();
            Livewire.hook('morph.updated', () => { initSelect2(); });">

                <select id="{{ $id }}"
                    class="form-select responsive-input @error($model) is-invalid @enderror
                    @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled-gray @endif"
                    wire:model="{{ $model }}" @if (
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
                @if (!empty($placeHolder))
                    <div class="placeholder-text">{{ $placeHolder }}</div>
                @endif
                @error($model)
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>

            @if (isset($clickEvent) && $clickEvent !== '')
                <div class="d-flex align-items-center ms-2">
                    <!-- Button when not loading -->
                    <span wire:loading.remove wire:target="{{ $clickEvent }}">
                        <x-ui-button :clickEvent="$clickEvent" cssClass="btn btn-secondary" :buttonName="$buttonName ?? 'Search'" :action="$action ?? ''"
                            :enabled="$enabled ?? true" />
                    </span>
                    <!-- Loading Spinner -->
                    <span wire:loading wire:target="{{ $clickEvent }}">
                        <span class="spinner-border spinner-border-sm align-middle" role="status"
                            aria-hidden="true"></span>
                    </span>
                </div>
                @endif
        </div>
    </div>
