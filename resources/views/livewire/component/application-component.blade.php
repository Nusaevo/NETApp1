<div>
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

@push('scripts')
<script>

document.addEventListener('livewire:init', () => {
    function formatState(state) {
        if (!state.id) {
            return state.text;
        }
        var baseUrl = state.element.getAttribute('data-image');
        var $state = $(
            '<span><img src="' + baseUrl + '" class="img-flag" style="width: 20px; height: auto; margin-right: 10px;" /> ' + state.text + '</span>'
        );
        return $state;
    }

    function initializeSelect2() {
        $('#applicationSelect').select2({
            templateResult: formatState,
            templateSelection: formatState,
            width: '100%',
            dropdownAutoWidth: true,
            minimumResultsForSearch: Infinity, // Disable search feature
            dropdownCssClass: 'hide-search-box' // Add CSS class to hide search input
        });

        $('#applicationSelect').on('change', function(e) {
            var selectedValue = $(this).val();
            Livewire.dispatch('configApplicationChanged', { selectedApplication: selectedValue } );
        });
    }

    initializeSelect2();

    Livewire.hook('message.processed', (message, component) => {
        $('#applicationSelect').select2('destroy'); // Destroy existing Select2
        initializeSelect2(); // Reinitialize Select2
    });
});

</script>
@endpush
<style>
    .aside-menu img {
        height: 30px; /* Sesuaikan dengan kebutuhan Anda */
        width: auto;
        margin-right: 10px;
    }

    /* Gaya untuk elemen application-select */
    #applicationSelect + .select2-container .select2-selection--single {
        height: 50px; /* Sesuaikan dengan kebutuhan Anda */
        display: flex;
        align-items: center;
    }

    #applicationSelect + .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 28px; /* Menyesuaikan teks secara vertikal */
        padding-left: 40px; /* Menambahkan padding untuk gambar */
    }

    #applicationSelect + .select2-container .select2-selection--single .select2-selection__arrow {
        height: 50px; /* Menyamakan tinggi dengan container */
    }

    #applicationSelect + .select2-container .select2-selection--single .select2-selection__rendered img {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px; /* Sesuaikan dengan kebutuhan Anda */
        height: auto;
    }

    #applicationSelect + .select2-container .select2-results__option .img-flag {
        width: 30px; /* Sesuaikan dengan kebutuhan Anda */
        height: 30px;
        margin-right: 10px;
        vertical-align: middle;
    }

    /* Menyembunyikan input pencarian */
    #applicationSelect + .select2-container .select2-search--dropdown {
        display: none;
    }

    #applicationSelect + .select2-container .hide-search-box .select2-search--dropdown {
        display: none !important;
    }
</style>

</div>
