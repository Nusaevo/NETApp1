<div class="mb-3" @if(!$visible) style="display: none;" @endif>
    <div style="display: flex; align-items: center; flex-basis: {{ $span === 'Half' ? '50%' : '100%' }};">

        <!-- Label -->
        <div style="flex: 0 0 100px;">
            <label class="{{ $labelClass }} @if($required) required @endif">{{ $label }} : </label>
        </div>

        <!-- Input Field -->
        <div style="flex: 1;">
            @if($type === 'textarea')
            <textarea wire:model.defer="{{ $model }}" class="form-control @error($model) is-invalid @enderror" {{ !$enabled ? 'disabled' : '' }} @if($required) required @endif placeholder="{{ $placeHolder }}"></textarea>
            @else
            <input wire:model.defer="{{ $model }}" type="{{ $type }}" class="form-control @error($model) is-invalid @enderror" {{ !$enabled ? 'disabled' : '' }} @if($required) required @endif placeholder="{{ $placeHolder }}" />
            @endif
        </div>
    </div>

    <!-- Error Message -->
    <div style="display: flex; align-items: flex-start;">
        <span style="flex: 0 0 100px;"></span>
        @error($model)
            <span class="error text-danger">{{ $message }}</span>
        @enderror
    </div>
</div>
