<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" type="text/css" href="{{ asset('customs/css/pagebase.css') }}">

    <!-- DataTable Custom Styles -->
    <link rel="stylesheet" type="text/css" href="{{ asset('css/datatable-custom.css') }}">

    <!-- Global Table Overflow Fix for Dropdowns -->
    <style>
        /* Fix mobile offcanvas backdrop issues */
        .offcanvas-backdrop {
            z-index: 1040;
        }

        /* Ensure only one backdrop can exist */
        .offcanvas-backdrop + .offcanvas-backdrop {
            display: none !important;
        }

        /* Single Application Component - Adaptive Styling */
        .application-selector-container {
            display: block !important;
            width: 100% !important;
        }

        .app-dropdown-container {
            position: relative !important;
            width: 100% !important;
        }

        .app-dropdown-trigger {
            width: 100% !important;
            cursor: pointer !important;
        }

        /* Desktop Sidebar Styling */
        @media (min-width: 992px) {
            #sidebarFixed .application-selector-container {
                padding: 0;
                margin: 0;
            }

            #sidebarFixed .app-dropdown-trigger {
                min-height: 40px;
                font-size: 0.9rem;
            }
        }

        /* Mobile Offcanvas Styling */
        @media (max-width: 991.98px) {
            .offcanvas-title .application-selector-container {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            .offcanvas-title .app-dropdown-trigger {
                min-height: 36px !important;
                font-size: 0.85rem !important;
                padding: 6px 10px !important;
                border: 1px solid var(--bs-border-color) !important;
                background-color: var(--bs-body-bg) !important;
                text-align: left !important;
            }

            /* Mobile dropdown positioning */
            .offcanvas .app-dropdown-container .dropdown-menu {
                position: absolute !important;
                top: 100% !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 1056 !important;
                width: 100% !important;
                max-height: 250px !important;
                overflow-y: auto !important;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
                border-radius: 0.375rem !important;
                animation: dropdownFadeIn 0.2s ease-out;
            }

            /* Auto-close dropdown when switching apps in mobile */
            .offcanvas .dropdown-menu.show {
                display: block !important;
            }
        }

        @keyframes dropdownFadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Additional dropdown fixes */
        .dropdown-menu {
            z-index: 1050 !important;
        }

        .dropdown.show .dropdown-menu,
        .btn-group.show .dropdown-menu {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .dropdown-toggle::after {
            display: inline-block !important;
            margin-left: 0.255em;
            vertical-align: 0.255em;
            content: "";
            border-top: 0.3em solid;
            border-right: 0.3em solid transparent;
            border-bottom: 0;
            border-left: 0.3em solid transparent;
        }

        /* FORCE Mobile Application Component Visibility - CSS Only Solution */
        @media (max-width: 991.98px) {
            /* Force visibility in mobile offcanvas header */
            #mobileSidebar .offcanvas-header .w-100 {
                display: block !important;
                width: 100% !important;
                flex: 1 !important;
            }

            #mobileSidebar .application-selector-container {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                width: 100% !important;
                height: auto !important;
                min-height: 40px !important;
                position: relative !important;
                z-index: 1 !important;
                background-color: #fff !important;
                border: 1px solid #dee2e6 !important;
                border-radius: 0.375rem !important;
                padding: 5px !important;
                margin: 0 !important;
            }

            #mobileSidebar .app-dropdown-container {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                width: 100% !important;
                position: relative !important;
            }

            #mobileSidebar .app-dropdown-trigger {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                width: 100% !important;
                min-height: 36px !important;
                padding: 6px 10px !important;
                cursor: pointer !important;
                background-color: #fff !important;
                border: 1px solid #ced4da !important;
                border-radius: 0.375rem !important;
                color: #212529 !important;
                font-size: 0.875rem !important;
                text-align: left !important;
            }

            #mobileSidebar .app-dropdown-trigger:hover {
                background-color: #f8f9fa !important;
                border-color: #0d6efd !important;
            }

            #mobileSidebar .current-app-logo {
                width: 20px !important;
                height: 20px !important;
                display: inline-block !important;
                margin-right: 8px !important;
            }

            #mobileSidebar .current-app-name {
                display: inline-block !important;
                font-size: 0.875rem !important;
                color: #212529 !important;
            }

            #mobileSidebar .dropdown-menu {
                position: absolute !important;
                top: 100% !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 1056 !important;
                width: 100% !important;
                max-height: 250px !important;
                overflow-y: auto !important;
                background-color: #fff !important;
                border: 1px solid rgba(0,0,0,.15) !important;
                border-radius: 0.375rem !important;
                box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15) !important;
                margin-top: 2px !important;
            }

            #mobileSidebar .dropdown-menu.show {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }

            /* Force all child elements to be visible */
            #mobileSidebar .application-selector-container * {
                visibility: visible !important;
                opacity: 1 !important;
            }

            /* Force Livewire component to be visible */
            #mobileSidebar [wire\\:id] {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }
        }
    </style>

    <!-- External Libraries -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.8/inputmask.min.js"></script>

    <!-- ApexCharts for charting -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script>

    <!-- Bootstrap JS (CDN fallback so offcanvas works without Vite build) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <!-- Vite Assets -->
    @vite(['resources/bootstrap/app.scss', 'resources/bootstrap/app.js'])

    @livewireStyles
