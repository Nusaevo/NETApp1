<div class="mb-10" @if (!$visible) style="display: none;" @endif>
    <label>{{ $label }}</label>
    <select name="{{ $name }}" class="form-select @error($name) is-invalid @enderror" @if (!$enabled) disabled @endif @if ($required) required @endif>
        @foreach ($options as $option)
            <option value="{{ $option[$optionValueProperty] }}" @if ($selectedValue == $option[$optionValueProperty]) selected @endif>
                {{ $option[$optionLabelProperty] }}
            </option>
        @endforeach
    </select>
    @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
