@php
    $id = str_replace(['.', '[', ']'], '_', $model);
@endphp

<div class="col-sm mb-5" @if (isset($span)) span="{{ $span }}" @endif
    @if (isset($visible) && $visible === 'false') style="display: none;" @endif>
    <div class="d-flex align-items-center">
        <div class="form-floating flex-grow-1">
            @if (isset($type) && $type === 'textarea')
                <textarea style="min-height: 150px;" wire:model="{{ $model }}" id="{{ $id }}"
                    rows="{{ isset($rows) ? $rows : '10' }}" class="form-control form-control-lg @error($model) is-invalid @enderror"
                    @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif @if (isset($required) && $required === 'true') required @endif
                    placeholder="{{ isset($label) ? $label : '' }}"
                    @if (isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" @endif
                    autocomplete="off"></textarea>
            @elseif(isset($type) && $type === 'document')
                <input wire:model="{{ $model }}" id="{{ $id }}" type="file"
                    class="form-control @error($model) is-invalid @enderror"
                    @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                    @if (isset($required) && $required === 'true') required @endif accept=".pdf, .doc, .docx"
                    @if (isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" @endif />
            @elseif(isset($type) && $type === 'barcode')
                <input x-data="{
                    initBarcode() {
                        let barcodeInput = this.$refs.inputField;
                        if (barcodeInput) {
                            window.addEventListener('barcode-processed', function() {
                                barcodeInput.value = '';
                                barcodeInput.focus();
                            });
                            barcodeInput.addEventListener('keydown', function(event) {
                                if (event.key === 'Enter') {
                                    event.preventDefault();
                                    if (barcodeInput.value !== '') {
                                        Livewire.dispatch('scanBarcode', barcodeInput.value);
                                    }
                                }
                            });
                        }
                    }
                }" x-init="initBarcode()" wire:model="{{ $model }}"
                    id="{{ $id }}" type="text" class="form-control @error($model) is-invalid @enderror"
                    @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                    @if (isset($required) && $required === 'true') required @endif placeholder="{{ isset($label) ? $label : '' }}"
                    autocomplete="off"
                    @if (isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" @endif
                    x-ref="inputField">
            @elseif(isset($type) && $type === 'code')
                <input wire:model="{{ $model }}" type="text"
                    class="form-control @error($model) is-invalid @enderror"
                    @if ((isset($action) && ($action === 'Edit' || $action === 'View')) || (isset($enabled) && $enabled === 'false')) disabled @endif
                    @if (isset($required) && $required === 'true') required @endif placeholder="{{ isset($label) ? $label : '' }}"
                    autocomplete="off"
                    @if (isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" @endif />
            @elseif(isset($type) && $type === 'date')
                <input x-data="{
                    initDatepicker() {
                        let input = this.$refs.inputField;
                        if (input) {
                            myJQuery(input).datepicker({
                                dateFormat: 'dd-mm-yy',
                                changeMonth: true,
                                changeYear: true,
                                showButtonPanel: true
                            }).on('change', function() {
                                $wire.set('{{ $model }}', myJQuery(this).val());
                                @if (isset($onChanged) && $onChanged !== '') Livewire.dispatch('{{ $onChanged }}'); @endif
                            });
                        }
                    }
                }" x-init="initDatepicker()" wire:model="{{ $model }}"
                    id="{{ $id }}" type="text" class="form-control @error($model) is-invalid @enderror"
                    @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                    @if (isset($required) && $required === 'true') required @endif readonly="readonly" x-ref="inputField"
                    @if (isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" @endif />
            @elseif(isset($type) && $type === 'number')
                <input x-data="{
                    initInputMask() {
                        let input = this.$refs.inputField;
                        if (input) {
                            Inputmask({
                                alias: 'numeric',
                                groupSeparator: '.', // Pemisah ribuan
                                radixPoint: ',', // Pemisah desimal
                                autoGroup: true,
                                digits: 2, // Jumlah desimal
                                rightAlign: false,
                                clearIncomplete: true,
                                allowMinus: false,
                                placeholder: '0'
                            }).mask(input);

                            // Sinkronkan nilai dengan Livewire setelah format
                            input.addEventListener('blur', () => {
                                if (input.value.trim() === '') {
                                    input.value = '0';
                                }
                                $wire.set('{{ $model }}', input.inputmask.unmaskedvalue().replace(',', '.'));
                            });
                        }
                    }
                }" x-init="initInputMask()" wire:model="{{ $model }}"
                    id="{{ $id }}" type="text"
                    class="form-control number-mask @error($model)
is-invalid
@enderror" @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false'))
                disabled
            @endif
            @if (isset($required) && $required === 'true')
                required
            @endif
            placeholder="{{ isset($label) ? $label : '' }}"
            autocomplete="off"
            @if (isset($onChanged) && $onChanged !== '')
                wire:change="{{ $onChanged }}"
            @endif
            x-ref="inputField">
        @elseif(isset($type) && $type === 'image')
            <input wire:model="{{ $model }}" id="{{ $id }}" type="file"
                class="form-control @error($model) is-invalid @enderror" accept="image/*"
                @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif @if (isset($required) && $required === 'true') required @endif
                @if (isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" @endif />
        @else
            <input x-data="{
                applyCapsLock() {
                    if ({{ isset($capslockMode) && $capslockMode === 'true' ? 'true' : 'false' }}) {
                        let input = this.$refs.inputField;
                        if (input) {
                            input.addEventListener('input', function() {
                                input.value = input.value.toUpperCase();
                                $wire.set('{{ $model }}', input.value);
                            });
                        }
                    }
                }
            }" x-init="applyCapsLock()" wire:model="{{ $model }}"
                type="{{ isset($type) ? $type : 'text' }}"
                class="form-control @if (isset($capslockMode) && $capslockMode === 'true') text-uppercase @endif @error($model) is-invalid @enderror"
                @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif @if (isset($required) && $required === 'true') required @endif
                placeholder="{{ isset($label) ? $label : '' }}" autocomplete="off"
                @if (isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" @endif
                x-ref="inputField" />
            @endif


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

        <!-- Refresh Button -->
        @if (isset($clickEvent) && $clickEvent !== '')
            <div class="d-flex align-items-center ms-2">
                <span wire:loading.remove wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}">
                    <x-ui-button :clickEvent="$clickEvent" cssClass="btn btn-secondary" :buttonName="$buttonName" :action="$action"
                        :enabled="$enabled" />
                </span>
                <span wire:loading wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}">
                    <span class="spinner-border spinner-border-sm align-middle" role="status"
                        aria-hidden="true"></span>
                </span>
            </div>
        @endif
    </div>
</div>
