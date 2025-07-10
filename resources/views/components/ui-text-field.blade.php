@php
    $id = str_replace(['.', '[', ']'], '_', $model);
@endphp

<div class="col-sm mb-5" @if(isset($span)) span="{{ $span }}" @endif @if(isset($visible) && $visible === 'false') style="display: none;" @endif>
    <div class="d-flex align-items-center">
        <div class="form-floating flex-grow-1">
            @if(isset($type) && $type === 'textarea')
                <textarea style="min-height: 80px;" wire:model="{{ $model }}" id="{{ $id }}" rows="{{ isset($rows) ? $rows : '10' }}" class="form-control form-control-lg @error($model) is-invalid @enderror"
                          @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                          @if(isset($required) && $required === 'true') required @endif
                          placeholder="{{ isset($label) ? $label : '' }}"
                          @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif autocomplete="off"></textarea>
            @elseif(isset($type) && $type === 'document')
                <input wire:model="{{ $model }}" id="{{ $id }}" type="file" class="form-control @error($model) is-invalid @enderror"
                       @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                       @if(isset($required) && $required === 'true') required @endif accept=".pdf, .doc, .docx"
                       @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif />
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
                    }" x-init="initBarcode()" wire:model="{{ $model }}" id="{{ $id }}" type="text" class="form-control @error($model) is-invalid @enderror"
                       @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                       @if(isset($required) && $required === 'true') required @endif
                       placeholder="{{ isset($label) ? $label : '' }}" autocomplete="off"
                       @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif x-ref="inputField">
            @elseif(isset($type) && $type === 'code')
                <input wire:model="{{ $model }}" type="text" class="form-control @error($model) is-invalid @enderror"
                       @if ((isset($action) && ($action === 'Edit' || $action === 'View')) || (isset($enabled) && $enabled === 'false')) disabled @endif
                       @if(isset($required) && $required === 'true') required @endif
                       placeholder="{{ isset($label) ? $label : '' }}" autocomplete="off"
                       @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif />
            @elseif(isset($type) && $type === 'date')
                <input wire:model="{{ $model }}" id="{{ $id }}" type="date" class="form-control @error($model) is-invalid @enderror"
                       @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                       @if(isset($required) && $required === 'true') required @endif
                       @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" @endif />
            @elseif(isset($type) && $type === 'number')
                @if(isset($currency) && $currency !== '')
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <div class="input-group-text">
                                @switch($currency)
                                    @case('IDR')
                                        IDR
                                        @break
                                    @case('USD')
                                        USD
                                        @break
                                    @default
                                        {{ $currency }}
                                @endswitch
                            </div>
                        </div>
                        <input x-data="{
                                rawValue: @entangle($model),
                                displayValue: '',
                                decimalPlaces: {{ isset($decimalPlaces) && $decimalPlaces !== null ? $decimalPlaces : 'null' }},

                                formatNumber(num, showDecimals = false, applyRounding = false) {
                                    if (!num || num === '' || num === null || num === undefined) {
                                        return '';
                                    }
                                    // Convert to number and format with Indonesian style (dots for thousands, comma for decimals)
                                    let number = parseFloat(num);
                                    if (isNaN(number)) return '';

                                    // Apply decimal rounding only when explicitly requested (usually onBlur)
                                    if (applyRounding && this.decimalPlaces !== null) {
                                        number = parseFloat(number.toFixed(this.decimalPlaces));
                                    }

                                    // Format using Indonesian locale style
                                    let parts = number.toString().split('.');
                                    let integerPart = parseInt(parts[0]).toLocaleString('de-DE'); // Use German locale for dot separation
                                    let decimalPart = parts[1] || '';

                                    // Only show decimals if explicitly requested (user typed comma) or if the number originally had decimals
                                    if (showDecimals && this.decimalPlaces !== null && this.decimalPlaces > 0) {
                                        if (decimalPart === '') {
                                            decimalPart = '0'.repeat(this.decimalPlaces);
                                        } else if (applyRounding) {
                                            // Only pad to exact decimal places when rounding (onBlur)
                                            decimalPart = decimalPart.padEnd(this.decimalPlaces, '0').substring(0, this.decimalPlaces);
                                        }
                                        return integerPart + ',' + decimalPart;
                                    } else if (decimalPart && showDecimals) {
                                        return integerPart + ',' + decimalPart;
                                    } else {
                                        return integerPart;
                                    }
                                },

                                parseNumber(str, applyRounding = false) {
                                    if (!str || str === '') return null;

                                    // Indonesian format: 1.234.567,89 → 1234567.89
                                    let cleanStr = str.toString().trim();

                                    // Handle the case where user is still typing
                                    if (cleanStr === ',' || cleanStr === '.') {
                                        return null;
                                    }

                                    // Find the last comma (decimal separator)
                                    let lastCommaIndex = cleanStr.lastIndexOf(',');

                                    if (lastCommaIndex !== -1) {
                                        // Has comma - treat as decimal separator
                                        let beforeComma = cleanStr.substring(0, lastCommaIndex);
                                        let afterComma = cleanStr.substring(lastCommaIndex + 1);

                                        // Remove all dots from integer part (thousand separators)
                                        beforeComma = beforeComma.replace(/\./g, '');

                                        // Remove any non-digit characters from decimal part
                                        afterComma = afterComma.replace(/[^0-9]/g, '');

                                        // Reconstruct with dot as decimal separator
                                        cleanStr = beforeComma + (afterComma ? '.' + afterComma : '');
                                    } else {
                                        // No comma - treat all dots as thousand separators
                                        // Remove all dots (they are thousand separators)
                                        cleanStr = cleanStr.replace(/\./g, '');

                                        // Remove any remaining non-numeric characters
                                        cleanStr = cleanStr.replace(/[^0-9]/g, '');
                                    }

                                    if (cleanStr === '' || cleanStr === '.') return null;

                                    let number = parseFloat(cleanStr);

                                    // Only apply rounding when explicitly requested (usually onBlur)
                                    if (applyRounding && this.decimalPlaces !== null && !isNaN(number)) {
                                        number = parseFloat(number.toFixed(this.decimalPlaces));
                                    }

                                    return isNaN(number) ? null : number;
                                },

                                updateDisplay() {
                                    // Check if rawValue has decimals or if current display has comma
                                    let hasDecimals = this.rawValue !== null && this.rawValue !== undefined &&
                                                    (this.rawValue.toString().includes('.') || this.rawValue % 1 !== 0);
                                    let hasComma = this.displayValue.includes(',');

                                    // Show decimals if value has decimals OR user previously typed comma
                                    let showDecimals = hasDecimals || hasComma;
                                    this.displayValue = this.formatNumber(this.rawValue, showDecimals, false);
                                },

                                onInput(event) {
                                    let inputValue = event.target.value;
                                    let cursorPosition = event.target.selectionStart;

                                    // Don't format immediately if user just typed a comma and nothing after
                                    if (inputValue.endsWith(',')) {
                                        this.displayValue = inputValue;
                                        // Still parse to update raw value (no rounding during input)
                                        let parsed = this.parseNumber(inputValue, false);
                                        this.rawValue = parsed;
                                        return;
                                    }

                                    // Parse the input to get raw number (no rounding during input)
                                    let parsed = this.parseNumber(inputValue, false);

                                    // Update raw value - ensure it's always synced properly
                                    this.rawValue = parsed;

                                    // Explicitly sync with Livewire for required validation
                                    this.syncWithLivewire();

                                    // Format the display value
                                    if (inputValue.includes(',')) {
                                        // User has decimals
                                        if (parsed !== null) {
                                            // Format but preserve user input while typing
                                            let commaIndex = inputValue.indexOf(',');
                                            if (commaIndex !== -1 && cursorPosition > commaIndex) {
                                                // User is typing in decimal part, show current input
                                                this.displayValue = inputValue;
                                            } else {
                                                // Format the whole number with decimals since user typed comma (no rounding)
                                                this.displayValue = this.formatNumber(parsed, true, false);
                                            }
                                        } else {
                                            this.displayValue = inputValue;
                                        }
                                    } else {
                                        // No comma in input - format as whole number with thousand separators only
                                        if (parsed !== null && parsed !== 0) {
                                            this.displayValue = Math.floor(parsed).toLocaleString('de-DE');
                                        } else {
                                            this.displayValue = inputValue;
                                        }
                                    }
                                },

                                onBlur(event) {
                                    let inputValue = event.target.value;

                                    // Parse and format the final value when user leaves the field (with rounding)
                                    let parsed = this.parseNumber(inputValue, true);
                                    this.rawValue = parsed;

                                    // Explicitly sync with Livewire for validation
                                    this.syncWithLivewire();

                                    if (parsed !== null) {
                                        // Only show decimals if the input contained a comma (user typed decimals)
                                        let hasComma = inputValue.includes(',');
                                        this.displayValue = this.formatNumber(parsed, hasComma, true);
                                        event.target.value = this.displayValue;
                                    } else {
                                        this.displayValue = '';
                                        event.target.value = '';
                                    }
                                },

                                onKeydown(event) {
                                    // Allow: backspace, delete, tab, escape, enter
                                    if ([8, 9, 27, 13, 46].includes(event.keyCode) ||
                                        // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X, Ctrl+Z
                                        (event.ctrlKey && [65, 67, 86, 88, 90].includes(event.keyCode)) ||
                                        // Allow: home, end, left, right, up, down
                                        (event.keyCode >= 35 && event.keyCode <= 40)) {
                                        return;
                                    }

                                    // Allow numbers (0-9) from both regular and numpad
                                    if ((event.keyCode >= 48 && event.keyCode <= 57) ||
                                        (event.keyCode >= 96 && event.keyCode <= 105)) {
                                        return;
                                    }

                                    // Allow comma (,) for decimal separator - key code 188
                                    if (event.keyCode === 188 || event.key === ',') {
                                        // Don't allow comma if decimalPlaces is 0
                                        if (this.decimalPlaces === 0) {
                                            event.preventDefault();
                                            return;
                                        }

                                        // Check if comma already exists in current value
                                        let currentValue = event.target.value;
                                        if (currentValue.includes(',')) {
                                            event.preventDefault();
                                            return;
                                        }
                                        // Allow the comma
                                        return;
                                    }

                                    // Block all other keys
                                    event.preventDefault();
                                },

                                syncWithLivewire() {
                                    // Force sync the value with Livewire for proper validation
                                    // This ensures required validation works even with empty values
                                    $wire.set('{{ $model }}', this.rawValue);
                                }
                            }"
                            x-init="updateDisplay(); $watch('rawValue', () => updateDisplay());
                                     // Ensure proper sync on init
                                     this.syncWithLivewire();"
                            type="text"
                            inputmode="decimal"
                            id="{{ $id }}"
                            class="form-control @error($model) is-invalid @enderror"
                            @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                            @if(isset($required) && $required === 'true') required @endif
                            placeholder="{{ isset($label) ? $label : '' }}"
                            autocomplete="off"
                            @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif
                            x-on:input="onInput($event)"
                            x-on:blur="onBlur($event)"
                            x-on:keydown="onKeydown($event)"
                            x-bind:value="displayValue">
                    </div>
                @else
                    <input x-data="{
                            rawValue: @entangle($model),
                            displayValue: '',
                            decimalPlaces: {{ isset($decimalPlaces) && $decimalPlaces !== null ? $decimalPlaces : 'null' }},

                            formatNumber(num, showDecimals = false, applyRounding = false) {
                                if (!num || num === '' || num === null || num === undefined) {
                                    return '';
                                }
                                // Convert to number and format with Indonesian style (dots for thousands, comma for decimals)
                                let number = parseFloat(num);
                                if (isNaN(number)) return '';

                                // Apply decimal rounding only when explicitly requested (usually onBlur)
                                if (applyRounding && this.decimalPlaces !== null) {
                                    number = parseFloat(number.toFixed(this.decimalPlaces));
                                }

                                // Format using Indonesian locale style
                                let parts = number.toString().split('.');
                                let integerPart = parseInt(parts[0]).toLocaleString('de-DE'); // Use German locale for dot separation
                                let decimalPart = parts[1] || '';

                                // Only show decimals if explicitly requested (user typed comma) or if original number has decimals
                                if (showDecimals && this.decimalPlaces !== null && this.decimalPlaces > 0) {
                                    if (decimalPart === '') {
                                        decimalPart = '0'.repeat(this.decimalPlaces);
                                    } else if (applyRounding) {
                                        // Only pad to exact decimal places when rounding (onBlur)
                                        decimalPart = decimalPart.padEnd(this.decimalPlaces, '0').substring(0, this.decimalPlaces);
                                    }
                                    return integerPart + ',' + decimalPart;
                                } else if (decimalPart && showDecimals) {
                                    return integerPart + ',' + decimalPart;
                                } else {
                                    return integerPart;
                                }
                            },

                            parseNumber(str, applyRounding = false) {
                                if (!str || str === '') return null;

                                // Indonesian format: 1.234.567,89 → 1234567.89
                                let cleanStr = str.toString().trim();

                                // Handle the case where user is still typing
                                if (cleanStr === ',' || cleanStr === '.') {
                                    return null;
                                }

                                // Find the last comma (decimal separator)
                                let lastCommaIndex = cleanStr.lastIndexOf(',');

                                if (lastCommaIndex !== -1) {
                                    // Has comma - treat as decimal separator
                                    let beforeComma = cleanStr.substring(0, lastCommaIndex);
                                    let afterComma = cleanStr.substring(lastCommaIndex + 1);

                                    // Remove all dots from integer part (thousand separators)
                                    beforeComma = beforeComma.replace(/\./g, '');

                                    // Remove any non-digit characters from decimal part
                                    afterComma = afterComma.replace(/[^0-9]/g, '');

                                    // Reconstruct with dot as decimal separator
                                    cleanStr = beforeComma + (afterComma ? '.' + afterComma : '');
                                } else {
                                    // No comma - treat all dots as thousand separators
                                    // Remove all dots (they are thousand separators)
                                    cleanStr = cleanStr.replace(/\./g, '');

                                    // Remove any remaining non-numeric characters
                                    cleanStr = cleanStr.replace(/[^0-9]/g, '');
                                }

                                if (cleanStr === '' || cleanStr === '.') return null;

                                let number = parseFloat(cleanStr);

                                // Only apply rounding when explicitly requested (usually onBlur)
                                if (applyRounding && this.decimalPlaces !== null && !isNaN(number)) {
                                    number = parseFloat(number.toFixed(this.decimalPlaces));
                                }

                                return isNaN(number) ? null : number;
                            },

                            updateDisplay() {
                                // Check if rawValue has decimals or if current display has comma
                                let hasDecimals = this.rawValue !== null && this.rawValue !== undefined &&
                                                (this.rawValue.toString().includes('.') || this.rawValue % 1 !== 0);
                                let hasComma = this.displayValue.includes(',');

                                // Show decimals if value has decimals OR user previously typed comma
                                let showDecimals = hasDecimals || hasComma;
                                this.displayValue = this.formatNumber(this.rawValue, showDecimals, false);
                            },

                            onInput(event) {
                                let inputValue = event.target.value;
                                let cursorPosition = event.target.selectionStart;

                                // Don't format immediately if user just typed a comma
                                if (inputValue.endsWith(',')) {
                                    this.displayValue = inputValue;
                                    return;
                                }

                                // Don't format if user is in the middle of typing decimal
                                if (inputValue.includes(',') && inputValue.split(',')[1] === '') {
                                    this.displayValue = inputValue;
                                    return;
                                }

                                // Parse the input to get raw number (no rounding during input)
                                let parsed = this.parseNumber(inputValue, false);

                                // Update raw value
                                this.rawValue = parsed;

                                // Explicitly sync with Livewire for required validation
                                this.syncWithLivewire();

                                // Only format if the input contains a comma (user wants decimals)
                                // For whole numbers without comma, just add thousand separators
                                if (inputValue.includes(',')) {
                                    // User typed comma, so they want decimal formatting
                                    if (parsed !== null) {
                                        let newDisplayValue = this.formatNumber(parsed, true, false);

                                        // If user is typing after comma, don't reformat yet
                                        let commaIndex = inputValue.indexOf(',');
                                        if (commaIndex !== -1 && cursorPosition > commaIndex) {
                                            // User is typing decimal part, keep the input as is
                                            this.displayValue = inputValue;
                                        } else {
                                            this.displayValue = newDisplayValue;
                                        }
                                    } else {
                                        this.displayValue = inputValue;
                                    }
                                } else {
                                    // No comma in input - format as whole number with thousand separators only
                                    if (parsed !== null && parsed !== 0) {
                                        // Format only with thousand separators, no decimal
                                        this.displayValue = Math.floor(parsed).toLocaleString('de-DE');
                                    } else {
                                        this.displayValue = inputValue;
                                    }
                                }
                            },

                            onBlur(event) {
                                let inputValue = event.target.value;

                                // Parse and format the final value when user leaves the field (with rounding)
                                let parsed = this.parseNumber(inputValue, true);
                                this.rawValue = parsed;

                                // Explicitly sync with Livewire for validation
                                this.syncWithLivewire();

                                if (parsed !== null) {
                                    // Only show decimals if the input contained a comma (user typed decimals)
                                    let hasComma = inputValue.includes(',');
                                    this.displayValue = this.formatNumber(parsed, hasComma, true);
                                    event.target.value = this.displayValue;
                                } else {
                                    this.displayValue = '';
                                    event.target.value = '';
                                }
                            },

                            onKeydown(event) {
                                // Allow: backspace, delete, tab, escape, enter
                                if ([8, 9, 27, 13, 46].includes(event.keyCode) ||
                                    // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X, Ctrl+Z
                                    (event.ctrlKey && [65, 67, 86, 88, 90].includes(event.keyCode)) ||
                                    // Allow: home, end, left, right, up, down
                                    (event.keyCode >= 35 && event.keyCode <= 40)) {
                                    return;
                                }

                                // Allow numbers (0-9) from both regular and numpad
                                if ((event.keyCode >= 48 && event.keyCode <= 57) ||
                                    (event.keyCode >= 96 && event.keyCode <= 105)) {
                                    return;
                                }

                                // Allow comma (,) for decimal separator - key code 188
                                if (event.keyCode === 188 || event.key === ',') {
                                    // Don't allow comma if decimalPlaces is 0
                                    if (this.decimalPlaces === 0) {
                                        event.preventDefault();
                                        return;
                                    }

                                    // Check if comma already exists in current value
                                    let currentValue = event.target.value;
                                    if (currentValue.includes(',')) {
                                        event.preventDefault();
                                        return;
                                    }
                                    // Allow the comma
                                    return;
                                }

                                // Block all other keys
                                event.preventDefault();
                            },

                            syncWithLivewire() {
                                // Force sync the value with Livewire for proper validation
                                // This ensures required validation works even with empty values
                                $wire.set('{{ $model }}', this.rawValue);
                            }
                        }"
                        x-init="updateDisplay(); $watch('rawValue', () => updateDisplay());
                                 // Ensure proper sync on init
                                 this.syncWithLivewire();"
                        type="text"
                        inputmode="decimal"
                        id="{{ $id }}"
                        class="form-control @error($model) is-invalid @enderror"
                        @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                        @if(isset($required) && $required === 'true') required @endif
                        placeholder="{{ isset($label) ? $label : '' }}"
                        autocomplete="off"
                        @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif
                        x-on:input="onInput($event)"
                        x-on:blur="onBlur($event)"
                        x-on:keydown="onKeydown($event)"
                        x-bind:value="displayValue">
                @endif
            @elseif(isset($type) && $type === 'image')
                <input wire:model="{{ $model }}" id="{{ $id }}" type="file" class="form-control @error($model) is-invalid @enderror" accept="image/*"
                       @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                       @if(isset($required) && $required === 'true') required @endif
                       @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif />
            @else
                <input wire:model="{{ $model }}" type="{{ isset($type) ? $type : 'text' }}" class="form-control @error($model) is-invalid @enderror"
                       @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                       @if(isset($required) && $required === 'true') required @endif
                       placeholder="{{ isset($label) ? $label : '' }}" autocomplete="off"
                       @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif />
            @endif

            @if (!empty($label))
                <label for="{{ $id }}" class="@if(isset($required) && $required === 'true') required @endif">{{ $label }}</label>
            @endif
            @if(!empty($placeHolder))
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
                    <button type="button" class="btn btn-secondary" wire:click="{{ $clickEvent }}"
                            @if ((isset($action) && $action === 'View') || (isset($buttonEnabled) && $buttonEnabled === 'false')) disabled @endif>
                        {{ $buttonName }}
                    </button>
                </span>
                <span wire:loading wire:target="{{ isset($clickEvent) ? $clickEvent : '' }}">
                    <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
                </span>
            </div>
        @endif
    </div>
</div>
