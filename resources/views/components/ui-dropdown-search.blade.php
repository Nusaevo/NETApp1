@php
    $id = str_replace(['.', '[', ']'], '_', $model);
    $blankValue = ($type === 'int') ? '0' : '';
@endphp

<div wire:ignore.self class="col-sm mb-5"
    @if (isset($span)) span="{{ $span }}" @endif
    @if (isset($visible) && $visible === 'false') style="display: none;" @endif
>
    <div class="d-flex align-items-center">
        <div class="form-floating flex-grow-1" x-data="{
            initSelect2() {
                let selectId = '{{ $id }}';
                let selectElement = document.getElementById(selectId);

                if (selectElement) {
                    $(selectElement).select2({
                        placeholder: '{{ $placeHolder ?? 'Select an option' }}',
                        ajax: {
                            // URL statis (misal: /partner-search)
                            url: '/search-dropdown',
                            dataType: 'json',
                            delay: 250,
                            data: function (params) {
                                return {
                                    q: params.term || '',
                                    page: params.page || 1,

                                    // Model dan where condition (opsional)
                                    model: $(selectElement).data('search-model'),
                                    where: $(selectElement).data('search-where'),

                                    // Field yang dijadikan value dan label
                                    option_value: $(selectElement).data('option-value'),
                                    option_label: $(selectElement).data('option-label'),
                                };
                            },
                            processResults: function (data) {
                                return {
                                    results: data.results,
                                    pagination: {
                                        more: data.pagination?.more || false
                                    }
                                };
                            },
                            cache: true
                        }
                    }).on('select2:select', function (e) {
                        const value = $(this).val();
                        @this.set('{{ $model }}', value);

                        // Panggil method onChanged kalau ada
                        let onChanged = '{{ $onChanged ?? '' }}';
                        if (onChanged) {
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
                        }
                    });
                }
            }
        }"
        x-init="initSelect2();
        Livewire.hook('morph.updated', () => {
            initSelect2();
        });">

            <select id="{{ $id }}"
                class="form-select responsive-input global-search @error($model) is-invalid @enderror
                    @if ((!empty($action) && $action === 'View') ||
                         (isset($enabled) && $enabled === 'false')) disabled-gray @endif"
                wire:model="{{ $model }}"
                data-placeholder="{{ $placeHolder }}"
                data-search-model="{{ $searchModel }}"
                data-search-where="{{ $searchWhereCondition }}"
                data-option-value="{{ $optionValue }}"
                data-option-label="{{ $optionLabel }}"
                @if (
                    !(isset($enabled) && ($enabled === 'always' || $enabled === 'true')) &&
                    ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'))
                ) disabled @endif
                wire:loading.attr="disabled"
            >
                <option value="{{ $blankValue }}"></option>
            </select>

            @if (!empty($label))
                <label for="{{ $id }}"
                    class="@if (isset($required) && $required === 'true') required @endif"
                >
                    {{ $label }}
                </label>
            @endif

            @error($model)
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        @if (!empty($clickEvent))
            <div class="d-flex align-items-center ms-2">
                <span wire:loading.remove wire:target="{{ $clickEvent }}">
                    <x-ui-button :clickEvent="$clickEvent"
                        cssClass="btn btn-secondary"
                        :buttonName="$buttonName ?? 'Search'"
                        :action="$action ?? ''"
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
{{--
<x-ui-dropdown-search
label="Partner"
model="selectedPartnerId"
optionValue="id"
optionLabel="name"
searchModel="App\Models\TrdTire1\Master\Partner"
searchWhereCondition="status=A"
placeHolder="Pilih Partner Aktif"
/> --}}
