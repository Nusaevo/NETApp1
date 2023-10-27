<div class="mb-10" @if (!$visible) style="display: none;" @endif>
    <label class="@if($required) required @endif">{{ $label }}</label>
    <select name="{{ $name }}" wire:model="{{ $name }}" @if ($onChanged) wire:change="{{ $onChanged }}" @endif class="form-select @error($name) is-invalid @enderror" @if (!$enabled) disabled @endif @if ($required) required @endif>
        @if (!is_null($options))
            @foreach ($options as $option)
                <option value="{{ $option['value'] }}" @if ($selectedValue == $option['value']) selected @endif>
                    {{ $option['label'] }}
                </option>
            @endforeach
        @endif
    </select>
    @error($name) <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
