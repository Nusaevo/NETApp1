<div class="mb-3 responsive-field" @if(isset($span)) span="{{ $span }}"@endif @if(isset($visible) && $visible === 'false') style="display: none;" @endif>
    @if (!empty($label))
        <div class="responsive-label">
            <label class="{{ isset($required) && $required === 'true' ? 'required' : '' }}">{{ $label }} :</label>
        </div>
    @endif

    <div class="responsive-input-container">
        @if(isset($type) && $type === 'textarea')
            <textarea wire:model.defer="{{ $model }}" id="{{ $model }}" rows="5" class="responsive-textarea form-control @error($model) is-invalid @enderror" @if(isset($action) && $action == 'View' || (!empty($enabled) && $enabled === 'false')) disabled @endif @if(isset($required) && $required === 'true') required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}"></textarea>
        @elseif(isset($type) && $type === 'document')
            <input wire:model.defer="{{ $model }}" id="{{ $model }}" type="file" class="responsive-input form-control @error($model) is-invalid @enderror" @if(isset($action) && $action == 'View' || (!empty($enabled) && $enabled === 'false')) disabled @endif @if(isset($required) && $required === 'true') required @endif accept=".pdf, .doc, .docx" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />
        @elseif(isset($type) && $type === 'barcode')
            <input wire:model="{{ $model }}" id="{{ $model }}" type="{{ isset($type) ? $type : 'text' }}" class="responsive-input form-control @error($model) is-invalid @enderror" @if(isset($action) && $action == 'View' || (!empty($enabled) && $enabled === 'false')) disabled @endif @if(isset($required) && $required === 'true') required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="{{ isset($type) && $type === 'password' ? 'new-password' : 'off' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />
        @elseif(isset($type) && $type === 'code')
            <input wire:model.defer="{{ $model }}" type="text" class="responsive-input form-control @error($model) is-invalid @enderror" @if((isset($action) && ($action == 'Edit' || $action == 'View')) || (!empty($enabled) && $enabled === 'false')) disabled @endif @if(isset($required) && $required === 'true') required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="{{ isset($type) && $type === 'password' ? 'new-password' : 'off' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />
        @else
            <input wire:model.defer="{{ $model }}" type="{{ isset($type) ? $type : 'text' }}" class="responsive-input form-control @error($model) is-invalid @enderror" @if(isset($action) && $action == 'View' || (!empty($enabled) && $enabled === 'false')) disabled @endif @if(isset($required) && $required === 'true') required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="{{ isset($type) && $type === 'password' ? 'new-password' : 'off' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />
        @endif

        @error($model)
            <div class="error-message">{{ $message }}</div>
        @enderror
    </div>
</div>

<script>
document.addEventListener('livewire:load', function() {
    var barcodeInput = document.getElementById('barcode');
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
});

</script>
