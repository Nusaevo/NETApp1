<div class="mb-3 responsive-field" @if(isset($span)) span="{{ $span }}"@endif
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
            <select id="{{ $id }}"
                    class="form-control responsive-input @error($model) is-invalid @enderror @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled-gray @endif"
                    wire:model="{{ $model }}" data-toggle="tooltip" title="Select an option"
                    @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif>
                    <option value=""></option>
                @if (!is_null($options))
                    @foreach ($options as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                @endif
            </select>

            @error($model)
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>

        <!-- Refresh Button -->
        @if (isset($clickEvent) && $clickEvent !== '')
            <button type="button" wire:click="{{ $clickEvent }}" class="btn btn-secondary btn-sm"
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
            var selectId = '{{ isset($id) ? $id : 'defaultSelect' }}';
            var isEnabled = '{{ isset($action) && $action === 'View' || (isset($enabled) && $enabled === 'false') }}';

            if (!isEnabled) {
                $('#' + selectId).select2();
                $('#' + selectId).on('change', function(e) {
                    var data = $(this).select2("val");
                    var id = $(this).attr('id');
                    @if(isset($model)  && $model !== '')
                        @this.set('{{ $model }}', data);
                    @endif
                    @if(isset($onChanged)  && $onChanged !== '')
                        @this.{{ $onChanged }}(id, data);
                    @endif
                });
            }
        };

        initializeSelect2();

        Livewire.hook('message.processed', (message, component) => {
            initializeSelect2();
        });
    });
</script>
