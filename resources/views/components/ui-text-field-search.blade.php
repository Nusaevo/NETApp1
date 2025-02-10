@php
    $id = str_replace(['.', '[', ']'], '_', $model);
    $blankValue = isset($type) && $type === 'int' ? '0' : '';
    $colClass = 'col-sm' . (!empty($label) ? ' mb-5' : '');
    $containerClass = !empty($label) ? 'form-floating flex-grow-1' : 'flex-grow-1';
@endphp

<div wire:ignore.self class="{{ $colClass }}" @if (isset($span)) span="{{ $span }}" @endif
    @if (isset($visible) && $visible === 'false') style="display: none;" @endif>
    
    <div class="input-group">
        <div class="{{ $containerClass }}" x-data="{
                initSelect2() {
                    let selectId = '{{ $id }}';
                    let selectElement = document.getElementById(selectId);

                    if (selectElement) {
                        $(selectElement).select2();
                        $(selectElement).on('change', function() {
                            const value = $(this).val();
                            @this.set('{{ $model }}', value);
                            let onChanged = '{{ isset($onChanged) ? $onChanged : '' }}';

                            console.log(`Value changed for ${selectId}:`, value);

                            if (!onChanged) {
                                console.warn('onChanged is not defined or empty.');
                                return;
                            }

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

                        console.log(`select2 initialized for ${selectId}`);
                    } else {
                        console.warn(`Element with ID ${selectId} not found.`);
                    }
                }
            }"
            x-init="initSelect2();
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
        </div> <!-- Penutup div containerClass -->

        @if (isset($clickEvent) && $clickEvent !== '')
            <x-ui-button type="InputButton" :clickEvent="$clickEvent" cssClass="btn btn-secondary"
                :buttonName="$buttonName" :action="$action" :enabled="$buttonEnabled" loading="true" />
        @endif
    </div>
</div>
