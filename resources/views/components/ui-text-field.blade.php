@php
$id = str_replace(['.', '[', ']'], '_', $model);
@endphp

<div class="col-sm mb-5" @if(isset($span)) span="{{ $span }}" @endif @if(isset($visible) && $visible==='false' ) style="display: none;" @endif>
    <div class="d-flex align-items-center">
        <div class="form-floating flex-grow-1">
            @if(isset($type) && $type === 'textarea')
            <textarea wire:model.defer="{{ $model }}" id="{{ $id }}" rows="{{ isset($rows) ? $rows : '10' }}" class="form-control @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" autocomplete="off"></textarea>
            @if (!empty($label))
            <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
            @endif
            @elseif(isset($type) && $type === 'document')
            <input wire:model.defer="{{ $model }}" id="{{ $id }}" type="file" class="form-control  @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif accept=".pdf, .doc, .docx" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />
            @if (!empty($label))
            <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
            @endif
            @elseif(isset($type) && $type === 'barcode')
            <input wire:model="{{ $model }}" id="{{ $id }}" type="{{ isset($type) ? $type : 'text' }}" class="form-control  @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="{{ isset($type) && $type === 'password' ? 'new-password' : 'off' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" autocomplete="off" />
            @if (!empty($label))
            <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
            @endif
            <script>
                document.addEventListener('livewire:load', function() {
                    var barcodeInput = document.getElementById('{{ $model }}');
                    if (barcodeInput) {
                        window.addEventListener('barcode-processed', function() {
                            barcodeInput.value = '';
                            barcodeInput.focus();
                        });

                        barcodeInput.addEventListener('keydown', function(event) {
                            if (event.key === 'Enter') {
                                event.preventDefault();
                                if (barcodeInput.value !== "") {
                                    console.log(barcodeInput.value);
                                    Livewire.emit('scanBarcode', barcodeInput.value);
                                }
                            }
                        });
                    }
                });

            </script>
            @elseif(isset($type) && $type === 'code')
            <input wire:model.defer="{{ $model }}" type="text" class="form-control  @error($model) is-invalid @enderror" @if((isset($action) && ($action=='Edit' || $action=='View' )) || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="{{ isset($type) && $type === 'password' ? 'new-password' : 'off' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" autocomplete="off" />
            @if (!empty($label))
            <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
            @endif
            @elseif(isset($type) && $type === 'date')
            <input wire:model.defer="{{ $model }}" id="{{ $id }}" type="text" class=" form-control  @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif wire:change="{{ isset($onChanged) ? $onChanged : '' }}" readonly="readonly" />
            @if (!empty($label))
            <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
            @endif
            <script>
                myJQuery(document).ready(function() {
                    myJQuery("[id='{{ $id }}']").datepicker({
                        dateFormat: 'dd-mm-yy', // Set the date format to 'dd mm yy'
                        changeMonth: true
                        , changeYear: true
                        , showButtonPanel: true
                    }).on("change", function() {
                        @this.set('{{ $model }}', myJQuery(this).val());
                    });
                });

            </script>
            @elseif(isset($type) && $type === 'number')
            <input wire:model.defer="{{ $model }}" id="{{ $id }}" type="text" class="form-control  @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="{{ isset($type) && $type === 'password' ? 'new-password' : 'off' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />
            @if (!empty($label))
            <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
            @endif
            <script>
                $(document).ready(function() {
                    var modelId = "{{ $id }}";
                    var $input = $('#' + modelId);

                    Inputmask({
                        alias: "numeric"
                        , groupSeparator: "."
                        , radixPoint: ","
                        , autoGroup: true
                        , digitsOptional: true
                        , rightAlign: false
                        , clearIncomplete: true
                        , allowMinus: false
                        , placeholder: "0"
                    }).mask($input[0]);

                    $input.on('blur', function() {
                        if ($(this).val().trim() === '') {
                            $(this).val('0');
                        }
                    });
                });

            </script>
            @elseif(isset($type) && $type === 'image')
            <input wire:model.defer="{{ $model }}" id="{{ $id }}" type="file" class="form-control  @error($model) is-invalid @enderror" accept="image/*" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />
            @if (!empty($label))
            <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
            @endif
            @else
            <input wire:model.defer="{{ $model }}" type="{{ isset($type) ? $type : 'text' }}" class="form-control  @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="{{ isset($type) && $type === 'password' ? 'new-password' : 'off' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />
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
            <button type="button" class="btn btn-secondary" wire:click="{{ $clickEvent }}" data-toggle="tooltip" title="Search for product" @if ((!empty($action) && $action==='View' ) || (isset($enabled) && $enabled==='false' )) disabled @endif>
                <i class="bi bi-search" style="font-size: 1.5rem;"></i>
            </button>
        </div>
        @endif
    </div>
</div>

