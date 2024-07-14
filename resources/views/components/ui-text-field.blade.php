@php
$id = str_replace(['.', '[', ']'], '_', $model);
@endphp
<div class="mb-3 responsive-field" @if(isset($span)) span="{{ $span }}" @endif @if(isset($visible) && $visible==='false' ) style="display: none;" @endif>
    <div class="text-field-container">
        <div class="responsive-input-container form-floating">
            @if(isset($type) && $type === 'textarea')
                <textarea wire:model.defer="{{ $model }}" id="{{ $id }}" rows="{{ isset($rows) ? $rows : '10' }}" class="form-control responsive-textarea @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" autocomplete="off"></textarea>
                @if (!empty($label))
                <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
                @endif
            @elseif(isset($type) && $type === 'document')
                <input wire:model.defer="{{ $model }}" id="{{ $id }}" type="file" class="form-control responsive-input @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif accept=".pdf, .doc, .docx" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />
                @if (!empty($label))
                <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
                @endif
            @elseif(isset($type) && $type === 'barcode')
                <input wire:model="{{ $model }}" id="{{ $id }}" type="{{ isset($type) ? $type : 'text' }}" class="form-control responsive-input @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="{{ isset($type) && $type === 'password' ? 'new-password' : 'off' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" autocomplete="off"/>
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
            <div class="form-floating">
                <input wire:model.defer="{{ $model }}" type="text" class="form-control responsive-input @error($model) is-invalid @enderror" @if((isset($action) && ($action=='Edit' || $action=='View' )) || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="{{ isset($type) && $type === 'password' ? 'new-password' : 'off' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" autocomplete="off"/>
                @if (!empty($label))
                <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
                @endif
            </div>
            @elseif(isset($type) && $type === 'date')
            <div class="form-floating">
                <input wire:model.defer="{{ $model }}" id="{{ $id }}" type="text" class="inputDates form-control responsive-input @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif wire:change="{{ isset($onChanged) ? $onChanged : '' }}" readonly="readonly" />
                @if (!empty($label))
                <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
                @endif
            </div>
            <script>
                myJQuery(document).ready(function() {
                    myJQuery("[id='{{ $id }}']").datepicker({
                        dateFormat: 'dd-mm-yy', // Set the date format to 'dd mm yy'
                        changeMonth: true,
                        changeYear: true,
                        showButtonPanel: true
                    }).on("change", function() {
                        @this.set('{{ $model }}', myJQuery(this).val());
                    });
                });
            </script>
            @elseif(isset($type) && $type === 'number')
                <input wire:model.defer="{{ $model }}" id="{{ $id }}" type="text" class="inputNumbers form-control responsive-input @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif wire:change="{{ isset($onChanged) ? $onChanged : '' }}" autocomplete="off">
                @if (!empty($label))
                <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
                @endif
            <script>
                $(document).ready(function() {
                    var modelId = "{{ $id }}";
                    Inputmask({
                        alias: "numeric",
                        groupSeparator: ".",
                        radixPoint: ",",
                        autoGroup: true,
                        digitsOptional: true,
                        placeholder: '0',
                        rightAlign: false,
                        clearIncomplete: true,
                        allowMinus: false
                    }).mask('#' + modelId);
                });
            </script>
            @elseif(isset($type) && $type === 'image')
                <input wire:model.defer="{{ $model }}" id="{{ $id }}" type="file" class="form-control responsive-input @error($model) is-invalid @enderror" accept="image/*" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />
                @if (!empty($label))
                <label for="{{ $id }}" class="@if(isset($required) && $required==='true') required @endif">{{ $label }}</label>
                @endif
            @else
                <input wire:model.defer="{{ $model }}" type="{{ isset($type) ? $type : 'text' }}" class="form-control responsive-input @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="{{ isset($type) && $type === 'password' ? 'new-password' : 'off' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />
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
        <button type="button" class="btn btn-secondary" wire:click="{{ $clickEvent }}" data-toggle="tooltip" title="Search for product" @if ((!empty($action) && $action==='View' ) || (isset($enabled) && $enabled==='false' )) disabled @endif style="margin-left: 10px;">
            <i class="bi bi-search" style="font-size: 1.5rem;"></i>
        </button>
        @endif

    </div>
</div>
