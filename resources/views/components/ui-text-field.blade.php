<div class="mb-3 responsive-field" @if(isset($span)) span="{{ $span }}" @endif @if(isset($visible) && $visible==='false' ) style="display: none;" @endif>
    @if (!empty($label))
    <div class="responsive-label">
        <label class="{{ isset($required) && $required === 'true' ? 'required' : '' }}">{{ $label }} :</label>
    </div>
    @endif

    <div class="responsive-input-container">
        @if(isset($type) && $type === 'textarea')
        <textarea wire:model.defer="{{ $model }}" id="{{ $id }}" rows="{{ isset($rows) ? $rows : '10' }}" class="responsive-textarea form-control @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}"></textarea>
        @elseif(isset($type) && $type === 'document')
        <input wire:model.defer="{{ $model }}" id="{{ $id }}" type="file" class="responsive-input form-control @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif accept=".pdf, .doc, .docx" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />
        @elseif(isset($type) && $type === 'barcode')
        <input wire:model="{{ $model }}" id="{{ $id }}" type="{{ isset($type) ? $type : 'text' }}" class="responsive-input form-control @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="{{ isset($type) && $type === 'password' ? 'new-password' : 'off' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />
        @elseif(isset($type) && $type === 'code')
        <input wire:model.defer="{{ $model }}" type="text" class="responsive-input form-control @error($model) is-invalid @enderror" @if((isset($action) && ($action=='Edit' || $action=='View' )) || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="{{ isset($type) && $type === 'password' ? 'new-password' : 'off' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />
        @elseif(isset($type) && $type === 'barcode')
        <input wire:model="{{ $model }}" id="{{ $id }}" type="text" class="responsive-input form-control @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="off" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />

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

        @elseif(isset($type) && $type === 'date')
        <input wire:model.defer="{{ $model }}" id="{{ $id }}" type="text" class="inputDates responsive-input form-control @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required
        @endif wire:change="{{ isset($onChanged) ? $onChanged : '' }}" readonly="readonly" />

        <script>
            myJQuery(document).ready(function() {
                myJQuery("[id='{{ $model }}']").datepicker({
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
        <input wire:model.defer="{{ $model }}" id="{{ $id }}" type="text" class="inputNumbers responsive-input form-control @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif wire:change="{{ isset($onChanged) ? $onChanged : '' }}">

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

        @else
        <input wire:model.defer="{{ $model }}" type="{{ isset($type) ? $type : 'text' }}" class="responsive-input form-control @error($model) is-invalid @enderror" @if(isset($action) && $action=='View' || (!empty($enabled) && $enabled==='false' )) disabled @endif @if(isset($required) && $required==='true' ) required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="{{ isset($type) && $type === 'password' ? 'new-password' : 'off' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />

        @endif

        @error($model)
        <div class="error-message">{{ $message }}</div>
        @enderror
    </div>
</div>

