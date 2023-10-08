<div class="mb-10">
    <label class="{{ $labelClass }} @if($required) required @endif" >{{ $label }}</label>
    <input wire:model.defer="{{ $model }}" type="{{ $type }}" class="form-control @error($model) is-invalid @enderror" {{ $disabled ? 'disabled' : '' }} @if($required) required @endif />
    @error($model) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
