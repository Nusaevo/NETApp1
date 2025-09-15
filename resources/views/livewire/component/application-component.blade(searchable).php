<div class="aside-menu flex-column-fluid" style="width: 100%;">
    <select id="applicationSelect" class="custom-select application-select" style="color: grey; width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #f8f9fa;" wire:loading.attr="disabled" wire:loading.class="select2-loading">
        @foreach($applications as $application)
            @php
                $imagePath = 'customs/logos/' . $application['value'] . '.png';
            @endphp
            <option value="{{ $application['value'] }}" @if($selectedApplication == $application['value']) selected @endif data-image="{{ asset($imagePath) }}">{{ $application['label'] }}</option>
        @endforeach
    </select>
</div>

<!-- Loading Indicator -->
<span wire:loading.remove></span>
<span wire:loading>
    <span class="spinner-border spinner-border-sm align-middle" role="status" aria-hidden="true"></span>
</span>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        function formatState(state) {
            if (!state.id) {
                return state.text;
            }
            var baseUrl = state.element.getAttribute('data-image');
            var $state = $(
                '<span><img src="' + baseUrl + '" class="img-flag" /> ' + state.text + '</span>'
            );
            return $state;
        }

        function initializeSelect2() {
            $('.application-select').select2({
                templateResult: formatState,
                templateSelection: formatState,
                width: '100%',
                dropdownAutoWidth: true
            });

            $('.application-select').on('change', function(e) {
                var selectedValue = $(this).val();
                Livewire.dispatch('configApplicationChanged', selectedValue);
            });
        }

        initializeSelect2();

        Livewire.hook('message.processed', (message, component) => {
            initializeSelect2();
        });
    });
</script>
