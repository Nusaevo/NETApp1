<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" {!! theme()->printHtmlAttributes('html') !!} {{ theme()->printHtmlClasses('html') }}>
{{-- begin::Head --}}
<head>
    <meta charset="utf-8" />
    <title>NusaEvo System</title>
    <meta name="description" content="{{ ucfirst(theme()->getOption('meta', 'description')) }}" />
    <meta name="keywords" content="{{ theme()->getOption('meta', 'keywords') }}" />
    <link rel="canonical" href="{{ ucfirst(theme()->getOption('meta', 'canonical')) }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="shortcut icon" href="{{ asset(theme()->getDemo() . '/' .theme()->getOption('assets', 'favicon')) }}" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/pagebase.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.jqueryui.min.js"></script>

    <script src="https://cdn.datatables.net/scroller/2.3.0/js/dataTables.scroller.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>


    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.0.0/css/buttons.dataTables.min.css">
    <script src="https://cdn.datatables.net/buttons/2.0.0/js/dataTables.buttons.min.js"></script>
    <!-- JSZip (for PDF/Excel export) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <!-- PDFMake (for PDF export) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <!-- Buttons extension HTML5 export -->
    <script src="https://cdn.datatables.net/buttons/2.0.0/js/buttons.html5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.25/webcam.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/numeral.js/2.0.6/numeral.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.8/inputmask.min.js"></script>
    <script>
        var myJQuery = jQuery;

    </script>

    {{-- begin::Fonts --}}
    {{ theme()->includeFonts() }}
    {{-- end::Fonts --}}

    @if (theme()->hasOption('page', 'assets/vendors/css'))
    {{-- begin::Page Vendor Stylesheets(used by this page) --}}
    @foreach (array_unique(theme()->getOption('page', 'assets/vendors/css')) as $file)
    {!! preloadCss(assetCustom($file)) !!}
    @endforeach
    {{-- end::Page Vendor Stylesheets --}}
    @endif

    @if (theme()->hasOption('page', 'assets/custom/css'))
    {{-- begin::Page Custom Stylesheets(used by this page) --}}
    @foreach (array_unique(theme()->getOption('page', 'assets/custom/css')) as $file)
    {!! preloadCss(assetCustom($file)) !!}
    @endforeach
    {{-- end::Page Custom Stylesheets --}}
    @endif

    @if (theme()->hasOption('assets', 'css'))
    {{-- begin::Global Stylesheets Bundle(used by all pages) --}}
    @foreach (array_unique(theme()->getOption('assets', 'css')) as $file)
    @if (strpos($file, 'plugins') !== false)
    {!! preloadCss(assetCustom($file)) !!}
    @else
    <link href="{{ assetCustom($file) }}" rel="stylesheet" type="text/css" />
    @endif
    @endforeach
    {{-- end::Global Stylesheets Bundle --}}
    @endif

    @if (theme()->getViewMode() === 'preview')
    {{ theme()->getView('partials/trackers/_ga-general') }}
    {{ theme()->getView('partials/trackers/_ga-tag-manager-for-head') }}
    @endif

    @yield('data-table-requirements')

    @yield('styles')
    @livewireStyles
</head>
{{-- end::Head --}}

{{-- begin::Body --}}
<body {!! theme()->printHtmlAttributes('body') !!} {!! theme()->printHtmlClasses('body') !!} {!! theme()->printCssVariables('body') !!}>

    {{-- @if (theme()->getOption('layout', 'loader/display') === true) --}}
    {{-- <div class="page-loader">
        <span class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </span>
    </div> --}}
    {{-- @endif --}}

    @yield('content')

    {{-- begin::Javascript --}}
    @if (theme()->hasOption('assets', 'js'))
    {{-- begin::Global Javascript Bundle(used by all pages) --}}
    @foreach (array_unique(theme()->getOption('assets', 'js')) as $file)
    <script src="{{ asset(theme()->getDemo() . '/' .$file) }}"></script>
    @endforeach
    {{-- end::Global Javascript Bundle --}}
    @endif

    @if (theme()->hasOption('page', 'assets/vendors/js'))
    {{-- begin::Page Vendors Javascript(used by this page) --}}
    @foreach (array_unique(theme()->getOption('page', 'assets/vendors/js')) as $file)
    <script src="{{ asset(theme()->getDemo() . '/' .$file) }}"></script>
    @endforeach
    {{-- end::Page Vendors Javascript --}}
    @endif

    @if (theme()->hasOption('page', 'assets/custom/js'))
    {{-- begin::Page Custom Javascript(used by this page) --}}
    @foreach (array_unique(theme()->getOption('page', 'assets/custom/js')) as $file)
    <script src="{{ asset(theme()->getDemo() . '/' .$file) }}"></script>
    @endforeach
    {{-- end::Page Custom Javascript --}}
    @endif
    {{-- end::Javascript --}}

    @if (theme()->getViewMode() === 'preview')
    {{ theme()->getView('partials/trackers/_ga-tag-manager-for-body') }}
    @endif

    @yield('scripts')
    @livewireScripts

    <div id="loader-container">
        <div class="page-loader">
            <span class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </span>
        </div>
    </div>

</body>
</html>
<script>

    document.addEventListener('shown.bs.modal', function (event) {
        var zIndex = 1050 + (10 * document.querySelectorAll('.modal.show').length);
        var modalBackdrop = document.createElement('div');
        modalBackdrop.className = 'modal-backdrop';
        modalBackdrop.style.zIndex = zIndex - 1;
        modalBackdrop.style.opacity = 0.5;
        document.body.appendChild(modalBackdrop);

        // Atur zIndex modal saat ini
        event.target.style.zIndex = zIndex;
    });

    document.addEventListener('hidden.bs.modal', function (event) {
        var backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length) {
            document.body.removeChild(backdrops[backdrops.length - 1]);
        }

        // Periksa jika tidak ada modal yang sedang terbuka, hapus semua backdrop yang mungkin tertinggal
        if (document.querySelectorAll('.modal.show').length === 0) {
            document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
                backdrop.remove();
            });
            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
        }
    });

    myJQuery(document).ready(function() {
        // Function to set autocomplete off for all text input fields
        function setAutocompleteOff() {
            myJQuery('input[type="text"], input[type="search"], input[type="email"], input[type="password"], input[type="tel"], input[type="url"], input[type="number"], input[type="date"]').attr('autocomplete', 'off');
        }

        // Set autocomplete off for all existing text input fields when the document is ready
        setAutocompleteOff();

        // Monitor the DOM for changes and set autocomplete off for new input fields
        const observer = new MutationObserver(function() {
            setAutocompleteOff();
        });

        observer.observe(document.body, { childList: true, subtree: true });
    });
</script>

