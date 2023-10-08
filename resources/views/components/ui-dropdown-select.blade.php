<div class="mb-10">
    <label class="form-label @if($required) required @endif">{{ $label }}</label>
    <select name="{{ $name }}" class="form-select @error($name) is-invalid @enderror" wire:model.lazy="{{ $name }}" @if($required) required @endif>
        <option value="">--</option>
        @foreach($options as $option)
            <option value="{{ $option[$optionValueProperty] }}" {{ $selectedValue == $option[$optionValueProperty] ? 'selected' : '' }}>
                {{ $option[$optionLabelProperty] }}
            </option>
        @endforeach
    </select>
    @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
