//
// Main Application JavaScript
// Bootstrap-based functionality without Metronic dependencies
//

// Import Bootstrap JavaScript
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

// Custom app initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // Initialize Bootstrap popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    })

    // Initialize all collapse elements (for accordion menus)
    document.querySelectorAll('.collapse').forEach(function (collapseElement) {
        new bootstrap.Collapse(collapseElement, {
            toggle: false
        });
    });

    // Scroll to top functionality
    initScrollTop();

    // Initialize Select2 dropdowns (if jQuery and Select2 are loaded)
    if (typeof $ !== 'undefined' && typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            theme: 'bootstrap-5'
        });
    }

    // Initialize DataTables with Bootstrap styling (if jQuery and DataTables are loaded)
    if (typeof $ !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
        $('.datatable').DataTable({
            responsive: true,
            pageLength: 25,
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ data per halaman",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Selanjutnya",
                    previous: "Sebelumnya"
                }
            }
        });
    }

    console.log('Bootstrap app initialized successfully');
});

// Scroll to top functionality
function initScrollTop() {
    const scrollTopElement = document.getElementById('scrolltop');
    if (!scrollTopElement) return;

    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            scrollTopElement.classList.add('show');
        } else {
            scrollTopElement.classList.remove('show');
        }
    });

    scrollTopElement.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// Global utilities
window.AppUtils = {
    // Toast notifications
    toast: function(message, type = 'success') {
        if (typeof toastr !== 'undefined') {
            toastr[type](message);
        } else {
            console.log(`Toast ${type}: ${message}`);
        }
    },

    // Sweet Alert wrapper
    confirm: function(title, text, callback) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: title,
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed && callback) {
                    callback();
                }
            });
        } else {
            if (confirm(title + '\n' + text) && callback) {
                callback();
            }
        }
    },

    // Loading overlay
    showLoading: function() {
        const loadingContainer = document.getElementById('custom-loading-container');
        if (loadingContainer) {
            loadingContainer.style.display = 'flex';
        }
    },

    hideLoading: function() {
        const loadingContainer = document.getElementById('custom-loading-container');
        if (loadingContainer) {
            loadingContainer.style.display = 'none';
        }
    }
};

// Export jQuery if available
if (typeof $ !== 'undefined') {
    window.$ = window.jQuery = $;
}
