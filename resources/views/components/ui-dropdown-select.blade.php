<div wire:ignore.self class="mb-3 responsive-field"
>
    <!-- Label -->
    @isset($label)
        @if (!empty($label))
            <div class="responsive-label">
                <label class="@if(isset($required) && $required === 'true') required @endif">{{ $label }} :</label>
            </div>
        @endif
    @endisset

    <div class="text-field-container">
        <div class="responsive-input-container">
            <select name="{{ isset($model) ? $model : '' }}" wire:model="{{ isset($model) ? $model : '' }}" @if (isset($onChanged) && $onChanged) wire:change="{{ $onChanged }}" @endif
                class="form-select @error($model) is-invalid @enderror @if (isset($enabled) && $enabled === 'false') disabled-gray @endif"
                @if (isset($action) && $action === 'View' || (isset($enabled) && $enabled === 'false')) disabled @endif
                @if (isset($required) && $required === 'true') required @endif>
                @if (!is_null($options))
                    @isset($selectedValue)
                        @foreach ($options as $option)
                            <option value="{{ $option['value'] }}" @if (isset($selectedValue) && $selectedValue == $option['value']) selected @endif>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    @endisset
                @endif
            </select>
            @error($model)
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Refresh Button -->
        @if (isset($clickEvent) && $clickEvent !== '')
            @if ((!empty($action) && $action !== 'View') || (isset($enabled) && $enabled !== 'false'))
                <button type="button" wire:click="{{ $clickEvent }}" class="btn btn-secondary btn-sm"
                    data-toggle="tooltip" title="Refresh your search to get the latest data"
                    @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif>
                    <span wire:loading.remove>
                        <i class="bi bi-arrow-repeat"></i> <!-- Bootstrap refresh icon -->
                    </span>
                    <span wire:loading>
                        <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
                    </span>
                </button>
            @endif
        @endisset
    </div>
</div>
