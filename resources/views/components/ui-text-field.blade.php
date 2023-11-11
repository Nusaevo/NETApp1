<div class="mb-3 responsive-field {{ isset($span) && $span === 'Half' ? 'half-width' : 'full-width' }}" @if(isset($visible) && $visible === 'false') style="display: none;" @endif>

    <div class="responsive-label">
        <!-- Conditionally rendered label -->
        <label class="{{ isset($required) && $required === 'true' ? 'required' : '' }}">{{ $label }} :</label>
    </div>

    <!-- Conditionally rendered input or textarea -->
    @if(isset($type) && $type === 'textarea')
    <textarea wire:model.defer="{{ $model }}" rows="5" class="responsive-textarea form-control @error($model) is-invalid @enderror" @if(isset($action) && $action == 'View' || (!empty($enabled) && $enabled === 'false')) disabled @endif @if(isset($required) && $required === 'true') required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}"></textarea>
    @else
    <input wire:model.defer="{{ $model }}" type="{{ isset($type) ? $type : 'text' }}" class="responsive-input form-control @error($model) is-invalid @enderror" @if(isset($action) && $action == 'View' || (!empty($enabled) && $enabled === 'false')) disabled @endif @if(isset($required) && $required === 'true') required @endif placeholder="{{ isset($placeHolder) ? $placeHolder : '' }}" autocomplete="{{ isset($type) && $type === 'password' ? 'new-password' : 'off' }}" wire:change="{{ isset($onChanged) ? $onChanged : '' }}" />
    @endif

    <!-- Error message -->
    @error($model)
    <div class="responsive-error">
        <span class="error text-danger">{{ $message }}</span>
    </div>
    @enderror

</div>
