<div wire:ignore.self
    @if ((!empty($action) && $action !== 'View') || (isset($enabled) && $enabled !== 'false'))
        class="mb-3 responsive-field full-width"
    @else
        class="mb-3" style="display: none;"
    @endisset
>
    <!-- Label -->
    @isset($label)
        @if (!empty($label))
            <div class="responsive-label">
                <label class="@if(isset($required) && $required === 'true') required @endif">{{ $label }} :</label>
            </div>
        @endif
    @endisset

    <div class="responsive-input-container">
        <!-- Select Element -->
        <select id="{{ isset($name) ? $name : '' }}" wire:key="select-{{ isset($name) ? $name : '' }}"
            class="form-select responsive-input
            @if ((isset($action) && $action === 'View') || (isset($enabled) && $enabled === 'false') ||
            (isset($action) && $action !== 'View' && empty($enabled))) disabled @endif"
            wire:ignore data-toggle="tooltip" title="Select an option"
            @if (isset($enabled) && $enabled === 'false') disabled @endif>
            <option selected value="">-- Select option --</option>
            @if (!is_null($options))
                @isset($selectedValue)
                    @foreach ($options as $option)
                        <option value="{{ $option['value'] }}"
                        @if(isset($selectedValue) && $option['value'] == $selectedValue) selected @endif>
                            {{ $option['label'] }}
                        </option>
                    @endforeach
                @endisset
            @endif
        </select>

        <!-- Refresh Button -->
        @isset($clickEvent)
        @if ((!empty($action) && $action !== 'View') || (isset($enabled) && $enabled !== 'false'))
            <button type="button" wire:click="{{ $clickEvent }}" class="btn btn-secondary btn-sm"
            data-toggle="tooltip" title="Refresh your search to get the latest data">
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

    @isset($model)
        @error($model)
            <div class="responsive-error">
                <span class="error text-danger">{{ $message }}</span>
            </div>
        @enderror
    @endisset
</div>
<script>
    document.addEventListener('livewire:load', function () {
        initializeSelect2();

        Livewire.hook('message.processed', (message, component) => {
            initializeSelect2();
        });

        function initializeSelect2() {
            var selectElement = $('#{{ isset($name) ? $name : '' }}');
            var placeholder = '{{ isset($placeHolder) ? $placeHolder : '' }}';

            if ($.data(selectElement[0], 'select2')) {
                selectElement.select2('destroy');
            }

            @if ((!empty($action) && $action !== 'View') || (isset($enabled) && $enabled !== 'false'))
            selectElement.select2({
                placeholder: placeholder,
            }).on('change', function(e) {
                @if(isset($model))
                    @this.set('{{ $model }}', e.target.value);
                @endif
            });
            @endif

            @isset($clickEvent)
                selectElement.on('click', function() {
                    Livewire.emit('{{ $clickEvent }}');
                });
            @endisset
        }
    });
</script>
