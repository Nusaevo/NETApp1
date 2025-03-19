@php
    $id = str_replace(['.', '[', ']'], '_', $model);
    $blankValue = isset($type) && $type === 'int' ? '0' : '';
    $colClass = 'col-sm' . (!empty($label) ? ' mb-5' : '');
    $containerClass = !empty($label) ? 'form-floating flex-grow-1' : 'flex-grow-1';
@endphp

<div wire:ignore class="{{ $colClass }}" @if (isset($span)) span="{{ $span }}" @endif
    @if (isset($visible) && $visible === 'false') style="display: none;" @endif>

    <div class="input-group">
        <div class="{{ $containerClass }}" x-data x-init="() => {
                const initSelect2 = () => {
                    const selectElement = document.getElementById('{{ $id }}');
                    if (!selectElement) {
                        console.warn(`Element #{{ $id }} not found.`);
                        return;
                    }

                    // Jika instance Select2 sudah ada, hancurkan terlebih dahulu
                    if ($(selectElement).hasClass('select2-hidden-accessible')) {
                        $(selectElement).select2('destroy');
                    }

                    // Inisialisasi Select2
                    $(selectElement).select2();

                    // Pastikan tidak terjadi duplikasi event binding
                    $(selectElement).off('change');

                    // Tangani event change pada elemen tersebut
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

                        // Reinisialisasi Select2 hanya untuk elemen ini setelah terjadi perubahan
                        initSelect2();
                    });

                    console.log(`Select2 initialized for #{{ $id }}`);
                };

                // Inisialisasi Select2 saat komponen dibuat
                initSelect2();
            }">

            <select id="{{ $id }}"
                class="form-select responsive-input @error($model) is-invalid @enderror
                @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled-gray @endif"
                wire:model="{{ $model }}"
                @if (!(isset($enabled) && ($enabled === 'always' || $enabled === 'true')) &&
                    ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')))
                    disabled
                @endif
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
        </div>

        @if (isset($clickEvent) && $clickEvent !== '')
            <x-ui-button type="InputButton" :clickEvent="$clickEvent" cssClass="btn btn-secondary"
                :buttonName="$buttonName" :action="$action" :enabled="$buttonEnabled" loading="true" />
        @endif
    </div>
</div>
