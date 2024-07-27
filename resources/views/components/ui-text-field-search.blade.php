<div class="mb-5 col-sm" @if(isset($span)) span="{{ $span }}" @endif>
    @php
    $id = str_replace(['.', '[', ']'], '_', $model);
    @endphp
    <div class="form-floating">
        <select id="{{ $id }}" class="form-select @error($model) is-invalid @enderror @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled-gray @endif" wire:model.defer="{{ $model }}" data-toggle="tooltip" title="Select an option" @if ((!empty($action) && $action==='View' ) || (isset($enabled) && $enabled==='false' )) disabled @endif>
            <option value=""></option>
            @if (!is_null($options))
            @foreach ($options as $option)
            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
            @endif
        </select>
        @if (!empty($label))
        <label for="{{ $id }}" class="@if(isset($required) && $required === 'true') required @endif">{{ $label }}</label>
        @endif
        @error($model)
        <div class="error-message">{{ $message }}</div>
        @enderror
    </div>

    <script>
        function initSelect2() {
            var selectId = '{{ $id }}';
            var selectElement = myJQuery('#' + selectId);
            var initialValue = selectElement.val(); // Get the initial value directly
            var isEnabled = '{{ !empty($action) && $action === "View" || (isset($enabled) && $enabled === "false") }}';

            if (!isEnabled) {
                selectElement.select2();
                selectElement.on('change', function(e) {
                    var data = myJQuery(this).val();
                    @this.set('{{ $model }}', data);
                });

                // Set the initial value to ensure the selected option is displayed
                if (initialValue) {
                    selectElement.val(initialValue).trigger('change');
                }
            }
        }

        document.addEventListener('livewire:init', function () {
            initSelect2();

            // Livewire.hook('morph.updated', function (el, component) {
            //     if (el.querySelector('#{{ $id }}')) {
            //         initSelect2();
            //     }
            // });
        });

        // myJQuery(document).ready(function() {
        //     initSelect2();
        // });
    </script>
</div>
