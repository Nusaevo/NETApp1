<div class="mb-3 responsive-field" @if(isset($span)) span="{{ $span }}"@endif @if(isset($visible) && $visible === 'false') style="display: none;" @endif>
    @if (!empty($label))
        <label for="customInput" class="form-label {{ isset($required) && $required === 'true' ? 'required' : '' }}">
            {{ $label }} :
        </label>
    @endif

    <div class="responsive-input-container">
        @if(isset($type) && $type === 'textarea')
            <textarea id="customInput" wire:model.defer="{{ $model }}" rows="5" class="form-control @error($model) is-invalid @enderror" @if(isset($action) && $action == 'View' || (!empty($enabled) && $enabled === 'false')) disabled @endif @if(isset($required) && $required === 'true') required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}"></textarea>
            @error($model)
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        @else
            <input id="customInput" wire:model.defer="{{ $model }}" type="{{ isset($type) ? $type : 'text' }}" class="form-control @error($model) is-invalid @enderror" @if(isset($action) && $action == 'View' || (!empty($enabled) && $enabled === 'false')) disabled @endif @if(isset($required) && $required === 'true') required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="{{ isset($type) && $type === 'password' ? 'new-password' : 'off' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />
            @error($model)
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        @endif
    </div>
</div>