</head>
<body style="font-family: 'Inter', sans-serif;">
    <div class="d-flex min-vh-100" id="app_root">
        <!-- Sidebar (offcanvas on small screens, fixed on large) -->
        <div class="bg-white border-end shadow-sm position-fixed top-0 start-0 h-100 d-none d-lg-block" style="width: 280px; z-index: 1000;" id="sidebarFixed">
            <!-- Application Selector at top -->
            <div class="p-3 border-bottom">
                @livewire('component.application-component')
            </div>

            <div class="p-3">
                @livewire('component.sidebar-menu')
            </div>
        </div>

        <!-- Offcanvas Sidebar for mobile (left) -->
        <div class="offcanvas offcanvas-start d-lg-none" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
            <div class="offcanvas-header border-bottom">
                <div class="w-100">
                    <!-- Mobile Application Component -->
                    @livewire('component.application-component')
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                @livewire('component.sidebar-menu')
            </div>
        </div>

        <!-- Offcanvas Profile for mobile (right) -->
        <div class="offcanvas offcanvas-end d-lg-none" tabindex="-1" id="mobileProfile" aria-labelledby="mobileProfileLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="mobileProfileLabel">Account</h5>
                <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <i class="bi bi-person-circle text-primary" style="font-size: 5rem;"></i>
                    </div>
                    <h6 class="fw-bold mb-1">{{ Auth::user()->name ?? 'User' }}</h6>
                    <small class="text-muted">{{ Auth::user()->email ?? '' }}</small>
                </div>

                <div class="list-group list-group-flush">
                    <a href="{{ url('/SysConfig1/ConfigUser/Detail/' . encryptWithSessionKey('Edit') . '/' . encryptWithSessionKey(Auth::id())) }}" class="list-group-item list-group-item-action border-0 py-3">
                        <i class="bi bi-person me-3"></i>Edit Profile
                    </a>
                    <div class="border-top my-2"></div>
                    <form method="POST" action="{{ route('logout') }}" class="mb-0">
                        @csrf
                        <button type="submit" class="list-group-item list-group-item-action border-0 py-3 text-danger">
                            <i class="bi bi-box-arrow-right me-3"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-fill d-flex flex-column" id="mainContent" style="margin-left: 0;">
            <!-- Header -->
            @include('layout.bootstrap.header')

            <!-- Content -->
            <main class="flex-fill" style="background-color: #f8f9fa;">
                <div class="container-fluid p-4">
                    {{ $slot }}
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-white border-top py-3">
                <div class="container-fluid px-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <p class="mb-0 text-muted small">© {{ date('Y') }} {{ config('app.name', 'Laravel') }}. All rights reserved.</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="mb-0 text-muted small">Version {{ config('app.version', '1.0.0') }}</p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Next.js Style Loading Bar -->
    <div id="nextjs-loading-bar" class="nextjs-loading-bar" style="display: none;">
        <div class="nextjs-progress"></div>
    </div>

    <!-- Scrolltop Button -->
    <div class="scrolltop" id="scrolltop">
        <i class="bi bi-arrow-up"></i>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Image Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
            // Next.js Style Loading Bar Implementation
            const loadingBar = document.getElementById('nextjs-loading-bar');
            const progressBar = loadingBar.querySelector('.nextjs-progress');
            const mainContent = document.getElementById('mainContent');

            let loadingInterval;
            let currentProgress = 0;

            // Loading Bar Functions
            function startLoading() {
                if (loadingBar) {
                    loadingBar.style.display = 'block';
                    loadingBar.classList.add('loading');
                    currentProgress = 0;
                    progressBar.style.width = '0%';

                    // Simulate progressive loading
                    loadingInterval = setInterval(() => {
                        if (currentProgress < 90) {
                            currentProgress += Math.random() * 15;
                            currentProgress = Math.min(currentProgress, 90);
                            progressBar.style.width = currentProgress + '%';
                        }
                    }, 200);
                }

                // Add subtle fade to content
                if (mainContent) {
                    mainContent.classList.add('page-transition', 'loading');
                }
            }

            function finishLoading() {
                if (loadingInterval) {
                    clearInterval(loadingInterval);
                }

                if (loadingBar && progressBar) {
                    currentProgress = 100;
                    progressBar.style.width = '100%';

                    // Hide loading bar after animation
                    setTimeout(() => {
                        loadingBar.style.display = 'none';
                        loadingBar.classList.remove('loading');
                        progressBar.style.width = '0%';
                    }, 500);
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

            // Mobile App Component Management
            function initializeMobileAppSwitcher() {
                const mobileSidebar = document.getElementById('mobileSidebar');
                const mobileProfile = document.getElementById('mobileProfile');

                if (mobileSidebar) {
                    // Handle app switching in mobile - auto-close dropdown and sidebar
                    mobileSidebar.addEventListener('click', function(event) {
                        // If clicking on an app option, close both dropdown and sidebar
                        if (event.target.closest('.app-option')) {
                            setTimeout(() => {
                                // Close any open dropdowns
                                const openDropdowns = mobileSidebar.querySelectorAll('.dropdown-menu.show');
                                openDropdowns.forEach(menu => menu.classList.remove('show'));

                                // Close the sidebar after app switch
                                const offcanvasInstance = bootstrap.Offcanvas.getInstance(mobileSidebar);
                                if (offcanvasInstance) {
                                    offcanvasInstance.hide();
                                }
                            }, 500); // Small delay to allow Livewire to process
                        }
                    });

                    // Ensure dropdown closes when sidebar closes
                    mobileSidebar.addEventListener('hide.bs.offcanvas', function() {
                        const openDropdowns = mobileSidebar.querySelectorAll('.dropdown-menu.show');
                        openDropdowns.forEach(menu => {
                            menu.classList.remove('show');
                            const container = menu.closest('.dropdown');
                            if (container) container.classList.remove('show');
                        });
                    });

                    // Clean backdrop on close
                    mobileSidebar.addEventListener('hidden.bs.offcanvas', function() {
                        setTimeout(() => {
                            const backdrops = document.querySelectorAll('.offcanvas-backdrop');
                            backdrops.forEach(backdrop => backdrop.remove());

                            // Restore body state
                            document.body.classList.remove('modal-open');
                            document.body.style.overflow = '';
                            document.body.style.paddingRight = '';
                        }, 100);
                    });
                }

                // Same for profile offcanvas
                if (mobileProfile) {
                    mobileProfile.addEventListener('hidden.bs.offcanvas', function() {
                        setTimeout(() => {
                            const backdrops = document.querySelectorAll('.offcanvas-backdrop');
                            backdrops.forEach(backdrop => backdrop.remove());

                            document.body.classList.remove('modal-open');
                            document.body.style.overflow = '';
                            document.body.style.paddingRight = '';
                        }, 100);
                    });
                }
            }

            // Initialize mobile handling
            initializeMobileAppSwitcher();

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
            const modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
            if (previewImage) {
                previewImage.src = imageUrl;
                modal.show();
            }
        }

        // Print function
        function printReport() {
            window.print();
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

            // Show loading bar for any Livewire requests
            Livewire.hook('request', ({ uri, options, payload, respond, succeed, fail }) => {
                if (window.NextjsLoader) {
                    window.NextjsLoader.start();
                }

                succeed(({ status, json }) => {
                    if (window.NextjsLoader) {
                        setTimeout(() => window.NextjsLoader.finish(), 200);
                    }
                });

                fail(({ status, json }) => {
                    if (window.NextjsLoader) {
                        setTimeout(() => window.NextjsLoader.finish(), 200);
                    }
                });
            });

            Livewire.on('success', (message) => {
                if (typeof toastr !== 'undefined') {
                    toastr.success(message);
                }
            });

            Livewire.on('error', (message) => {
                if (typeof toastr !== 'undefined') {
                    toastr.error(message);
                }
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

    <!-- Next.js Style Loading Bar Styles -->
    <style>
        .nextjs-loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            z-index: 9999;
            background: transparent;
        }

        .nextjs-progress {
            height: 100%;
            background: linear-gradient(90deg, #495057 0%, #6c757d 50%, #495057 100%);
            background-size: 200% 100%;
            border-radius: 0 3px 3px 0;
            box-shadow: 0 0 8px rgba(73, 80, 87, 0.6);
            width: 0%;
            transition: width 0.3s ease;
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }

        .nextjs-loading-bar.loading .nextjs-progress {
            animation: shimmer 2s infinite, loadingPulse 1.5s ease-in-out infinite;
        }

        @keyframes loadingPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* Smooth fade for page transitions */
        .page-transition {
            transition: opacity 0.3s ease-in-out;
        }

        .page-transition.loading {
            opacity: 0.95;
        }

        /* Loading overlay for specific actions (optional) */
        .content-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(248, 249, 250, 0.8);
            backdrop-filter: blur(2px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            border-radius: 8px;
        }

        .content-loading-overlay.show {
            display: flex;
        }

        .mini-spinner {
            width: 24px;
            height: 24px;
            border: 3px solid #e9ecef;
            border-top: 3px solid #495057;
            border-radius: 50%;
            animation: miniSpin 1s linear infinite;
        }

        @keyframes miniSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    @livewireScripts
    <script>
        // Ensure mobile offcanvas instances are initialized and provide debug logs
        document.addEventListener('DOMContentLoaded', function() {
            // Bootstrap and Dropdown Debugging

            // Global dropdown debugging
            function debugAllDropdowns() {
                const allDropdowns = document.querySelectorAll('.dropdown-toggle, [data-bs-toggle="dropdown"]');

                allDropdowns.forEach((trigger, index) => {
                    // Silent initialization check
                });
            }

            // Force initialize all dropdowns
            function forceInitializeDropdowns() {
                const allTriggers = document.querySelectorAll('.dropdown-toggle, [data-bs-toggle="dropdown"]');

                allTriggers.forEach((trigger) => {
                    try {
                        // Dispose existing instance
                        const existingInstance = bootstrap.Dropdown.getInstance(trigger);
                        if (existingInstance) {
                            existingInstance.dispose();
                        }

                        // Create new instance
                        const newInstance = new bootstrap.Dropdown(trigger);

                        // Add manual click handler as fallback
                        trigger.addEventListener('click', function(e) {
                            const menu = trigger.nextElementSibling;
                            if (menu && menu.classList.contains('dropdown-menu')) {
                                const isVisible = menu.classList.contains('show');

                                // Toggle manually if Bootstrap fails
                                if (!isVisible) {
                                    // Hide all other dropdowns first
                                    document.querySelectorAll('.dropdown-menu.show').forEach(otherMenu => {
                                        otherMenu.classList.remove('show');
                                        const otherContainer = otherMenu.closest('.dropdown');
                                        if (otherContainer) otherContainer.classList.remove('show');
                                    });

                                    // Show this dropdown
                                    menu.classList.add('show');
                                    const container = trigger.closest('.dropdown');
                                    if (container) container.classList.add('show');
                                } else {
                                    // Hide this dropdown
                                    menu.classList.remove('show');
                                    const container = trigger.closest('.dropdown');
                                    if (container) container.classList.remove('show');
                                }
                            }
                        });

                    } catch (error) {
                        console.error('Error initializing dropdown:', error, trigger);
                    }
                });
            }            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                        menu.classList.remove('show');
                        const container = menu.closest('.dropdown');
                        if (container) container.classList.remove('show');
                    });
                }
            });

            // Listen for reinit requests from components
            document.addEventListener('reinitDropdowns', function() {
                setTimeout(() => {
                    forceInitializeDropdowns();
                }, 100);
            });

            // Run debugging after DOM is ready
            setTimeout(() => {
                debugAllDropdowns();
                forceInitializeDropdowns();
            }, 500);

            try {
                const mobileSidebarEl = document.getElementById('mobileSidebar');
                const mobileProfileEl = document.getElementById('mobileProfile');
                if (mobileSidebarEl) {
                    // Initialize if not already
                    if (!bootstrap.Offcanvas.getInstance(mobileSidebarEl)) {
                        new bootstrap.Offcanvas(mobileSidebarEl);
                    }
                }

                if (mobileProfileEl) {
                    if (!bootstrap.Offcanvas.getInstance(mobileProfileEl)) {
                        new bootstrap.Offcanvas(mobileProfileEl);
                    }
                }
            } catch (err) {
                console.error('Error initializing mobile offcanvas', err);
            }
        });
    </script>

    {{-- Custom Scripts Stack --}}
    @stack('scripts')
</body>
</html>
