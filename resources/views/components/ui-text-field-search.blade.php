@php
$id = str_replace(['.', '[', ']'], '_', $model);
@endphp
<div class="mb-5 col-sm" @if(isset($span)) span="{{ $span }}"@endif>
    <div class="form-floating">
        <select id="{{ $id }}"
                class="form-select responsive-input @error($model) is-invalid @enderror @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled-gray @endif"
                wire:model="{{ $model }}" data-toggle="tooltip" title="Select an option"
                @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif>
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
</div>

<script>
document.addEventListener('livewire:init', () => {
    function initializeSelect2() {
        var selectId = '{{ $id }}';
        var selectElement = $('#' + selectId);
        var initialValue = selectElement.data('initial-value');
        var isEnabled = '{{ isset($action) && $action === 'View' || (isset($enabled) && $enabled === 'false') }}';

        if (!isEnabled) {
            selectElement.select2();
            selectElement.on('change', function(e) {
                var data = $(this).val();
                @this.set('{{ $model }}', data);
            });

            // Set the initial value to ensure the selected option is displayed
            selectElement.val(initialValue).trigger('change');
        }
    }

    initializeSelect2();

    Livewire.hook('morph.updated', (el, component) => {
        if (el.querySelector('#{{ $id }}')) {
            initializeSelect2();
        }
    });
});
</script>
