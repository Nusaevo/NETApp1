@php
$id = str_replace(['.', '[', ']'], '_', $model);
@endphp
<div class="mb-5 col-sm" @if(isset($span)) span="{{ $span }}"@endif>
    <!-- Select Element -->
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

        <!-- Refresh Button -->
        {{-- @if (isset($clickEvent) && $clickEvent !== '')
            <button type="button" wire:click="{{ $clickEvent }}" class="btn btn-secondary btn-sm"
                    data-toggle="tooltip" title="Refresh your search to get the latest data"
                    @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled @endif>
                <i class="bi bi-arrow-repeat"></i>
            </button>
        @endif --}}
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
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
