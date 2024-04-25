<div wire:ignore.self class="mb-3 responsive-field" @if(isset($span)) span="{{ $span }}" @endif>
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
            <select id="{{ $id }}" name="{{ isset($model) ? $model : '' }}" @if(isset($modelType) && $modelType==='lazy' ) wire:model.lazy="{{ isset($model) ? $model : '' }}" @else wire:model="{{ isset($model) ? $model : '' }}" @endif @if (isset($onChanged) && $onChanged) wire:change="{{ $onChanged }}" @endif class="form-control @error($model) is-invalid @enderror @if (isset($enabled) && $enabled === 'false') disabled-gray @endif" @if (isset($action) && $action==='View' || (isset($enabled) && $enabled==='false' )) disabled @endif @if (isset($required) && $required==='true' ) required @endif>
                <option value=""></option>
                @if (!is_null($options))
                    @forelse ($options as $option)
                        <option value="{{ $option['value'] }}" @if(isset($model) && $model === $option['value']) selected @endif>
                            {{ $option['label'] }}
                        </option>
                    @empty
                        <!-- No options available -->
                    @endforelse
                @endif
            </select>
            @error($model)
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <!-- Refresh Button -->
        @if (isset($clickEvent) && $clickEvent !== '')
            @if ((!empty($action) && $action !== 'View') || (isset($enabled) && $enabled !== 'false'))
                <button type="button" wire:click="{{ $clickEvent }}" wire:loading.attr="disabled" class="btn btn-secondary btn-sm" data-toggle="tooltip" title="Refresh your search to get the latest data" @if ((!empty($action) && $action==='View' ) || (isset($enabled) && $enabled==='false' )) disabled @endif>
                    <i class="bi bi-arrow-repeat"></i>
                </button>
            @endif
        @endisset
    </div>
</div>
