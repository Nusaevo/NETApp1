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
                minimumResultsForSearch: Infinity, // Menonaktifkan fitur pencarian
                dropdownCssClass: 'hide-search-box' // Menambahkan kelas CSS untuk menyembunyikan input pencarian
            });

            $('#applicationSelect').on('change', function(e) {
                var selectedValue = $(this).val();
                Livewire.emit('configApplicationChanged', selectedValue);
            });
        }

        initializeSelect2();

        Livewire.hook('message.processed', (message, component) => {
            initializeSelect2();
        });
    });
</script>

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
