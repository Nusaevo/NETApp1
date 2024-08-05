@php
$id = str_replace(['.', '[', ']'], '_', $model);
@endphp
<div wire:ignore class="mb-5 col-sm" @if(isset($span)) span="{{ $span }}" @endif>
    <div class="form-floating">
        <select x-data="{
                initSelect2() {
                    let selectElement = this.$refs.selectField;
                    if (selectElement) {
                        console.log('Initializing select2 for:', selectElement.id);

                        // Initialize select2
                        $(selectElement).select2();

                        // Listen for change events and update Livewire model
                        $(selectElement).on('change', function(e) {
                            var data = $(this).val();
                            var onChanged = '{{ isset($onChanged) ? $onChanged : '' }}';
                            $wire.set('{{ $model }}', data);

                            if (onChanged !== '') {
                                Livewire.dispatch(onChanged);
                                console.log('Event dispatched:', onChanged); // Log the event dispatch action
                            }
                        });
                    }
                }
            }" x-init="initSelect2()" x-ref="selectField" id="{{ $id }}" class="form-select responsive-input @error($model) is-invalid @enderror @if ((!empty($action) && $action === 'View') || (isset($enabled) && $enabled === 'false')) disabled-gray @endif" wire:model="{{ $model }}" data-toggle="tooltip" title="Select an option" @if ((!empty($action) && $action==='View' ) || (isset($enabled) && $enabled==='false' )) disabled @endif>
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
{{--
@push('scripts')
<script>
    document.addEventListener('livewire:load', function() {
        // Ensure the Alpine.js component is initialized when Livewire loads
        Livewire.hook('element.init', (el, component) => {
            if (el.id === '{{ $id }}') {
                console.log('Livewire element.init hook for:', el.id);
                Alpine.store('initSelect2');
            }
        });

        Livewire.hook('morph.updated', (el, component) => {
            if (el.id === '{{ $id }}') {
                console.log('Livewire morph.updated hook for:', el.id);
                Alpine.store('initSelect2');
            }
        });
    });
</script>
@endpush --}}
