<div class="mb-3 responsive-field" @if(isset($span)) span="{{ $span }}"@endif @if(isset($visible) && $visible === 'false') style="display: none;" @endif>
    @if (!empty($label))
        <div class="responsive-label">
            <label class="{{ isset($required) && $required === 'true' ? 'required' : '' }}">{{ $label }} :</label>
        </div>
    @endif

    <div class="responsive-input-container">
        <div class="form-options" style="flex: 1; display: flex;">
            @isset($options)
                @foreach ($options as $key => $optionLabel)
                <div class="form-option" style="display: flex; align-items: center; margin-left: 10px;">
                    <input id="{{ $id }}" type="checkbox" wire:model="{{ isset($model) ? $model.'.' : '' }}{{ $key }}"
                           id="option{{ $key }}"
                           style="width: 20px; height: 20px; margin-right: 5px;"
                           @if (isset($enabled) && !$enabled) disabled @endif />
                    <label for="option{{ $key }}">{{ $optionLabel }}</label>
                </div>

                @endforeach
            @endisset
        </div>
        @error($model)
            <div class="error-message">{{ $message }}</div>
        @enderror
    </div>
</div>
