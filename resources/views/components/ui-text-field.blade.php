@php
$id = str_replace(['.', '[', ']'], '_', $model);
@endphp

<div class="col-sm mb-5" @if(isset($span)) span="{{ $span }}" @endif @if(isset($visible) && $visible==='false' ) style="display: none;" @endif>
    <div class="d-flex align-items-center">
        <div class="form-floating flex-grow-1">
            @if(isset($type) && $type === 'textarea')
            <textarea wire:model.lazy="{{ $model }}" id="{{ $id }}" rows="{{ isset($rows) ? $rows : '10' }}" class="form-control @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($label) ? $label : '' }}" @if(isset($onChanged) && $onChanged !=='' ) wire:keyup="{{ $onChanged }}" @endif autocomplete="off" ></textarea>
            @if (!empty($label))
            <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
            @endif
            @elseif(isset($type) && $type === 'document')
            <input wire:model.lazy="{{ $model }}" id="{{ $id }}" type="file" class="form-control @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif accept=".pdf, .doc, .docx" @if(isset($onChanged) && $onChanged !=='' ) wire:keyup="{{ $onChanged }}" @endif  />
            @if (!empty($label))
            <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
            @endif
            @elseif(isset($type) && $type === 'barcode')
            <input x-data="{
                initBarcode() {
                    let barcodeInput = this.$refs.inputField;
                    if (barcodeInput) {
                        console.log('Initializing barcode input for:', barcodeInput.id);

                        window.addEventListener('barcode-processed', function() {
                            barcodeInput.value = '';
                            barcodeInput.focus();
                        });

                        barcodeInput.addEventListener('keydown', function(event) {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                if (barcodeInput.value !== '') {
                                    console.log(barcodeInput.value);
                                    Livewire.dispatch('scanBarcode', barcodeInput.value);
                                }
                            }
                        });
                    }
                }
            }" x-init="initBarcode()" wire:model="{{ $model }}" id="{{ $id }}" type="text" class="form-control @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($label) ? $label : '' }}" autocomplete="off" @if(isset($onChanged) && $onChanged !=='' ) wire:keyup="{{ $onChanged }}" @endif  x-ref="inputField">
            @if (!empty($label))
            <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
            @endif
            @elseif(isset($type) && $type === 'code')
            <input wire:model.lazy="{{ $model }}" type="text" class="form-control @error($model) is-invalid @enderror" @if((isset($action) && ($action=='Edit' || $action=='View' )) || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($label) ? $label : '' }}" autocomplete="off" @if(isset($onChanged) && $onChanged !=='' ) wire:keyup="{{ $onChanged }}" @endif  />
            @if (!empty($label))
            <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
            @endif
            @elseif(isset($type) && $type === 'date')
            <input x-data="{
                initDatepicker() {
                    let input = this.$refs.inputField;
                    if (input) {
                        console.log('Initializing datepicker for:', input.id);

                        myJQuery(input).datepicker({
                            dateFormat: 'dd-mm-yy',
                            changeMonth: true,
                            changeYear: true,
                            showButtonPanel: true
                        }).on('change', function() {
                            $wire.set('{{ $model }}', myJQuery(this).val());
                            @if(isset($onChanged) && $onChanged !== '')
                            Livewire.dispatch('{{ $onChanged }}');
                            @endif
                        });
                    }
                }
            }" x-init="initDatepicker()" wire:model.lazy="{{ $model }}" id="{{ $id }}" type="text" class="form-control @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif @if(isset($onChanged) && $onChanged !=='' ) wire:keyup="{{ $onChanged }}" @endif readonly="readonly"  x-ref="inputField">

            @if (!empty($label))
            <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
            @endif
            @elseif(isset($type) && $type === 'number')
            <input x-data="{
                    initInputMask() {
                        let input = this.$refs.inputField;
                        if (input) {

                            Inputmask({
                                alias: 'numeric',
                                groupSeparator: '.',
                                radixPoint: ',',
                                autoGroup: true,
                                digitsOptional: true,
                                rightAlign: false,
                                clearIncomplete: true,
                                allowMinus: false,
                                placeholder: '0'
                            }).mask(input);

                            input.addEventListener('blur', () => {
                                if (input.value.trim() === '') {
                                    input.value = '0';
                                }
                                $wire.set('{{ $model }}', input.value);
                            });
                        }
                    }
                }" x-init="initInputMask()" wire:model.debounce.300ms="{{ $model }}" id="{{ $id }}" type="text" class="form-control number-mask @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($label) ? $label : '' }}" autocomplete="off" @if(isset($onChanged) && $onChanged !=='' ) wire:keyup="{{ $onChanged }}" @endif  x-ref="inputField">
            @if (!empty($label))
            <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
            @endif

            @elseif(isset($type) && $type === 'image')
            <input wire:model.lazy="{{ $model }}" id="{{ $id }}" type="file" class="form-control @error($model) is-invalid @enderror" accept="image/*" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif @if(isset($onChanged) && $onChanged !=='' ) wire:keyup="{{ $onChanged }}" @endif  />
            @if (!empty($label))
            <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
            @endif
            @else
            <input wire:model.lazy="{{ $model }}" type="{{ isset($type) ? $type : 'text' }}" class="form-control @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($label) ? $label : '' }}" autocomplete="off" @if(isset($onChanged) && $onChanged !=='' ) wire:keyup="{{ $onChanged }}" @endif  />
            @if (!empty($label))
            <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
            @endif
            @endif

            @error($model)
            <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <!-- Refresh Button -->
        @if (isset($clickEvent) && $clickEvent !== '')
        <div class="d-flex align-items-center ms-2">
            <span wire:loading.remove>
                <button type="button" class="btn btn-secondary" wire:click="{{ $clickEvent }}" @if ((!empty($action) && $action==='View' ) || (isset($enabled) && $enabled==='false' )) disabled @endif>
                    {{ $buttonName }}
                </button>
            </span>
            <span wire:loading>
                <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
            </span>
        </div>
        @endif
    </div>
</div>

