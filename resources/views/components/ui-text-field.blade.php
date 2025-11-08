@php
    $id = str_replace(['.', '[', ']'], '_', $model);

    // Parameter untuk decimal places
    $decimalPlaces = isset($decimalPlaces) ? intval($decimalPlaces) : null;

    // Determine if field has label for styling purposes
    $hasLabel = !empty($label);
    $inputClass = $hasLabel ? 'form-control' : 'form-control form-control-sm';

    // Set textarea class with appropriate styling
    $textareaClass = $hasLabel ? 'form-control' : 'form-control mb-4';

    // Set column class with bottom margin when label is present
    $colClass = 'col-sm' . (!empty($label) ? ' mb-4' : '');
@endphp

<div class="{{ $colClass }}" @if(isset($span)) span="{{ $span }}" @endif @if(isset($visible) && $visible === 'false') style="display: none;" @endif>

    <div class="input-group">
        <div class="{{ !empty($label) ? 'form-floating' : '' }} flex-grow-1 position-relative">
            @if(isset($type) && $type === 'textarea')
                <textarea style="min-height: 80px;" wire:model="{{ $model }}" id="{{ $id }}" rows="{{ isset($rows) ? $rows : '10' }}" class="{{ $textareaClass }} @error($model) is-invalid @enderror"
                          @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                          @if(isset($required) && $required === 'true') required @endif
                          placeholder="{{ isset($label) ? $label : '' }}"
                          @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif autocomplete="off"></textarea>
            @elseif(isset($type) && $type === 'document')
                <input wire:model="{{ $model }}" id="{{ $id }}" type="file" class="{{ $inputClass }} @error($model) is-invalid @enderror"
                       @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                       @if(isset($required) && $required === 'true') required @endif accept=".pdf, .doc, .docx"
                       @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif />
            @elseif(isset($type) && $type === 'barcode')
                <input wire:model="{{ $model }}" x-data="{
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
                    }" x-init="initBarcode()" id="{{ $id }}" type="text" class="{{ $inputClass }} @error($model) is-invalid @enderror"
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
                        <span class="input-group-text">
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
                        </span>
                        <div class="{{ !empty($label) ? 'form-floating' : '' }} flex-grow-1 position-relative">
                            <input x-data="{
                                rawValue: @entangle($model).live,
                                displayValue: '',

                                init() {
                                    // Watch for external model changes from Livewire
                                    this.$wire.$watch('{{ $model }}', (value) => {
                                        this.rawValue = value;
                                        this.updateDisplay();
                                    });
                                },

                            formatNumber(num, showDecimals = false) {
                                if (!num || num === '' || num === null || num === undefined) {
                                    return '';
                                }
                                let number = parseFloat(num);
                                if (isNaN(number)) return '';

                                // Fix floating point precision issues by rounding to 10 decimal places first
                                number = Math.round(number * 10000000000) / 10000000000;

                                // Jika decimalPlaces diset, bulatkan ke jumlah decimal tersebut
                                @if(isset($decimalPlaces))
                                // Bulatkan ke jumlah decimal yang ditentukan dengan Math.round untuk precision
                                number = Math.round(number * Math.pow(10, {{ $decimalPlaces }})) / Math.pow(10, {{ $decimalPlaces }});
                                @endif

                                let parts = number.toString().split('.');
                                let integerPart = parseInt(parts[0]).toLocaleString('de-DE');
                                let decimalPart = parts[1] || '';

                                if (decimalPart) {
                                    @if(isset($decimalPlaces))
                                    // Pad dengan 0 jika perlu untuk mencapai jumlah decimal yang diinginkan
                                    while (decimalPart.length < {{ $decimalPlaces }}) {
                                        decimalPart += '0';
                                    }
                                    // Potong jika terlalu panjang
                                    decimalPart = decimalPart.substring(0, {{ $decimalPlaces }});

                                    // Jika semua digit decimal adalah 0, jangan tampilkan decimal
                                    if (decimalPart.replace(/0/g, '') === '') {
                                        return integerPart;
                                    }
                                    @else
                                    // Jika tidak ada decimalPlaces, tampilkan maksimal 2 decimal untuk mencegah precision error
                                    if (decimalPart.length > 2) {
                                        decimalPart = decimalPart.substring(0, 2);
                                    }
                                    @endif
                                    return integerPart + ',' + decimalPart;
                                }

                                if (showDecimals) {
                                    @if(isset($decimalPlaces))
                                    // Cek apakah decimalPlaces menghasilkan semua 0 - jika ya, jangan tampilkan
                                    let zeroDecimal = '0'.repeat({{ $decimalPlaces }});
                                    if (zeroDecimal.replace(/0/g, '') === '') {
                                        return integerPart; // Jangan tampilkan ,00 atau ,000 dst
                                    }
                                    return integerPart + ',' + zeroDecimal;
                                    @else
                                    // Jika tidak ada decimalPlaces yang di-set, jangan tampilkan ,00
                                    return integerPart;
                                    @endif
                                }

                                // Jika tidak ada decimal dan tidak diminta showDecimals, tampilkan integer saja
                                return integerPart;
                            },                                parseNumber(str) {
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

                                        // If decimalPlaces is set, limit decimal length during parsing
                                        @if(isset($decimalPlaces))
                                        if (afterComma.length > {{ $decimalPlaces }}) {
                                            afterComma = afterComma.substring(0, {{ $decimalPlaces }});
                                        }
                                        @endif

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

                                    return isNaN(number) ? null : number;
                                },                                updateDisplay() {
                                    if (this.rawValue === null || this.rawValue === undefined || this.rawValue === '') {
                                        this.displayValue = '';
                                        if (this.$el) {
                                            this.$el.value = '';
                                        }
                                        return;
                                    }

                                let numberValue;
                                if (typeof this.rawValue === 'string') {
                                    numberValue = this.parseNumber(this.rawValue);
                                } else {
                                    numberValue = this.rawValue;
                                }

                                // Apply precision fix EARLY to prevent floating point errors
                                if (numberValue !== null && numberValue !== undefined) {
                                    numberValue = Math.round(numberValue * 10000000000) / 10000000000;
                                    this.rawValue = numberValue; // Update rawValue with precision-fixed number
                                }

                                if (numberValue !== null && numberValue !== undefined) {
                                    @if(isset($decimalPlaces))
                                    // Jika decimalPlaces di-set, selalu tampilkan dengan decimal
                                    this.displayValue = this.formatNumber(numberValue, true);
                                    @else
                                    // Check if number has meaningful decimal places
                                    let remainder = Math.abs(numberValue % 1);
                                    let hasRealDecimals = false;

                                    // If remainder is very close to 1.0, round to next integer
                                    if (remainder > 0.9999) {
                                        numberValue = Math.round(numberValue);
                                        this.rawValue = numberValue; // Update rawValue with rounded number
                                        remainder = 0;
                                    } else if (remainder > 0.0001) {
                                        hasRealDecimals = true;
                                    }

                                    this.displayValue = this.formatNumber(numberValue, hasRealDecimals);
                                    @endif
                                } else {
                                    this.displayValue = '';
                                }                                    if (this.$el) {
                                        this.$el.value = this.displayValue;
                                    }
                                },

                                onInput(event) {
                                    let inputValue = event.target.value;
                                    let cursorPosition = event.target.selectionStart;

                                    // Don't format immediately if user just typed a comma and nothing after
                                    if (inputValue.endsWith(',')) {
                                        this.displayValue = inputValue;
                                        // Still parse to update raw value
                                        let parsed = this.parseNumber(inputValue);
                                        this.rawValue = parsed;
                                        return;
                                    }

                                    // Parse the input to get raw number
                                    let parsed = this.parseNumber(inputValue);

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
                                                // Format the whole number with decimals since user typed comma
                                                this.displayValue = this.formatNumber(parsed, true);
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

                                    // Parse and format the final value when user leaves the field
                                    let parsed = this.parseNumber(inputValue);
                                    this.rawValue = parsed;

                                    // Explicitly sync with Livewire for validation
                                    this.syncWithLivewire();

                                    if (parsed !== null) {
                                        // Show decimals if the input contained a comma (user typed decimals) OR if parsed value has decimals
                                        let hasComma = inputValue.includes(',');
                                        let valueHasDecimals = parsed % 1 !== 0; // Check if value is not a whole number
                                        let showDecimals = hasComma || valueHasDecimals;

                                        this.displayValue = this.formatNumber(parsed, showDecimals);
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
                                    let valueToSync = this.rawValue;
                                    if (typeof valueToSync === 'string') {
                                        valueToSync = this.parseNumber(valueToSync);
                                    }
                                    $wire.set('{{ $model }}', valueToSync);
                                }
                            }"
                            x-init="init();
                                     updateDisplay();
                                     $watch('rawValue', (value, oldValue) => {
                                         // Handle reset case - when value becomes null/undefined/empty, clear display
                                         if (value === null || value === undefined || value === '') {
                                             displayValue = '';
                                             if ($el) {
                                                 $el.value = '';
                                             }
                                             return;
                                         }

                                         // Always normalize rawValue to dot-decimal number
                                         if (typeof value === 'string') {
                                             let parsed = parseNumber(value);
                                             if (parsed !== rawValue) {
                                                 rawValue = parsed;
                                             }
                                         }
                                         updateDisplay();
                                     });"
                            type="text"
                            inputmode="decimal"
                            id="{{ $id }}"
                            class="{{ $inputClass }} @error($model) is-invalid @enderror"
                            @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                            @if(isset($required) && $required === 'true') required @endif
                            placeholder="{{ isset($label) ? $label : '' }}"
                            autocomplete="off"
                            @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif
                            x-on:input="onInput($event)"
                            x-on:blur="onBlur($event)"
                            x-on:keydown="onKeydown($event)"
                            x-bind:value="displayValue">

                            @if (!empty($label))
                                <label for="{{ $id }}" class="@if(isset($required) && $required === 'true') required @endif">{{ $label }}</label>
                            @endif
                        </div>
                    </div>
                @else
                    <input x-data="{
                            rawValue: @entangle($model).live,
                            displayValue: '',

                            init() {
                                // Watch for external model changes from Livewire
                                this.$wire.$watch('{{ $model }}', (value) => {
                                    this.rawValue = value;
                                    this.updateDisplay();
                                });
                            },

                            formatNumber(num, showDecimals = false) {
                                if (!num || num === '' || num === null || num === undefined) {
                                    return '';
                                }
                                let number = parseFloat(num);
                                if (isNaN(number)) return '';

                                // Fix floating point precision issues by rounding to 10 decimal places first
                                number = Math.round(number * 10000000000) / 10000000000;

                                // Jika decimalPlaces diset, bulatkan ke jumlah decimal tersebut
                                @if(isset($decimalPlaces))
                                // Bulatkan ke jumlah decimal yang ditentukan dengan Math.round untuk precision
                                number = Math.round(number * Math.pow(10, {{ $decimalPlaces }})) / Math.pow(10, {{ $decimalPlaces }});
                                @endif

                                let parts = number.toString().split('.');
                            let integerPart = parseInt(parts[0]).toLocaleString('de-DE');
                            let decimalPart = parts[1] || '';

                            if (decimalPart) {
                                @if(isset($decimalPlaces))
                                // Pad dengan 0 jika perlu untuk mencapai jumlah decimal yang diinginkan
                                while (decimalPart.length < {{ $decimalPlaces }}) {
                                    decimalPart += '0';
                                }
                                // Potong jika terlalu panjang
                                decimalPart = decimalPart.substring(0, {{ $decimalPlaces }});

                                // Jika semua digit decimal adalah 0, jangan tampilkan decimal
                                if (decimalPart.replace(/0/g, '') === '') {
                                    return integerPart;
                                }
                                @else
                                // Jika tidak ada decimalPlaces, tampilkan maksimal 2 decimal untuk mencegah precision error
                                if (decimalPart.length > 2) {
                                    decimalPart = decimalPart.substring(0, 2);
                                }
                                @endif
                                return integerPart + ',' + decimalPart;
                            }

                            if (showDecimals) {
                                @if(isset($decimalPlaces))
                                // Cek apakah decimalPlaces menghasilkan semua 0 - jika ya, jangan tampilkan
                                let zeroDecimal = '0'.repeat({{ $decimalPlaces }});
                                if (zeroDecimal.replace(/0/g, '') === '') {
                                    return integerPart; // Jangan tampilkan ,00 atau ,000 dst
                                }
                                return integerPart + ',' + zeroDecimal;
                                @else
                                // Jika tidak ada decimalPlaces yang di-set, jangan tampilkan ,00
                                return integerPart;
                                @endif
                            }

                            // Jika tidak ada decimal dan tidak diminta showDecimals, tampilkan integer saja
                            return integerPart;
                        },

                        parseNumber(str) {
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

                                    // If decimalPlaces is set, limit decimal length during parsing
                                    @if(isset($decimalPlaces))
                                    if (afterComma.length > {{ $decimalPlaces }}) {
                                        afterComma = afterComma.substring(0, {{ $decimalPlaces }});
                                    }
                                    @endif

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

                                return isNaN(number) ? null : number;
                            },                            updateDisplay() {
                                if (this.rawValue === null || this.rawValue === undefined || this.rawValue === '') {
                                    this.displayValue = '';
                                    if (this.$el) {
                                        this.$el.value = '';
                                    }
                                    return;
                                }

                                let numberValue;
                                if (typeof this.rawValue === 'string') {
                                    numberValue = this.parseNumber(this.rawValue);
                                } else {
                                    numberValue = this.rawValue;
                                }

                                // Apply precision fix and update rawValue
                                if (numberValue !== null && numberValue !== undefined) {
                                    numberValue = Math.round(numberValue * 10000000000) / 10000000000;
                                    this.rawValue = numberValue; // Update rawValue with precision-fixed number
                                }

                                if (numberValue !== null && numberValue !== undefined) {
                                    @if(isset($decimalPlaces))
                                    // Jika decimalPlaces di-set, selalu tampilkan dengan decimal
                                    this.displayValue = this.formatNumber(numberValue, true);
                                    @else
                                    // Check if number has meaningful decimal places
                                    let remainder = Math.abs(numberValue % 1);
                                    let hasRealDecimals = false;

                                    // If remainder is very close to 1.0, round to next integer
                                    if (remainder > 0.9999) {
                                        numberValue = Math.round(numberValue);
                                        this.rawValue = numberValue; // Update rawValue with rounded number
                                        remainder = 0;
                                    } else if (remainder > 0.0001) {
                                        hasRealDecimals = true;
                                    }

                                    this.displayValue = this.formatNumber(numberValue, hasRealDecimals);
                                    @endif
                                } else {
                                    this.displayValue = '';
                                }

                                if (this.$el) {
                                    this.$el.value = this.displayValue;
                                }
                            },

                            onInput(event) {
                                let inputValue = event.target.value;
                                let cursorPosition = event.target.selectionStart;

                                // Don't format immediately if user just typed a comma
                                if (inputValue.endsWith(',')) {
                                    this.displayValue = inputValue;
                                    // Still parse to update raw value
                                    let parsed = this.parseNumber(inputValue);
                                    this.rawValue = parsed;
                                    return;
                                }

                                // Don't format if user is in the middle of typing decimal
                                if (inputValue.includes(',') && inputValue.split(',')[1] === '') {
                                    this.displayValue = inputValue;
                                    return;
                                }

                                // Parse the input to get raw number
                                let parsed = this.parseNumber(inputValue);

                                // Update raw value
                                this.rawValue = parsed;

                                // Explicitly sync with Livewire for required validation
                                this.syncWithLivewire();

                                // Only format if the input contains a comma (user wants decimals)
                                // For whole numbers without comma, just add thousand separators
                                if (inputValue.includes(',')) {
                                    // User typed comma, so they want decimal formatting
                                    if (parsed !== null) {
                                        let newDisplayValue = this.formatNumber(parsed, true);

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

                                // Parse and format the final value when user leaves the field
                                let parsed = this.parseNumber(inputValue);
                                this.rawValue = parsed;

                                // Explicitly sync with Livewire for validation
                                this.syncWithLivewire();

                                if (parsed !== null) {
                                    // Show decimals if the input contained a comma (user typed decimals) OR if parsed value has decimals
                                    let hasComma = inputValue.includes(',');
                                    let valueHasDecimals = parsed % 1 !== 0; // Check if value is not a whole number
                                    let showDecimals = hasComma || valueHasDecimals;

                                    this.displayValue = this.formatNumber(parsed, showDecimals);
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
                                let valueToSync = this.rawValue;
                                if (typeof valueToSync === 'string') {
                                    valueToSync = this.parseNumber(valueToSync);
                                }
                                $wire.set('{{ $model }}', valueToSync);
                            }
                        }"
                        x-init="init();
                                 updateDisplay();
                                 $watch('rawValue', (value, oldValue) => {
                                     // Handle reset case - when value becomes null/undefined/empty, clear display
                                     if (value === null || value === undefined || value === '') {
                                         displayValue = '';
                                         if ($el) {
                                             $el.value = '';
                                         }
                                         return;
                                     }

                                     // Always normalize rawValue to dot-decimal number
                                     if (typeof value === 'string') {
                                         let parsed = parseNumber(value);
                                         if (parsed !== rawValue) {
                                             rawValue = parsed;
                                         }
                                     }
                                     updateDisplay();
                                 });"
                        type="text"
                        inputmode="decimal"
                        id="{{ $id }}"
                        class="{{ $inputClass }} @error($model) is-invalid @enderror"
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
                <input wire:model="{{ $model }}" id="{{ $id }}" type="file" class="{{ $inputClass }} @error($model) is-invalid @enderror" accept="image/*"
                       @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                       @if(isset($required) && $required === 'true') required @endif
                       @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif />
            @else
                <input wire:model="{{ $model }}" type="{{ isset($type) ? $type : 'text' }}" class="{{ $inputClass }} @error($model) is-invalid @enderror"
                       @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif
                       @if(isset($required) && $required === 'true') required @endif
                       placeholder="{{ isset($label) ? $label : '' }}" autocomplete="off"
                       @if(isset($onChanged) && $onChanged !== '') wire:change="{{ $onChanged }}" wire:keydown.enter="{{ $onChanged }}" @endif
                       @if(isset($capslockMode) && $capslockMode === 'true')
                       x-data="{
                           initCapslock() {
                               let input = this.$el;
                               // Convert to uppercase when typing (input event)
                               input.addEventListener('input', function() {
                                   if (input.value) {
                                       let cursorPosition = input.selectionStart;
                                       input.value = input.value.toUpperCase();
                                       // Restore cursor position
                                       input.setSelectionRange(cursorPosition, cursorPosition);
                                   }
                               });

                               // Also convert on blur as a fallback
                               input.addEventListener('blur', function() {
                                   if (input.value) {
                                       input.value = input.value.toUpperCase();
                                       // Trigger Livewire model update
                                       input.dispatchEvent(new Event('input', { bubbles: true }));
                                   }
                               });
                           }
                       }"
                       x-init="initCapslock()"
                       @endif
                       />
            @endif

            @if (!empty($label) && !(isset($type) && $type === 'number' && isset($currency) && $currency !== ''))
                <label for="{{ $id }}" class="@if(isset($required) && $required === 'true') required @endif">{{ $label }}</label>
            @endif
            @if(!empty($placeHolder))
                <div class="placeholder-text position-absolute text-muted" style="left: .75rem; top: calc(100% + .3rem); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: calc(100% - 2.5rem);">{{ $placeHolder }}</div>
            @endif
            @error($model)
                <div class="error-message" style="position: relative; margin-top: 5px; font-size: 0.875em; color: #dc3545;">{{ $message }}</div>
            @enderror
        </div>

        <!-- Refresh Button -->
        @if (isset($clickEvent) && $clickEvent !== '')
            <x-ui-button type="InputButton" :clickEvent="$clickEvent" cssClass="btn btn-secondary"
                :buttonName="$buttonName" :action="$action" :enabled="$buttonEnabled" loading="true" />
        @endif
    </div>
</div>
