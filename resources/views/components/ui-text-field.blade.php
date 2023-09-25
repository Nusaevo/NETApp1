<div class="mb-10">
    <label class="{{ $labelClass }}">{{ $label }}</label>
    <input wire:model.defer="{{ $model }}" type="{{ $type }}" class="form-control @error($model) is-invalid @enderror" {{ $disabled ? 'disabled' : '' }}/>
    @error($model) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
