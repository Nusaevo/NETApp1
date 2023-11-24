<div class="mb-3 responsive-field"
>
    <!-- Label -->
    @isset($label)
        @if (!empty($label))
            <div class="responsive-label">
                <label class="@if(isset($required) && $required === 'true') required @endif">{{ $label }} :</label>
            </div>
        @endif
    @endisset

    <!-- Select Element -->
    <div class="text-field-container">
        <div class="responsive-input-container">
            <select id="{{ isset($name) ? $name : 'defaultSelect' }}"
                    class="form-select responsive-input @error($model) is-invalid @enderror @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled-gray @endif"
                    wire:model="{{ $model }}" data-toggle="tooltip" title="Select an option"
                    @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif>
                @if (!is_null($options))
                    @foreach ($options as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                @endif
            </select>

            @error($model)
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <!-- Refresh Button -->
        @if (isset($clickEvent) && $clickEvent !== '')
            <button id="refreshButton" type="button" wire:click="{{ $clickEvent }}" class="btn btn-secondary btn-sm"
                    data-toggle="tooltip" title="Refresh your search to get the latest data"
                    @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif>
                <i class="bi bi-arrow-repeat"></i>
            </button>
        @endif
    </div>
</div>

<script>
    document.addEventListener('livewire:load', function() {
        var initializeSelect2 = function() {
            var selectId = '{{ isset($name) ? $name : 'defaultSelect' }}';
            var isEnabled = '{{ isset($action) && $action === 'View' || (isset($enabled) && $enabled === 'false') }}';

            if (!isEnabled) {
                $('#' + selectId).select2();
                $('#' + selectId).on('change', function(e) {
                    var data = $(this).select2("val");
                    @this.set('{{ $model }}', data);
                });
            }
        };

        initializeSelect2();

        Livewire.hook('message.processed', (message, component) => {
            initializeSelect2();
        });
    });
</script>
