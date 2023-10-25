<div class="mb-10" @if(!$visible) style="display: none;" @endif>
    <label class="{{ $labelClass }} @if($required) required @endif">{{ $label }}</label>
    @if($type === 'textarea')
    <textarea wire:model.defer="{{ $model }}" class="form-control @error($model) is-invalid @enderror" {{ !$enabled ? 'disabled' : '' }} @if($required) required @endif placeholder="{{ $placeHolder }}"></textarea>
    @else
    <input wire:model.defer="{{ $model }}" type="{{ $type }}" class="form-control @error($model) is-invalid @enderror" {{ !$enabled ? 'disabled' : '' }} @if($required) required @endif placeholder="{{ $placeHolder }}" />
    @endif
    @error($model) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
