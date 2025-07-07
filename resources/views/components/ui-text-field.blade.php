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
                <textarea style="{{ isset($height) && $height !== '' ? 'height: ' . $height . ';' : 'min-height: 80px;' }}"
                    wire:model.lazy="{{ $model }}" id="{{ $id }}" rows="{{ isset($rows) ? $rows : '10' }}"
                    class="form-control form-control-lg @error($model) is-invalid @enderror"
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
                <input wire:model="{{ $model }}" id="{{ $id }}" type="text"
                    class="form-control @error($model) is-invalid @enderror"
                    @if ((isset($action) && $action == 'View') || (!empty($enabled) && $enabled === 'false')) disabled @endif
                    @if (isset($required) && $required === 'true') required @endif
                    placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="off"
                    wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />

                <script>
                    document.addEventListener('livewire:load', function() {
                        var barcodeInput = document.getElementById('{{ $model }}');
                        if (barcodeInput) {
                            window.addEventListener('barcode-processed', function() {
                                barcodeInput.value = '';
                                barcodeInput.focus();
                            });
                        }
                    });
                </script>
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
                                    setTimeout(function() {
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
            @elseif(isset($type) && $type === 'datetime')
                <input x-data="{
                    initDatetimepicker() {
                        let input = this.$refs.inputField;
                        if (input) {
                            myJQuery(input).datetimepicker({
                                dateFormat: 'yy-mm-dd',
                                timeFormat: 'HH:mm:ss',
                                changeMonth: true,
                                changeYear: true,
                                showButtonPanel: true,
                                controlType: 'select',
                                oneLine: true,
                                beforeShow: function(input, inst) {
                                    setTimeout(function() {
                                        $('.ui-datepicker').css('z-index', 99999999999999);
                                    }, 0);
                                }
                            }).on('change', function() {
                                $wire.set('{{ $model }}', myJQuery(this).val());
                                @if (isset($onChanged) && $onChanged !== '') Livewire.dispatch('{{ $onChanged }}'); @endif
                            });
                        }
                    }
                }" x-init="initDatetimepicker()" wire:model.lazy="{{ $model }}"
                    id="{{ $id }}" type="text" class="form-control @error($model) is-invalid @enderror"
                    @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                    @if (isset($required) && $required === 'true') required @endif readonly="readonly" x-ref="inputField"
                    @if (isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" @endif />
            @elseif(isset($type) && $type === 'number')
                <input x-data="{
                    originalValue: @entangle($model),
                    displayValue: '',
                    currency: '{{ $currency ?? '' }}',

                    formatForDisplay() {
                        if (!this.originalValue || this.originalValue === '' || this.originalValue === null || this.originalValue === undefined) {
                            this.displayValue = '';
                            return;
                        }
                        let num = parseFloat(this.originalValue) || 0;
                        if (num === 0) {
                            this.displayValue = '';
                            return;
                        }

                        // Hitung jumlah desimal dari nilai asli
                        let decimalPlaces = 0;
                        let numStr = num.toString();
                        if (numStr.indexOf('.') !== -1) {
                            decimalPlaces = numStr.split('.')[1].length;
                        }

                        let formattedNumber = '';

                        // Format berdasarkan currency
                        if (this.currency === 'IDR') {
                            formattedNumber = 'Rp ' + num.toLocaleString('id-ID', {
                                minimumFractionDigits: decimalPlaces,
                                maximumFractionDigits: decimalPlaces
                            }).replace(/\./g, '.').replace(/,/g, ',');
                        } else if (this.currency === 'USD') {
                            formattedNumber = '$' + num.toLocaleString('en-US', {
                                minimumFractionDigits: decimalPlaces,
                                maximumFractionDigits: decimalPlaces
                            });
                        } else if (this.currency === 'EUR') {
                            formattedNumber = '€' + num.toLocaleString('de-DE', {
                                minimumFractionDigits: decimalPlaces,
                                maximumFractionDigits: decimalPlaces
                            });
                        } else if (this.currency === 'GBP') {
                            formattedNumber = '£' + num.toLocaleString('en-GB', {
                                minimumFractionDigits: decimalPlaces,
                                maximumFractionDigits: decimalPlaces
                            });
                        } else if (this.currency === 'JPY') {
                            formattedNumber = '¥' + num.toLocaleString('ja-JP', {
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            });
                        } else {
                            // Default format tanpa currency
                            formattedNumber = num.toLocaleString('id-ID', {
                                minimumFractionDigits: decimalPlaces,
                                maximumFractionDigits: decimalPlaces
                            }).replace(/\./g, '.').replace(/,/g, ',');
                        }

                        this.displayValue = formattedNumber;
                    },

                    onFocus() {
                        // Saat focus, tampilkan nilai asli untuk editing
                        if (this.originalValue === null || this.originalValue === undefined || this.originalValue === '') {
                            this.$refs.inputField.value = '';
                        } else {
                            this.$refs.inputField.value = this.originalValue;
                        }
                    },

                    onBlur() {
                        // Saat blur, format kembali untuk display dan update model
                        let value = this.$refs.inputField.value;
                        if (value === '' || value === null || value === undefined) {
                            this.originalValue = null;
                            this.displayValue = '';
                        } else {
                            // Parse nilai yang diinput
                            let cleanValue = value.replace(/[^0-9.,\-]/g, '');
                            cleanValue = cleanValue.replace(/,/g, '.');
                            let numValue = parseFloat(cleanValue);

                            if (isNaN(numValue) || numValue === 0) {
                                this.originalValue = null;
                                this.displayValue = '';
                            } else {
                                // Update model dengan nilai asli
                                this.originalValue = numValue;

                                // Format untuk display
                                this.formatForDisplay();
                            }
                        }

                        // Set display value ke input
                        this.$refs.inputField.value = this.displayValue;
                    },

                    onKeydown(event) {
                        // Allow: backspace, delete, tab, escape, enter
                        if ([8, 9, 27, 13, 46].indexOf(event.keyCode) !== -1 ||
                            // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                            (event.keyCode === 65 && event.ctrlKey === true) ||
                            (event.keyCode === 67 && event.ctrlKey === true) ||
                            (event.keyCode === 86 && event.ctrlKey === true) ||
                            (event.keyCode === 88 && event.ctrlKey === true) ||
                            // Allow: home, end, left, right
                            (event.keyCode >= 35 && event.keyCode <= 39)) {
                            return;
                        }

                        // Allow: numbers, minus, comma, period
                        if (!((event.keyCode >= 48 && event.keyCode <= 57) ||
                            (event.keyCode >= 96 && event.keyCode <= 105) ||
                            event.keyCode === 188 || event.keyCode === 190 ||
                            event.keyCode === 109 || event.keyCode === 189)) {
                            event.preventDefault();
                        }
                    }
                }"
                x-init="formatForDisplay(); $watch('originalValue', () => formatForDisplay())"
                type="text"
                id="{{ $id }}"
                class="form-control @error($model) is-invalid @enderror"
                @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                @if (isset($required) && $required === 'true') required @endif
                placeholder="{{ isset($label) ? $label : '' }}"
                autocomplete="off"
                @if (isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" @endif
                x-ref="inputField"
                x-on:focus="onFocus()"
                x-on:blur="onBlur()"
                x-on:keydown="onKeydown($event)"
                x-bind:value="displayValue" />
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
