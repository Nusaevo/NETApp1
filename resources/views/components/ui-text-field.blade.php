<!-- UI Text Field Component -->
<div class="mb-3" @if(!$visible) style="display: none;" @endif style="display: inline-flex; flex-direction: column; width: {{ $span === 'Half' ? '50%' : '100%' }}; padding-right: 5px;">

    <div style="display: flex; align-items: center; width: 100%;">

        <!-- Label -->
        <div style="flex: 0 0 100px;">
            <label class="{{ $labelClass }} @if($required) required @endif">{{ $label }} :</label>
        </div>

        @if($type === 'textarea')
        <textarea wire:model.defer="{{ $model }}" rows="5" class="form-control @error($model) is-invalid @enderror" {{ !$enabled ? 'disabled' : '' }} @if($required) required @endif placeholder="{{ $placeHolder }}"></textarea>
        @else
        <input wire:model.defer="{{ $model }}" type="{{ $type }}" class="form-control @error($model) is-invalid @enderror" {{ !$enabled ? 'disabled' : '' }} @if($required) required @endif placeholder="{{ $placeHolder }}" />
        @endif
    </div>

    @error($model)
    <div style="display: flex; align-items: start;">
        <div style="flex: 0 0 100px;"></div>
        <span class="error text-danger">{{ $message }}</span>
    </div>
    @enderror

</div>
