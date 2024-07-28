<div class="mb-5 col-sm" @if(isset($span)) span="{{ $span }}" @endif>
    @php
    $id = str_replace(['.', '[', ']'], '_', $model);
    @endphp
    <div class="form-floating">
        <select id="{{ $id }}" class="form-select responsive-input @error($model) is-invalid @enderror @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled-gray @endif" data-toggle="tooltip" title="Select an option" @if ((!empty($action) && $action==='View' ) || (isset($enabled) && $enabled==='false' )) disabled @endif>
            <option value=""></option>
            @if (!is_null($options))
            @foreach ($options as $option)
            <option value="{{ $option['value'] }}" {{ $selectedValue == $option['value'] ? 'selected' : '' }} >{{ $option['label'] }}</option>
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
        document.addEventListener('livewire:init', () => {
            function initializeSelect2() {
                var selectId = '{{ isset($id) ? $id : 'defaultSelect' }}';
                var isEnabled = '{{ isset($action) && $action === '
                View ' || (isset($enabled) && $enabled === '
                false ') }}';

                if (!isEnabled) {
                    $('#' + selectId).select2();
                    $('#' + selectId).on('change', function(e) {
                        var data = $(this).select2("val");
                        var id = $(this).attr('id');
                        @if(isset($model)  && $model !== '')
                            @this.set('{{ $model }}', data);
                        @endif
                        @if(isset($onChanged)  && $onChanged !== '')
                        Livewire.dispatch('{{ $onChanged }}');
                        @endif
                });
                }
            }

            initializeSelect2();
        });

    </script>


</div>
