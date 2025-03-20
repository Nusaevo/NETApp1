@php
    $id = str_replace(['.', '[', ']'], '_', $model);
    $blankValue = isset($type) && $type === 'int' ? '0' : '';

    $colClass = 'col-sm' . (!empty($label) ? ' mb-5' : '');
    $containerClass = !empty($label) ? 'form-floating flex-grow-1' : 'flex-grow-1';
@endphp

<div wire:ignore.self class="{{ $colClass }}" @if (isset($span)) span="{{ $span }}" @endif
    @if (isset($visible) && $visible === 'false') style="display: none;" @endif>
    <div class="input-group">
        <div class="{{ $containerClass }}">
            @if (isset($type) && $type === 'textarea')
                <textarea
                    style="{{ isset($height) && $height !== '' ? 'height: ' . $height . ';' : 'min-height: 80px;' }}"
                     wire:model.lazy="{{ $model }}" id="{{ $id }}"
                    rows="{{ isset($rows) ? $rows : '10' }}" class="form-control form-control-lg @error($model) is-invalid @enderror"
                    @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif @if (isset($required) && $required === 'true') required @endif
                    placeholder="{{ isset($label) ? $label : '' }}"
                    @if (isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" @endif autocomplete="off"
                    x-data="{
                        applyCapsLock() {
                            if ({{ isset($capslockMode) && $capslockMode === 'true' ? 'true' : 'false' }}) {
                                let input = this.$refs.inputField;
                                if (input) {
                                    // Saat kehilangan fokus (blur)
                                    input.addEventListener('blur', function() {
                                        input.value = input.value.toUpperCase();
                                        $wire.set('{{ $model }}', input.value);
                                    });

                                    // Saat tekan Enter
                                    input.addEventListener('keydown', function(event) {
                                        if (event.key === 'Enter') {
                                            event.preventDefault();
                                            input.value = input.value.toUpperCase();
                                            $wire.set('{{ $model }}', input.value);
                                        }
                                    });
                                }
                            }
                        }
                    }" x-init="applyCapsLock()" x-ref="inputField"></textarea>
            @elseif(isset($type) && $type === 'code')
                <input wire:model.lazy="{{ $model }}" type="text"
                    class="form-control @error($model) is-invalid @enderror"
                    @if ((isset($action) && ($action === 'Edit' || $action === 'View')) || (isset($enabled) && $enabled === 'false')) disabled @endif
                    @if (isset($required) && $required === 'true') required @endif placeholder="{{ isset($label) ? $label : '' }}"
                    autocomplete="off" @if (isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" @endif
                    x-data="{
                        applyCapsLock() {
                            if ({{ isset($capslockMode) && $capslockMode === 'true' ? 'true' : 'false' }}) {
                                let input = this.$refs.inputField;
                                if (input) {
                                    // Saat kehilangan fokus (blur)
                                    input.addEventListener('blur', function() {
                                        input.value = input.value.toUpperCase();
                                        $wire.set('{{ $model }}', input.value);
                                    });

                                    // Saat tekan Enter
                                    input.addEventListener('keydown', function(event) {
                                        if (event.key === 'Enter') {
                                            event.preventDefault();
                                            input.value = input.value.toUpperCase();
                                            $wire.set('{{ $model }}', input.value);
                                        }
                                    });
                                }
                            }
                        }
                    }" x-init="applyCapsLock()" x-ref="inputField" />
            @elseif(isset($type) && $type === 'document')
                <input wire:model.lazy="{{ $model }}" id="{{ $id }}" type="file"
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
                }" x-init="initBarcode()" wire:model.lazy="{{ $model }}"
                    id="{{ $id }}" type="text" class="form-control @error($model) is-invalid @enderror"
                    @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                    @if (isset($required) && $required === 'true') required @endif placeholder="{{ isset($label) ? $label : '' }}"
                    autocomplete="off" @if (isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" @endif
                    x-ref="inputField">
            @elseif(isset($type) && $type === 'date')
                <input x-data="{
                    initDatepicker() {
                        let input = this.$refs.inputField;
                        if (input) {
                            myJQuery(input).datepicker({
                                dateFormat: 'yy-mm-dd',
                                changeMonth: true,
                                changeYear: true,
                                showButtonPanel: true,
                                beforeShow: function(input, inst) {
                                    setTimeout(function(){
                                        $('.ui-datepicker').css('z-index', 99999999999999);
                                    }, 0);
                                }
                            }).on('change', function() {
                                $wire.set('{{ $model }}', myJQuery(this).val());
                                @if (isset($onChanged) && $onChanged !== '') Livewire.dispatch('{{ $onChanged }}'); @endif
                            });
                        }
                    }
                }" x-init="initDatepicker()" wire:model.lazy="{{ $model }}"
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
                }" x-init="initInputMask()" wire:model.lazy="{{ $model }}"
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
            <input wire:model.lazy="{{ $model }}" id="{{ $id }}" type="file"
                class="form-control @error($model) is-invalid @enderror" accept="image/*"
                @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif @if (isset($required) && $required === 'true') required @endif
                @if (isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" @endif />
        @else
            <input x-data="{
                applyCapsLock() {
                    if ({{ isset($capslockMode) && $capslockMode === 'true' ? 'true' : 'false' }}) {
                        let input = this.$refs.inputField;
                        if (input) {
                            // Saat kehilangan fokus (blur)
                            input.addEventListener('blur', function() {
                                input.value = input.value.toUpperCase();
                                $wire.set('{{ $model }}', input.value);
                            });

                            // Saat tekan Enter
                            input.addEventListener('keydown', function(event) {
                                if (event.key === 'Enter') {
                                    event.preventDefault();
                                    input.value = input.value.toUpperCase();
                                    $wire.set('{{ $model }}', input.value);
                                }
                            });
                        }
                    }
                }
            }" x-init="applyCapsLock()" wire:model.lazy="{{ $model }}"
                type="{{ isset($type) ? $type : 'text' }}"
                class="form-control @if (isset($capslockMode) && $capslockMode === 'true') text-uppercase @endif @error($model) is-invalid @enderror"
                @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif @if (isset($required) && $required === 'true') required @endif
                placeholder="{{ isset($label) ? $label : '' }}" autocomplete="off"
                @if (isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" @endif x-ref="inputField" />
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
            <x-ui-button type="InputButton" :clickEvent="$clickEvent" cssClass="btn btn-secondary" :buttonName="$buttonName"
                :action="$action" :enabled="$buttonEnabled" loading="true" />
        @endif
    </div>
</div>
