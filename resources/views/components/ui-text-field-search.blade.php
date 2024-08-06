@php
$id = str_replace(['.', '[', ']'], '_', $model);
@endphp
<div class="mb-5 col-sm" @if(isset($span)) span="{{ $span }}" @endif>
    <div class="form-floating">
        <select id="{{ $id }}" class="form-select responsive-input @error($model) is-invalid @enderror @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled-gray @endif" wire:model="{{ $model }}" data-toggle="tooltip" title="Select an option" @if ((!empty($action) && $action==='View' ) || (isset($enabled) && $enabled==='false' )) disabled @endif wire:loading.attr="disabled">
            <option value=""></option>
            @if (!is_null($options))
            @foreach ($options as $option)
            <option value="{{ $option['value'] }}" {{ $selectedValue == $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
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
            var isEnabled = '{{ isset($action) && $action === '
            View ' || (isset($enabled) && $enabled === '
            false ') }}';

            if (!isEnabled) {
                selectElement.select2();
                selectElement.on('change', function(e) {
                    var data = $(this).val();
                    var onChanged = '{{ isset($onChanged) ? $onChanged : '
                    ' }}';
                    @this.set('{{ $model }}', data);

                    if (onChanged !== '') {
                        Livewire.dispatch(onChanged);
                        console.log('Event dispatched:', onChanged); // Log the event dispatch action
                    }
                });
            }
        }

        initializeSelect2();
        Livewire.hook('morph.updated', function() {
            initializeSelect2();
        });
    });

</script>

