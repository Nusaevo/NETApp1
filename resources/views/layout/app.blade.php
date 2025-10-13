<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="facebook-domain-verification" content="nrk61mmi9umrp43dddp99yron03921" />
    <title>@yield('title', isset($menuName) && !empty($menuName) ? strip_tags($menuName) : '')</title>

    <!-- Dynamic Favicon from SysConfig1 Application Logo -->
    @php
        $appCode = Session::get('app_code', 'default');
        $faviconPath = 'customs/logos/SysConfig1.png';
        // Fallback ke favicon default jika logo aplikasi tidak ada
        if (!file_exists(public_path($faviconPath))) {
            $faviconPath = 'favicon.ico';
        }
    @endphp
    <link rel="shortcut icon" href="{{ asset($faviconPath) }}" type="image/png">
    <link rel="icon" href="{{ asset($faviconPath) }}" type="image/png">

    <!-- Bootstrap CSS via CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Core Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <!-- External Libraries -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.0/dist/barcodes/JsBarcode.code128.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.flash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

    <!-- Select2 Latest Version (Fixed compatibility issues) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.8/inputmask.min.js"></script>

    <!-- ApexCharts for charting -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script>

    <!-- Clean Application Styles -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/app.css') }}">

    <!-- Enhanced DataTable Styles -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/datatable-enhanced.css') }}">

    <!-- Custom Mobile Profile Styles -->
    <style>
        .bg-light-hover:hover {
            background-color: #f8f9fa !important;
            transform: translateX(2px);
            transition: all 0.2s ease-in-out;
        }

        .offcanvas-body {
            display: flex;
            flex-direction: column;
        }

        .list-group-item {
            border: none !important;
            transition: all 0.2s ease-in-out;
        }

        .list-group-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .rounded-circle {
            transition: all 0.2s ease-in-out;
        }

        .list-group-item:hover .rounded-circle {
            transform: scale(1.05);
        }

        /* Profile gradient animation */
        .bg-primary.bg-gradient {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            position: relative;
            overflow: hidden;
        }

        .bg-primary.bg-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shimmer 3s infinite;
        }

        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        /* Badge styling */
        .badge.bg-light {
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        /* Online indicator animation */
        .position-absolute .bg-success {
            animation: pulse-online 2s infinite;
        }

        @keyframes pulse-online {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }

        /* Hide loading spinner when printing */
        @media print {
            .nextjs-loading-spinner {
                display: none !important;
                visibility: hidden !important;
            }
        }
    </style>

    @livewireStyles

</head>
<body>
    <div class="d-flex min-vh-100" id="app_root" style="padding-top: 0; margin-top: 0;">`
        <!-- Sidebar (offcanvas on small screens, fixed on large) -->
        <div class="bg-white border-end shadow-sm position-fixed top-0 start-0 h-100 d-none d-lg-block" style="width: 280px; z-index: 1000;" id="sidebarFixed">
            <!-- Application Selector at top -->
            <div class="p-3 border-bottom" style="position: absolute; top: 0; left: 0; right: 0; z-index: 10;">
                @livewire('component.application-component')
            </div>

            <!-- Menu content - scrollable area -->
            <div class="p-3 overflow-auto" style="position: absolute; top: 80px; left: 0; right: 0; bottom: 120px;">
                @livewire('component.sidebar-menu')
            </div>

            <!-- Sidebar Footer with Nusavo Branding - Always at bottom -->
            <div class="p-3 border-top" style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(135deg, #f8f9fa, #e9ecef);">
                <div class="text-center">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        @php
                            $logoPath = 'customs/logos/SysConfig1.png';
                            $hasLogo = file_exists(public_path($logoPath));
                        @endphp
                        @if($hasLogo)
                            <img src="{{ asset($logoPath) }}" alt="Logo" class="me-2" style="width: 20px; height: 20px; object-fit: contain;">
                        @else
                            <i class="bi bi-shield-check text-primary me-2" style="font-size: 1.2rem;"></i>
                        @endif
                        <span class="fw-bold text-primary" style="font-size: 1.1rem; letter-spacing: 0.5px;">NusaEvo</span>
                    </div>
                    <small class="text-muted" style="font-size: 0.75rem;">
                        Powered by Advanced Technology
                    </small>
                </div>
            </div>
        </div>

        <!-- Offcanvas Sidebar for mobile (left) -->
        <div class="offcanvas offcanvas-start d-lg-none"  id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
            <div class="offcanvas-header border-bottom p-3">
                <div class="w-100 me-3">
                    <!-- Mobile Application Component -->
                    @livewire('component.application-component')
                </div>
                <button type="button" class="btn-close flex-shrink-0" data-bs-dismiss="offcanvas" aria-label="Close" style="position: relative; z-index: 10;"></button>
            </div>
            <div class="offcanvas-body p-0" style="position: relative; height: 100%;">
                <!-- Menu content - scrollable area -->
                <div class="overflow-auto p-3" style="position: absolute; top: 0; left: 0; right: 0; bottom: 120px;">
                    @livewire('component.sidebar-menu')
                </div>

                <!-- Mobile Sidebar Footer with Nusavo Branding - Always at bottom -->
                <div class="p-3 border-top" style="position: absolute; bottom: 0; left: 0; right: 0; background: linear-gradient(135deg, #f8f9fa, #e9ecef);">
                    <div class="text-center">
                        <div class="d-flex align-items-center justify-content-center mb-2">
                            @php
                                $logoPath = 'customs/logos/SysConfig1.png';
                                $hasLogo = file_exists(public_path($logoPath));
                            @endphp
                            @if($hasLogo)
                                <img src="{{ asset($logoPath) }}" alt="Logo" class="me-2" style="width: 20px; height: 20px; object-fit: contain;">
                            @else
                                <i class="bi bi-shield-check text-primary me-2" style="font-size: 1.2rem;"></i>
                            @endif
                            <span class="fw-bold text-primary" style="font-size: 1.1rem; letter-spacing: 0.5px;">NusaEvo</span>
                        </div>
                        <small class="text-muted" style="font-size: 0.75rem;">
                            Powered by Advanced Technology
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Offcanvas Profile for mobile (right) -->
        <div class="offcanvas offcanvas-end d-lg-none"  id="mobileProfile" aria-labelledby="mobileProfileLabel">
            <div class="offcanvas-header border-bottom bg-light">
                <h5 class="offcanvas-title fw-bold text-primary" id="mobileProfileLabel">
                    <i class="bi bi-person-circle me-2"></i>Account
                </h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body p-0">
                <!-- User Profile Section -->
                <div class="bg-primary bg-gradient text-white p-4 text-center">
                    <div class="mb-3">
                        <div class="position-relative d-inline-block">
                            <i class="bi bi-person-circle" style="font-size: 4.5rem; opacity: 0.9;"></i>
                            <div class="position-absolute bottom-0 end-0 bg-success rounded-circle p-1" style="width: 20px; height: 20px;">
                                <div class="bg-white rounded-circle" style="width: 12px; height: 12px;"></div>
                            </div>
                        </div>
                    </div>
                    <h6 class="fw-bold mb-1" style="font-size: 1.1rem;">{{ Auth::user()->name ?? 'User' }}</h6>
                    <small class="opacity-75" style="font-size: 0.9rem;">{{ Auth::user()->email ?? 'user@example.com' }}</small>

                </div>

                <!-- Menu Section -->
                <div class="p-3">
                    <div class="list-group list-group-flush">
                        <!-- Edit Profile -->
                        <a href="{{ url('/SysConfig1/AccountSetting/Detail/' . encryptWithSessionKey('Edit') . '/' . encryptWithSessionKey(Auth::id())) }}"
                           class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center rounded-3 mb-2 bg-light-hover">
                            <div class="d-flex align-items-center justify-content-center me-3 bg-primary bg-opacity-10 rounded-circle" style="width: 40px; height: 40px;">
                                <i class="bi bi-person-gear text-primary" style="font-size: 1.1rem;"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold text-dark">Edit Profile</div>
                                <small class="text-muted">Update your personal information</small>
                            </div>
                            <i class="bi bi-chevron-right text-muted"></i>
                        </a>

                        <!-- Divider -->
                        <div class="border-top my-3"></div>

                        <!-- Logout -->
                        <form method="POST" action="{{ route('logout') }}" class="mb-0">
                            @csrf
                            <button type="submit" class="list-group-item list-group-item-action border-0 py-3 d-flex align-items-center w-100 text-start rounded-3 bg-light-hover">
                                <div class="d-flex align-items-center justify-content-center me-3 bg-danger bg-opacity-10 rounded-circle" style="width: 40px; height: 40px;">
                                    <i class="bi bi-box-arrow-right text-danger" style="font-size: 1.1rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-danger">Sign Out</div>
                                    <small class="text-muted">Logout from your account</small>
                                </div>
                                <i class="bi bi-chevron-right text-muted"></i>
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-fill d-flex flex-column" id="mainContent" style="margin-left: 280px;">
            <!-- Header -->
            @include('layout.bootstrap.header')

            <!-- Content -->
            <main class="flex-fill" style="background-color: #f8f9fa;">
                <div class="container-fluid p-4">
                    {{ $slot }}
                </div>
            </main>

            <!-- Footer - Always at bottom -->
            <footer class="bg-white border-top py-3 mt-auto">
                <div class="container-fluid px-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <p class="mb-0 text-muted small">© {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved.</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="mb-0 text-muted small">Version 1.0.0</p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Next.js Style Loading Spinner - Bottom Right -->
    <div id="nextjs-loading-spinner" class="nextjs-loading-spinner">
        <!-- Loading Dots Animation (most recognizable loading pattern) -->
        <div class="nextjs-loading-dots">
            <div class="nextjs-loading-dot"></div>
            <div class="nextjs-loading-dot"></div>
            <div class="nextjs-loading-dot"></div>
        </div>
    </div>

    <!-- Scrolltop Button -->
    <div class="scrolltop" id="scrolltop">
        <i class="bi bi-arrow-up"></i>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true" data-bs-keyboard="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imagePreviewModalLabel">Image Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="previewImage" src="" alt="Image Preview" class="img-fluid rounded">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SweetAlert2 & Toastr -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Next.js Style Loading Spinner Implementation
            const loadingSpinner = document.getElementById('nextjs-loading-spinner');
            const mainContent = document.getElementById('mainContent');

            // Loading Functions
            function startLoading() {
                // Show loading spinner
                if (loadingSpinner) {
                    loadingSpinner.classList.add('show');
                }

                // Add subtle fade to content
                if (mainContent) {
                    mainContent.classList.add('page-transition', 'loading');
                }
            }

            function finishLoading() {
                // Hide loading spinner
                if (loadingSpinner) {
                    setTimeout(() => {
                        loadingSpinner.classList.remove('show');
                    }, 300);
                }

                // Remove content fade
                if (mainContent) {
                    setTimeout(() => {
                        mainContent.classList.remove('loading');
                    }, 300);
                }
            }

            // Show loading on initial page load
            startLoading();

            // Hide loading when page is fully loaded
            if (document.readyState === 'loading') {
                window.addEventListener('load', finishLoading);
            } else {
                finishLoading();
            }

            // Test spinner manually (temporary for debugging)
            setTimeout(() => {
                console.log('Testing spinner visibility...');
                if (loadingSpinner) {
                    loadingSpinner.classList.add('show');
                    console.log('Spinner should be visible now');

                    // Hide after 3 seconds for testing
                    setTimeout(() => {
                        loadingSpinner.classList.remove('show');
                        console.log('Spinner hidden');
                    }, 3000);
                }
            }, 1000);

            // Intercept form submissions and links for loading bar
            document.addEventListener('click', function(e) {
                // Check if it's a link that will navigate away
                if (e.target.tagName === 'A' && e.target.href &&
                    !e.target.href.startsWith('javascript:') &&
                    !e.target.href.startsWith('#') &&
                    !e.target.hasAttribute('download') &&
                    !e.target.target === '_blank') {

                    // Only show loading if it's an internal link
                    if (e.target.href.includes(window.location.origin)) {
                        startLoading();
                    }
                }
            });

            // Handle form submissions
            document.addEventListener('submit', function(e) {
                // Don't show loading for logout forms or external forms
                if (!e.target.action || e.target.action.includes(window.location.origin)) {
                    startLoading();
                }
            });

            // Scrolltop functionality
            const scrolltopButton = document.getElementById('scrolltop');
            if (scrolltopButton) {
                window.addEventListener('scroll', function() {
                    if (window.pageYOffset > 300) {
                        scrolltopButton.classList.add('show');
                    } else {
                        scrolltopButton.classList.remove('show');
                    }
                });

                scrolltopButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }

            // Responsive sidebar for mobile (offcanvas) and fixed for large screens
            const sidebarFixed = document.getElementById('sidebarFixed');
            const mobileSidebarEl = document.getElementById('mobileSidebar');
            const mainContentEl = document.getElementById('mainContent');

            function adjustLayout() {
                if (window.innerWidth <= 991.98) {
                    if (sidebarFixed) sidebarFixed.style.display = 'none';
                    if (mainContentEl) mainContentEl.style.marginLeft = '0';
                } else {
                    if (sidebarFixed) sidebarFixed.style.display = 'block';
                    if (mainContentEl) mainContentEl.style.marginLeft = '280px';
                    // ensure offcanvas is hidden when switching to large
                    try {
                        if (mobileSidebarEl) {
                            const bs = bootstrap.Offcanvas.getInstance(mobileSidebarEl);
                            if (bs) bs.hide();
                        }
                    } catch (e) { /* ignore */ }
                }
            }

            window.addEventListener('resize', adjustLayout);
            adjustLayout();

            // Expose loading functions globally for manual control
            window.NextjsLoader = {
                start: startLoading,
                finish: finishLoading,

                // Helper function for AJAX requests
                wrapRequest: function(requestFunction, ...args) {
                    startLoading();
                    return requestFunction(...args).finally(() => {
                        setTimeout(finishLoading, 100);
                    });
                }
            };
        });

        // Image preview function
        function showImagePreview(imageUrl) {
            const previewImage = document.getElementById('previewImage');
            const modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'), {
                keyboard: true, // Enable ESC key
                backdrop: true  // Enable click outside to close
            });
            if (previewImage) {
                previewImage.src = imageUrl;
                modal.show();
            }
        }

        // Additional ESC key handler for image preview modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                const imageModal = document.getElementById('imagePreviewModal');
                if (imageModal && imageModal.classList.contains('show')) {
                    const modalInstance = bootstrap.Modal.getInstance(imageModal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }
            }
        });

        // Print function
        function printReport() {
            window.print();
        }

        // Initialize modern toastr settings with different durations for success and error
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": true,
            "onclick": null,
            "showDuration": "400",
            "hideDuration": "1000",
            "timeOut": "3000", // Default timeout
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "slideDown",
            "hideMethod": "slideUp"
        };

        // Custom toast functions with different durations and colors
        function showToastSuccess(message, title = 'Berhasil') {
            toastr.success(message, title, {
                "timeOut": "5000", // Success toast: 2 detik (lebih pendek)
                "extendedTimeOut": "500",
                "progressBar": true,
                "closeButton": true
            });
        }

        function showToastError(message, title = 'Gagal') {
            toastr.error(message, title, {
                "timeOut": "10000", // Error toast: 6 detik (lebih lama)
                "extendedTimeOut": "2000",
                "progressBar": true,
                "closeButton": true
            });
        }

        function showToastWarning(message, title = 'Peringatan') {
            toastr.warning(message, title, {
                "timeOut": "5000", // Warning toast: 4 detik
                "extendedTimeOut": "1500",
                "progressBar": true,
                "closeButton": true
            });
        }

        function showToastInfo(message, title = 'Info') {
            toastr.info(message, title, {
                "timeOut": "5000", // Info toast: 3 detik
                "extendedTimeOut": "1000",
                "progressBar": true,
                "closeButton": true
            });
        }

        // Livewire event listeners
        document.addEventListener('livewire:init', () => {
            // Integrate Next.js loader with Livewire
            Livewire.on('loading', () => {
                if (window.NextjsLoader) {
                    window.NextjsLoader.start();
                }
            });

            Livewire.on('loaded', () => {
                if (window.NextjsLoader) {
                    setTimeout(() => window.NextjsLoader.finish(), 200);
                }
            });

            // Hook into Livewire's loading states
            document.addEventListener('livewire:navigating', () => {
                if (window.NextjsLoader) {
                    window.NextjsLoader.start();
                }
            });

            document.addEventListener('livewire:navigated', () => {
                if (window.NextjsLoader) {
                    setTimeout(() => window.NextjsLoader.finish(), 300);
                }
            });



            Livewire.on('success', (message) => {
                showToastSuccess(message, 'Berhasil');
            });

            Livewire.on('error', (message) => {
                showToastError(message, 'Gagal');
            });

            Livewire.on('warning', (message) => {
                showToastWarning(message, 'Peringatan');
            });

            Livewire.on('info', (message) => {
                showToastInfo(message, 'Informasi');
            });

            Livewire.on('notify-swal', (dataArray) => {
                if (typeof Swal !== 'undefined') {
                    let data = dataArray[0];
                    let message = data.message || '';
                    let icon = data.type || 'success';
                    let confirmButtonText = 'Ok';

                    Swal.fire({
                        html: message,
                        icon: icon,
                        buttonsStyling: false,
                        confirmButtonText: confirmButtonText,
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        }
                    });
                }
            });

            Livewire.on('open-print-tab', (data) => {
                let url = data[0]?.url || '';
                if (url) {
                    window.open(url, '_blank');
                }
            });

            Livewire.on('full-page-reload', (data) => {
                let url = data[0]?.url || data.url || '';
                if (url) {
                    if (window.NextjsLoader) {
                        window.NextjsLoader.start();
                    }
                    setTimeout(() => {
                        window.location.href = url;
                    }, 200);
                } else {
                    console.error('App.blade: No URL provided in full-page-reload event');
                }
            });

            Livewire.on('open-confirm-dialog', (dataArray) => {
                if (typeof Swal !== 'undefined') {
                    let data = Array.isArray(dataArray) ? dataArray[0] : dataArray;
                    let title = data.title || 'Confirm Action';
                    let message = data.message || 'Are you sure?';
                    let icon = data.icon || 'warning';
                    let confirmMethod = data.confirmMethod || '';
                    let confirmParams = data.confirmParams || null;
                    let confirmButtonText = data.confirmButtonText || 'Yes, confirm';

                    Swal.fire({
                        title: title,
                        text: message,
                        icon: icon,
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: confirmButtonText
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Livewire.dispatch(confirmMethod, {
                                data: confirmParams
                            });
                        }
                    });
                }
            });
        });
    </script>

    @livewireScripts

    {{-- Custom Scripts Stack --}}
    @stack('scripts')
</body>
</html>
