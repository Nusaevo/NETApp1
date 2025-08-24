//
// Main Application JavaScript (Vite Compatible)
//

// Import global dependencies
window.jQuery = window.$ = require('jquery');
window.bootstrap = require('bootstrap');

// Custom app initialization
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize Bootstrap popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Scroll to top functionality
    initScrollTop();

    // Initialize Select2 dropdowns
    if (typeof $.fn.select2 !== 'undefined') {
        $('.select2').select2({
            theme: 'bootstrap-5'
        });
    }

    // Initialize DataTables with Bootstrap styling
    if (typeof $.fn.DataTable !== 'undefined') {
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
            console.log(`${type.toUpperCase()}: ${message}`);
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
        if (!document.getElementById('app-loading')) {
            const loading = document.createElement('div');
            loading.id = 'app-loading';
            loading.innerHTML = '<div class="d-flex justify-content-center"><div class="spinner-border" role="status"></div></div>';
            loading.style.cssText = 'position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:9999;background:rgba(255,255,255,0.8);padding:20px;border-radius:5px;';
            document.body.appendChild(loading);
        }
    },

    hideLoading: function() {
        const loading = document.getElementById('app-loading');
        if (loading) {
            loading.remove();
        }
    }
};
