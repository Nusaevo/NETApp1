<!-- UI Text Field Component -->
<div class="mb-3" @if(!$visible) style="display: none;" @endif style="display: inline-flex; flex-direction: column; width: {{ $span === 'Half' ? '50%' : '100%' }}; padding-right: 5px;">

    <div style="display: flex; align-items: center; width: 100%;">

        <!-- Label -->
        <div style="flex: 0 0 100px;">
            <label class="@if($required) required @endif">{{ $label }} :</label>
        </div>

        <!-- Use the wire:ignore directive to prevent Livewire from managing the select element -->
        <select id="{{ $name }}" class="form-control" style="width: 100%;" wire:ignore>
            <option value="">Select an option</option>
            @foreach ($options as $option)
                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
            @endforeach
        </select>

    </div>

    @error($model)
    <div style="display: flex; align-items: start;">
        <div style="flex: 0 0 100px;"></div>
        <span class="error text-danger">{{ $message }}</span>
    </div>
    @enderror

</div>
<script>
    $(document).ready(function() {
        $('#{{ $name }}').select2({
            placeholder: '{{ $placeHolder }}',
            dropdownPosition: 'above',
        });

        Livewire.on('refreshSelect', function () {
            $('#{{ $name }}').select2({
                placeholder: '{{ $placeHolder }}',
                dropdownPosition: 'above',
            });
        });

        $('#{{ $name }}').on('change', function(e) {
            @this.set('{{ $model }}', e.target.value);
            @this.emit('refreshSelect{{ $name }}', e.target.value);
        });
    });
</script>
